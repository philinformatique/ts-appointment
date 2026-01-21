<?php
/**
 * Intégration Google Calendar
 */

if (!defined('ABSPATH')) {
    exit;
}

class TS_Appointment_Google_Calendar {
    
    private $client_id;
    private $client_secret;
    private $redirect_uri;
    private $access_token;
    private $refresh_token;
    private $calendar_id;

    public function __construct() {
        $this->client_id = get_option('ts_appointment_google_client_id');
        $this->client_secret = get_option('ts_appointment_google_client_secret');
        $this->redirect_uri = admin_url('admin.php?page=ts-appointment-settings&action=google_callback');
        $this->access_token = get_option('ts_appointment_google_access_token');
        $this->refresh_token = get_option('ts_appointment_google_refresh_token');
        $this->calendar_id = get_option('ts_appointment_google_calendar_id');
    }

    /**
     * Obtenir l'URL d'autorisation Google
     */
    public function get_auth_url() {
        $params = array(
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/calendar',
            'access_type' => 'offline', // nécessaire pour obtenir un refresh_token
            'prompt' => 'consent', // force Google à redonner un refresh_token même si un consentement existe déjà
            'include_granted_scopes' => 'true',
        );

        return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
    }

    /**
     * Échanger le code d'autorisation contre un token
     */
    public function get_access_token($code) {
        // Vérifier les paramètres essentiels
        if (empty($this->client_id)) {
            error_log('TS Appointment: get_access_token failed - client_id is empty');
            return false;
        }
        if (empty($this->client_secret)) {
            error_log('TS Appointment: get_access_token failed - client_secret is empty');
            return false;
        }
        if (empty($code)) {
            error_log('TS Appointment: get_access_token failed - code is empty');
            return false;
        }

        error_log('TS Appointment: Attempting Google token exchange with redirect_uri=' . $this->redirect_uri);

        $response = wp_remote_post('https://oauth2.googleapis.com/token', array(
            'body' => array(
                'code' => $code,
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'redirect_uri' => $this->redirect_uri,
                'grant_type' => 'authorization_code',
            ),
        ));

        if (is_wp_error($response)) {
            error_log('TS Appointment: wp_remote_post failed - ' . $response->get_error_message());
            return false;
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $body_raw = wp_remote_retrieve_body($response);
        error_log('TS Appointment: Google response code=' . $http_code . ', body=' . substr($body_raw, 0, 200));

        $body = json_decode($body_raw);
        
        if (isset($body->access_token)) {
            error_log('TS Appointment: Token received successfully');
            update_option('ts_appointment_google_access_token', $body->access_token);
            
            if (isset($body->refresh_token)) {
                update_option('ts_appointment_google_refresh_token', $body->refresh_token);
                $this->refresh_token = $body->refresh_token;
            }
            
            $this->access_token = $body->access_token;
            return true;
        }

        error_log('TS Appointment: Token exchange failed - access_token not in response');
        if (isset($body->error)) {
            error_log('TS Appointment: Google error code: ' . $body->error . ' - ' . (isset($body->error_description) ? $body->error_description : ''));
        }

        return false;
    }

    /**
     * Rafraîchir le token d'accès via le refresh_token stocké
     */
    private function refresh_access_token() {
        if (empty($this->refresh_token) || empty($this->client_id) || empty($this->client_secret)) {
            error_log('TS Appointment: refresh_access_token skipped - missing refresh_token/client credentials');
            return false;
        }

        $response = wp_remote_post('https://oauth2.googleapis.com/token', array(
            'body' => array(
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->refresh_token,
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
            ),
        ));

        if (is_wp_error($response)) {
            error_log('TS Appointment: refresh_access_token http error - ' . $response->get_error_message());
            return false;
        }

        $body_raw = wp_remote_retrieve_body($response);
        $body = json_decode($body_raw);
        if (!empty($body->access_token)) {
            $this->access_token = $body->access_token;
            update_option('ts_appointment_google_access_token', $body->access_token);
            error_log('TS Appointment: access token refreshed successfully');
            return true;
        }

        error_log('TS Appointment: refresh_access_token failed - response: ' . substr($body_raw, 0, 300));
        return false;
    }

    /**
     * Normalise date+heure en ISO 8601 pour Google (évite les doubles secondes)
     */
    private function format_event_datetime($date, $time) {
        $tz_string = get_option('ts_appointment_timezone', 'UTC');
        $tz = new DateTimeZone($tz_string ?: 'UTC');
        $dt = DateTime::createFromFormat('Y-m-d H:i', $date . ' ' . $time, $tz);
        if (!$dt) {
            // Fallback si le temps contient déjà des secondes
            $dt = new DateTime($date . ' ' . $time, $tz);
        }
        return $dt->format('Y-m-d\TH:i:s');
    }

    /**
     * Créer un événement dans Google Calendar
     */
    public function create_event($appointment, $is_confirmed = null) {
        if (!$this->access_token || !$this->calendar_id) {
            return false;
        }

        // Defensive: ensure we never forward verification tokens or sensitive fields to Google
        if (is_object($appointment) && isset($appointment->turnstile_token)) {
            unset($appointment->turnstile_token);
        }
        if (is_object($appointment) && isset($appointment->turnstile_response)) {
            unset($appointment->turnstile_response);
        }

        // Déterminer si le rendez-vous est confirmé; par défaut, on se base sur le statut stocké
        $confirmed = $is_confirmed;
        if ($confirmed === null && isset($appointment->status)) {
            $confirmed = ($appointment->status === 'confirmed');
        }

        $service = TS_Appointment_Database::get_service($appointment->service_id);
        $status_prefix = $confirmed ? '' : '[En attente] ';
        $status_line = $confirmed ? __('Statut: confirmé', 'ts-appointment') : __('Statut: en attente de confirmation', 'ts-appointment');
        
        $start_iso = $this->format_event_datetime($appointment->appointment_date, $appointment->appointment_time);
        $event = array(
            'summary' => $status_prefix . $service->name . ' - ' . $appointment->client_name,
            'description' => 'Client: ' . $appointment->client_name . "\n" .
                           'Email: ' . $appointment->client_email . "\n" .
                           'Téléphone: ' . $appointment->client_phone . "\n" .
                           'Type: ' . $this->get_appointment_type_label($appointment->appointment_type) . "\n" .
                           'Notes: ' . $appointment->notes . "\n" .
                           $status_line,
            'start' => array(
                'dateTime' => $start_iso,
                'timeZone' => get_option('ts_appointment_timezone', 'UTC'),
            ),
            'end' => array(
                'dateTime' => $this->calculate_end_time($appointment->appointment_date, $appointment->appointment_time, $service->duration),
                'timeZone' => get_option('ts_appointment_timezone', 'UTC'),
            ),
            // Assure un créneau occupé même si le rendez-vous est en attente
            'transparency' => 'opaque',
            'attendees' => array(
                array('email' => $appointment->client_email),
            ),
        );

        // Optionally include reminders: email override only when enabled in settings
        $google_email_reminders = get_option('ts_appointment_google_email_reminders', 0);
        if ($google_email_reminders) {
            $event['reminders'] = array(
                'useDefault' => false,
                'overrides' => array(
                    array('method' => 'email', 'minutes' => 24 * 60),
                    array('method' => 'popup', 'minutes' => 30),
                ),
            );
        } else {
            $event['reminders'] = array('useDefault' => false);
        }

        if ($appointment->client_address) {
            $event['location'] = $appointment->client_address;
        }

        error_log('TS Appointment: Creating Google event with summary: ' . $event['summary']);
        error_log('TS Appointment: Event start: ' . $event['start']['dateTime'] . ', timeZone: ' . $event['start']['timeZone']);
        error_log('TS Appointment: Calendar ID: ' . $this->calendar_id);

        // Respect sendUpdates option: none|externalOnly|all
        $sendUpdates = get_option('ts_appointment_google_send_updates', 'none');
        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . $this->calendar_id . '/events';
        if (in_array($sendUpdates, array('none','externalOnly','all'), true)) {
            $url .= '?sendUpdates=' . rawurlencode($sendUpdates);
        }

        $response = wp_remote_post(
            $url,
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->access_token,
                    'Content-Type' => 'application/json',
                ),
                'body' => json_encode($event),
            )
        );

