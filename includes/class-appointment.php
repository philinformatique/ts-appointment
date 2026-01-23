<?php
/**
 * Gestion des rendez-vous
 */

if (!defined('ABSPATH')) {
    exit;
}

class TS_Appointment_Manager {
    
    public static function book_appointment($data) {
        // Validation
        $validation = self::validate_appointment($data);
        if (!$validation['valid']) {
            return array('success' => false, 'message' => $validation['message']);
        }

        // Hook avant création
        do_action('ts_appointment_before_book', $data);

        // Insérer le rendez-vous — construire dynamiquement depuis form_schema (éviter hardcoded fields)
        $appointment_data = array(
            'service_id' => intval($data['service_id']),
            'appointment_type' => sanitize_text_field($data['appointment_type']),
            'appointment_date' => sanitize_text_field($data['appointment_date']),
            'appointment_time' => sanitize_text_field($data['appointment_time']),
            'status' => 'pending',
        );

        // Build client_data JSON from schema fields and location extras
        $client_data_json = array();
        $form_schema = json_decode(get_option('ts_appointment_form_schema'), true);
        $extra = isset($data['extra']) && is_array($data['extra']) ? $data['extra'] : array();
        
        // Collect form schema fields
        if (is_array($form_schema)) {
            foreach ($form_schema as $f) {
                $k = isset($f['key']) ? $f['key'] : '';
                if (!$k) continue;
                $val = null;
                if (isset($data[$k])) $val = $data[$k];
                elseif (isset($extra[$k])) $val = $extra[$k];
                if ($val !== null) {
                    $client_data_json[$k] = $val;
                }
            }
        }
        
        // Also include location-specific extras not in schema
        if (!empty($extra) && is_array($extra)) {
            foreach ($extra as $k => $v) {
                if (!isset($client_data_json[$k]) && $v !== '' && $v !== null) {
                    $client_data_json[$k] = $v;
                }
            }
        }
        
        // Store the complete client_data as JSON
        $appointment_data['client_data'] = wp_json_encode($client_data_json);

        $appointment_id = TS_Appointment_Database::insert_appointment($appointment_data);

        if (!$appointment_id) {
            return array('success' => false, 'message' => __('Erreur lors de la création du rendez-vous', 'ts-appointment'));
        }

        // Synchroniser avec Google Calendar si activé
        $appointment = TS_Appointment_Database::get_appointment($appointment_id);
        if (get_option('ts_appointment_google_calendar_enabled')) {
            $google = new TS_Appointment_Google_Calendar();
            // Crée l'événement même si le rendez-vous est en attente afin de bloquer le créneau
            ts_appointment_log('TS Appointment: Attempting to create Google Calendar event for appointment ' . $appointment_id, 'debug');
            $google_event_id = $google->create_event($appointment, false);
            
            if ($google_event_id) {
                TS_Appointment_Database::update_appointment($appointment_id, array('google_calendar_id' => $google_event_id));
                ts_appointment_log('TS Appointment: Google event ID stored: ' . $google_event_id, 'debug');
            } else {
                ts_appointment_log('TS Appointment: Failed to create Google Calendar event for appointment ' . $appointment_id, 'error');
            }
        }

        // Envoyer les emails - booking notification (pending), pas confirmation
        TS_Appointment_Email::send_booking_notification($appointment);
        TS_Appointment_Email::send_admin_notification($appointment);

        // Hook après création
        do_action('ts_appointment_after_book', $appointment_id, $appointment);

        return array(
            'success' => true,
            'message' => __('Rendez-vous réservé avec succès!', 'ts-appointment'),
            'appointment_id' => $appointment_id,
        );
    }