        if (is_wp_error($response)) {
            error_log('TS Appointment: wp_remote_post error - ' . $response->get_error_message());
            return false;
        }

        $http_code = wp_remote_retrieve_response_code($response);
        $body_raw = wp_remote_retrieve_body($response);
        error_log('TS Appointment: Google create_event response code=' . $http_code);
        
        $body = json_decode($body_raw);
        
        if ($http_code === 401) {
            error_log('TS Appointment: create_event received 401, attempting token refresh');
            if ($this->refresh_access_token()) {
                return $this->create_event($appointment, $is_confirmed);
            }
        }

        if (isset($body->id)) {
            error_log('TS Appointment: Google event created successfully with id: ' . $body->id);
            return $body->id;
        }
        
        if (isset($body->error)) {
            error_log('TS Appointment: Google API error - code: ' . $body->error->code . ', message: ' . $body->error->message);
        } else {
            error_log('TS Appointment: Google create_event failed - no id in response. Response: ' . substr($body_raw, 0, 500));
        }

        return false;
    }

    /**
     * Mettre à jour un événement dans Google Calendar
     */
    public function update_event($appointment) {
        if (!$this->access_token || !$this->calendar_id) {
            return false;
        }

        if (!$appointment->google_calendar_id) {
            return false;
        }

        // Defensive: remove any verification tokens from the appointment object
        if (is_object($appointment) && isset($appointment->turnstile_token)) {
            unset($appointment->turnstile_token);
        }
        if (is_object($appointment) && isset($appointment->turnstile_response)) {
            unset($appointment->turnstile_response);
        }

        $service = TS_Appointment_Database::get_service($appointment->service_id);
        $is_confirmed = isset($appointment->status) ? ($appointment->status === 'confirmed') : false;
        $status_prefix = $is_confirmed ? '' : '[En attente] ';
        $status_line = $is_confirmed ? __('Statut: confirmé', 'ts-appointment') : __('Statut: en attente de confirmation', 'ts-appointment');
        
        $start_iso = $this->format_event_datetime($appointment->appointment_date, $appointment->appointment_time);
        $event = array(
            'summary' => $status_prefix . $service->name . ' - ' . $appointment->client_name,
            'description' => 'Client: ' . $appointment->client_name . "\n" .
                           'Email: ' . $appointment->client_email . "\n" .
                           'Téléphone: ' . $appointment->client_phone . "\n" .
                           'Type: ' . $this->get_appointment_type_label($appointment->appointment_type) . "\n" .
                           'Notes: ' . $appointment->notes . "\n" .
                           $status_line,
            'start' => array(
                'dateTime' => $start_iso,
                'timeZone' => get_option('ts_appointment_timezone', 'UTC'),
            ),
            'end' => array(
                'dateTime' => $this->calculate_end_time($appointment->appointment_date, $appointment->appointment_time, $service->duration),
                'timeZone' => get_option('ts_appointment_timezone', 'UTC'),
            ),
            'transparency' => 'opaque',
        );

        // Respect sendUpdates option for updates to avoid notifying attendees unnecessarily
        $sendUpdates = get_option('ts_appointment_google_send_updates', 'none');
        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . $this->calendar_id . '/events/' . $appointment->google_calendar_id;
        if (in_array($sendUpdates, array('none','externalOnly','all'), true)) {
            $url .= '?sendUpdates=' . rawurlencode($sendUpdates);
        }

        // Use PATCH to partially update the event (less disruptive than full PUT)
        $response = wp_remote_request(
            $url,
            array(
                'method' => 'PATCH',
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->access_token,
                    'Content-Type' => 'application/json',
                ),
                'body' => json_encode($event),
            )
        );

        if (is_wp_error($response)) {
            return false;
        }

        if (wp_remote_retrieve_response_code($response) === 401) {
            error_log('TS Appointment: update_event received 401, attempting token refresh');
            if ($this->refresh_access_token()) {
                return $this->update_event($appointment);
            }
        }

        return true;
    }

    /**
     * Supprimer un événement dans Google Calendar
     */
    public function delete_event($appointment) {
        if (!$this->access_token || !$this->calendar_id || !$appointment->google_calendar_id) {
            return false;
        }

        // Respect sendUpdates option for deletes to avoid notifying attendees if configured
        $sendUpdates = get_option('ts_appointment_google_send_updates', 'none');
        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . $this->calendar_id . '/events/' . $appointment->google_calendar_id;
        if (in_array($sendUpdates, array('none','externalOnly','all'), true)) {
            $url .= '?sendUpdates=' . rawurlencode($sendUpdates);
        }

        $response = wp_remote_request(
            $url,
            array(
                'method' => 'DELETE',
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->access_token,
                ),
            )
        );

        if (is_wp_error($response)) {
            return false;
        }

        if (wp_remote_retrieve_response_code($response) === 401) {
            error_log('TS Appointment: delete_event received 401, attempting token refresh');
            if ($this->refresh_access_token()) {
                return $this->delete_event($appointment);
            }
        }

        return true;
    }

    /**
     * Calculer l'heure de fin
     */
    private function calculate_end_time($date, $time, $duration) {
        $timestamp = strtotime($date . ' ' . $time) + ($duration * 60);
        return date('Y-m-d\TH:i:s', $timestamp);
    }

    /**
     * Obtenir le libellé du type de rendez-vous
     */
    private function get_appointment_type_label($type) {
        // Tenter de récupérer l'étiquette depuis la configuration des lieux
        $locs = json_decode(get_option('ts_appointment_locations_config'), true);
        if (is_array($locs)) {
            foreach ($locs as $l) {
                if (isset($l['key']) && $l['key'] === $type) {
                    return isset($l['label']) ? $l['label'] : $type;
                }
            }
        }
        // Valeurs par défaut
        $types = array(
            'on_site' => __('Au bureau', 'ts-appointment'),
            'remote' => __('À distance', 'ts-appointment'),
            'home' => __('Chez le client', 'ts-appointment'),
        );
        return isset($types[$type]) ? $types[$type] : $type;
    }

    /**
     * Récupérer les événements Google pour une date donnée (utilisé pour détecter les conflits)
     * Retourne un tableau de fenêtres temporelles [ 'start_ts' => int, 'end_ts' => int ]
     */
    public function get_events_for_date($date) {
        if (!$this->access_token || !$this->calendar_id) {
            return array();
        }

        $tz_string = get_option('ts_appointment_timezone', 'UTC');
        $tz = new DateTimeZone($tz_string ?: 'UTC');
        $time_min_dt = new DateTime($date . ' 00:00:00', $tz);
        $time_max_dt = new DateTime($date . ' 23:59:59', $tz);
        $time_min = $time_min_dt->format(DateTime::RFC3339);
        $time_max = $time_max_dt->format(DateTime::RFC3339);

        $query = array(
            'timeMin' => $time_min,
            'timeMax' => $time_max,
            'singleEvents' => 'true',
            'orderBy' => 'startTime',
            'timeZone' => $tz_string,
        );

        error_log('TS Appointment Google: fetch events timeMin=' . $time_min . ' timeMax=' . $time_max . ' tz=' . $tz_string);

        $response = wp_remote_get('https://www.googleapis.com/calendar/v3/calendars/' . $this->calendar_id . '/events?' . http_build_query($query), array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
            ),
        ));

        if (is_wp_error($response)) {
            error_log('TS Appointment Google: wp_remote_get error ' . $response->get_error_message());
            return array();
        }

        $code = wp_remote_retrieve_response_code($response);
        $body_raw = wp_remote_retrieve_body($response);
        error_log('TS Appointment Google: response code=' . $code . ' len=' . strlen($body_raw));

        if ($code === 401) {
            error_log('TS Appointment Google: get_events_for_date received 401, attempting token refresh');
            if ($this->refresh_access_token()) {
                return $this->get_events_for_date($date);
            }
        }

        $body = json_decode($body_raw, true);
        if (empty($body['items']) || !is_array($body['items'])) {
            return array();
        }

        $events = array();
        foreach ($body['items'] as $item) {
            // Support all-day events (date) and timed events (dateTime)
            $start_iso = $item['start']['dateTime'] ?? ($item['start']['date'] . 'T00:00:00' ?? null);
            $end_iso = $item['end']['dateTime'] ?? ($item['end']['date'] . 'T23:59:59' ?? null);
            if (!$start_iso || !$end_iso) {
                continue;
            }
            $start_ts = strtotime($start_iso);
            $end_ts = strtotime($end_iso);
            if ($start_ts && $end_ts) {
                $events[] = array('start_ts' => $start_ts, 'end_ts' => $end_ts);
            }
        }

        error_log('TS Appointment Google: parsed events=' . count($events));
        return $events;
    }
}