    public static function validate_appointment($data) {
        // Vérifier Turnstile si activé
        if (get_option('ts_appointment_turnstile_enabled')) {
            $token = isset($data['turnstile_token']) ? sanitize_text_field($data['turnstile_token']) : '';
            if (empty($token)) {
                return array('valid' => false, 'message' => __('Merci de valider le contrôle anti-robot.', 'ts-appointment'));
            }

            $turnstile_result = self::verify_turnstile_token($token);
            if (!$turnstile_result['success']) {
                $msg = !empty($turnstile_result['message']) ? $turnstile_result['message'] : __('La vérification anti-robot a échoué. Merci de réessayer.', 'ts-appointment');
                return array('valid' => false, 'message' => $msg);
            }
        }

        // Vérifier les champs obligatoires minimaux (service, lieu, date, heure)
        $required = array('service_id', 'appointment_type', 'appointment_date', 'appointment_time');
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return array('valid' => false, 'message' => sprintf(__('Le champ %s est obligatoire', 'ts-appointment'), $field));
            }
        }

        // Vérifier l'email en utilisant la clé du formulaire (définie dans form_schema)
        $form_schema = json_decode(get_option('ts_appointment_form_schema'), true);
        $email_key = 'client_email';
        if (is_array($form_schema)) {
            foreach ($form_schema as $f) {
                $fk = isset($f['key']) ? $f['key'] : '';
                $ft = isset($f['type']) ? $f['type'] : '';
                if ($ft === 'email') { $email_key = $fk; break; }
            }
        }
        $email_val = isset($data[$email_key]) ? $data[$email_key] : (isset($data['extra'][$email_key]) ? $data['extra'][$email_key] : '');
        if (!is_email($email_val)) {
            return array('valid' => false, 'message' => __('Email invalide', 'ts-appointment'));
        }

        // Vérifier la date + heure dans le fuseau configuré
        $tz = new DateTimeZone(get_option('ts_appointment_timezone', 'UTC'));
        $dt = DateTime::createFromFormat('Y-m-d H:i', $data['appointment_date'] . ' ' . $data['appointment_time'], $tz);
        if (!$dt) {
            return array('valid' => false, 'message' => __('Date invalide', 'ts-appointment'));
        }
        $now = new DateTime('now', $tz);
        if ($dt <= $now) {
            return array('valid' => false, 'message' => __('Date invalide', 'ts-appointment'));
        }

        // Vérifier le lieu (type) selon la configuration des lieux
        $locs = json_decode(get_option('ts_appointment_locations_config'), true);
        $loc_keys = is_array($locs) ? array_map(function($l){ return isset($l['key']) ? $l['key'] : null; }, $locs) : array('on_site','remote','home');
        if (!in_array($data['appointment_type'], $loc_keys, true)) {
            return array('valid' => false, 'message' => __('Lieu de rendez-vous invalide', 'ts-appointment')); 
        }

        // Conditional required fields per-location handled via form schema; client address requirement removed
        $selected = null;
        if (is_array($locs)) {
            foreach ($locs as $l) { if (isset($l['key']) && $l['key'] === $data['appointment_type']) { $selected = $l; break; } }
        }
        if ($selected && !empty($selected['fields']) && is_array($selected['fields'])) {
            $extra = isset($data['extra']) && is_array($data['extra']) ? $data['extra'] : array();
            foreach ($selected['fields'] as $f) {
                if (!empty($f['required'])) {
                    $k = isset($f['key']) ? $f['key'] : '';
                    if ($k) {
                        // Consider value present if found in extra or as top-level param
                        $val = null;
                        if (isset($extra[$k])) $val = $extra[$k];
                        elseif (isset($data[$k])) $val = $data[$k];

                        $filled = false;
                        if (is_array($val)) {
                            // array considered filled if any non-empty element
                            foreach ($val as $vv) { if ($vv !== '' && $vv !== null) { $filled = true; break; } }
                        } else {
                            $filled = !(is_null($val) || (is_string($val) && trim($val) === ''));
                        }

                        if (!$filled) {
                            return array('valid' => false, 'message' => sprintf(__('Le champ %s est obligatoire', 'ts-appointment'), $f['label'] ?? $k));
                        }
                    }
                }
            }
        }

        // Validate required fields from the form schema (uses DB-driven schema, not hardcoded keys)
        $form_schema = json_decode(get_option('ts_appointment_form_schema'), true);
        $extra = isset($data['extra']) && is_array($data['extra']) ? $data['extra'] : array();
        if (is_array($form_schema)) {
            foreach ($form_schema as $f) {
                if (empty($f['required'])) continue;
                $k = isset($f['key']) ? $f['key'] : '';
                if (empty($k)) continue;

                // If field has conditional visibility by location, and current appointment_type
                // is not listed, skip required validation for this field.
                $visible_locations = isset($f['visible_locations']) && is_array($f['visible_locations']) ? $f['visible_locations'] : array();
                if (!empty($visible_locations) && !in_array($data['appointment_type'], $visible_locations, true)) {
                    continue;
                }

                // Determine value: prefer extra[...] then top-level field
                $val = null;
                if (isset($extra[$k])) $val = $extra[$k];
                elseif (isset($data[$k])) $val = $data[$k];

                $filled = false;
                if (is_array($val)) {
                    foreach ($val as $vv) { if ($vv !== '' && $vv !== null) { $filled = true; break; } }
                } else {
                    $filled = !(is_null($val) || (is_string($val) && trim($val) === ''));
                }

                if (!$filled) {
                    return array('valid' => false, 'message' => sprintf(__('Le champ %s est obligatoire', 'ts-appointment'), $f['label'] ?? $k));
                }
            }
        }

        // Vérifier la disponibilité
        $available_slots = TS_Appointment_Database::get_available_slots($data['service_id'], $data['appointment_date']);
        if (!in_array($data['appointment_time'], $available_slots)) {
            return array('valid' => false, 'message' => __('Créneau non disponible', 'ts-appointment'));
        }

        return array('valid' => true);
    }

    public static function confirm_appointment($appointment_id) {
        $appointment = TS_Appointment_Database::get_appointment($appointment_id);
        
        if (!$appointment) {
            return false;
        }

        do_action('ts_appointment_before_confirm', $appointment_id);

        $ok = TS_Appointment_Database::update_appointment($appointment_id, array('status' => 'confirmed'));
        if ($ok === false) {
            ts_appointment_log('TS Appointment: failed to update appointment status for ID ' . $appointment_id, 'error');
            return false;
        }
        // Met à jour l'objet pour que Google utilise le bon statut
        $appointment->status = 'confirmed';
        
        // Synchroniser avec Google Calendar
        if (get_option('ts_appointment_google_calendar_enabled') && $appointment->google_calendar_id) {
            try {
                $google = new TS_Appointment_Google_Calendar();
                $google->update_event($appointment);
            } catch (Exception $e) {
                ts_appointment_log('TS Appointment: Google update_event failed for appointment ' . $appointment_id . ' - ' . $e->getMessage(), 'error');
            }
        } elseif (get_option('ts_appointment_google_calendar_enabled')) {
            // Si aucun événement n'existait (échec initial), on en crée un maintenant pour bloquer le créneau
            try {
                $google = new TS_Appointment_Google_Calendar();
                $google_event_id = $google->create_event($appointment, true);
                if ($google_event_id) {
                    TS_Appointment_Database::update_appointment($appointment_id, array('google_calendar_id' => $google_event_id));
                }
            } catch (Exception $e) {
                ts_appointment_log('TS Appointment: Google create_event on confirm failed for appointment ' . $appointment_id . ' - ' . $e->getMessage(), 'error');
            }
        }

        try {
            TS_Appointment_Email::send_confirmation($appointment);
        } catch (Exception $e) {
            ts_appointment_log('TS Appointment: send_confirmation failed for appointment ' . $appointment_id . ' - ' . $e->getMessage(), 'error');
        }

        do_action('ts_appointment_after_confirm', $appointment_id);

        return true;
    }

    public static function cancel_appointment($appointment_id, $reason = '') {
        $appointment = TS_Appointment_Database::get_appointment($appointment_id);
        
        if (!$appointment) {
            return false;
        }

        do_action('ts_appointment_before_cancel', $appointment_id);

        // Si déjà annulé, on supprime définitivement l'entrée (demande admin)
        if ($appointment->status === 'cancelled') {
            if (get_option('ts_appointment_google_calendar_enabled')) {
                $google = new TS_Appointment_Google_Calendar();
                $google->delete_event($appointment);
            }
            TS_Appointment_Database::delete_appointment($appointment_id);
            do_action('ts_appointment_after_cancel', $appointment_id);
            return true;
        }

        TS_Appointment_Database::update_appointment($appointment_id, array('status' => 'cancelled'));
        
        // Supprimer de Google Calendar
        if (get_option('ts_appointment_google_calendar_enabled')) {
            $google = new TS_Appointment_Google_Calendar();
            $google->delete_event($appointment);
        }

        TS_Appointment_Email::send_cancellation($appointment, $reason);

        do_action('ts_appointment_after_cancel', $appointment_id);

        return true;
    }

    public static function complete_appointment($appointment_id) {
        $appointment = TS_Appointment_Database::get_appointment($appointment_id);
        
        if (!$appointment) {
            return false;
        }

        return TS_Appointment_Database::update_appointment($appointment_id, array('status' => 'completed'));
    }

    private static function verify_turnstile_token($token) {
        $secret = get_option('ts_appointment_turnstile_secret_key');
        if (empty($secret)) {
            return array('success' => false, 'message' => __('Vérification anti-robot indisponible. Contactez l’administrateur.', 'ts-appointment'));
        }

        $remote_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

        $response = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', array(
            'body' => array(
                'secret'   => $secret,
                'response' => $token,
                'remoteip' => $remote_ip,
            ),
            'timeout' => 10,
        ));

        if (is_wp_error($response)) {
            ts_appointment_log('TS Appointment: Turnstile verification error - ' . $response->get_error_message(), 'error');
            return array('success' => false, 'message' => __('Échec de la vérification anti-robot.', 'ts-appointment'));
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if ($code !== 200 || !is_array($body)) {
            ts_appointment_log('TS Appointment: Turnstile unexpected response code=' . $code, 'warning');
            return array('success' => false, 'message' => __('Échec de la vérification anti-robot.', 'ts-appointment'));
        }

        if (!empty($body['success'])) {
            return array('success' => true);
        }

        if (!empty($body['error-codes']) && is_array($body['error-codes'])) {
            ts_appointment_log('TS Appointment: Turnstile errors - ' . implode(',', $body['error-codes']), 'warning');
        }

        return array('success' => false, 'message' => __('Vérification anti-robot invalide ou expirée.', 'ts-appointment'));
    }
}
