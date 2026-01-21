<?php
/**
 * Gestion de la base de données
 */

if (!defined('ABSPATH')) {
    exit;
}

class TS_Appointment_Database {
    
    public static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Table des services
        $services_table = $wpdb->prefix . 'ts_appointment_services';
        $sql[] = "CREATE TABLE IF NOT EXISTS $services_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description longtext,
            duration int(11) NOT NULL DEFAULT 60,
            price varchar(500) DEFAULT '0',
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Table des créneaux disponibles
        $slots_table = $wpdb->prefix . 'ts_appointment_slots';
        $sql[] = "CREATE TABLE IF NOT EXISTS $slots_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            service_id bigint(20) NOT NULL,
            day_of_week int(1) NOT NULL,
            start_time time NOT NULL,
            end_time time NOT NULL,
            max_appointments int(11) DEFAULT 1,
            slot_interval int(11) DEFAULT 60,
            active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (service_id) REFERENCES $services_table (id) ON DELETE CASCADE
        ) $charset_collate;";

        // Table des rendez-vous
        $appointments_table = $wpdb->prefix . 'ts_appointment_appointments';
        $sql[] = "CREATE TABLE IF NOT EXISTS $appointments_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            service_id bigint(20) NOT NULL,
            client_name varchar(255) NOT NULL,
            client_email varchar(255) NOT NULL,
            client_phone varchar(20) NOT NULL,
            appointment_type varchar(100) NOT NULL,
            appointment_date date NOT NULL,
            appointment_time time NOT NULL,
            client_address longtext,
            notes longtext,
            status enum('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
            google_calendar_id varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (service_id) REFERENCES $services_table (id) ON DELETE CASCADE,
            INDEX idx_date (appointment_date),
            INDEX idx_status (status),
            INDEX idx_email (client_email)
        ) $charset_collate;";

        // Table des paramètres
        $settings_table = $wpdb->prefix . 'ts_appointment_settings';
        $sql[] = "CREATE TABLE IF NOT EXISTS $settings_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            option_name varchar(255) NOT NULL UNIQUE,
            option_value longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_option_name (option_name)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        foreach ($sql as $statement) {
            dbDelta($statement);
        }

        // Migration: s'assurer que appointment_type est bien en VARCHAR(100)
        $column = $wpdb->get_var($wpdb->prepare(
            "SELECT DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'appointment_type'",
            DB_NAME,
            $appointments_table
        ));
        if ($column && strtolower($column) !== 'varchar') {
            $wpdb->query("ALTER TABLE $appointments_table MODIFY appointment_type varchar(100) NOT NULL");
        }

        // Initialiser les paramètres par défaut
        self::init_default_settings();
    }

    public static function maybe_create_tables() {
        global $wpdb;
        $services_table = $wpdb->prefix . 'ts_appointment_services';
        $slots_table = $wpdb->prefix . 'ts_appointment_slots';
        $appointments_table = $wpdb->prefix . 'ts_appointment_appointments';
        $settings_table = $wpdb->prefix . 'ts_appointment_settings';

        $tables = array($services_table, $slots_table, $appointments_table, $settings_table);
        $missing = false;

        foreach ($tables as $table_name) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $wpdb->esc_like($table_name)
            ));
            if ($exists !== $table_name) {
                $missing = true;
                break;
            }
        }

        if ($missing) {
            self::create_tables();
        }

        // Si les tables existent mais que la colonne slot_interval manque, l'ajouter
        global $wpdb;
        $slots_table = $wpdb->prefix . 'ts_appointment_slots';
        $col = $wpdb->get_var($wpdb->prepare(
            "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'slot_interval'",
            DB_NAME,
            $slots_table
        ));
        if (!$col) {
            $wpdb->query("ALTER TABLE $slots_table ADD COLUMN slot_interval int(11) DEFAULT 60 AFTER max_appointments");
        }

        // Migration: convertir les créneaux existants en créneaux globaux (service_id = 0)
        // puis supprimer les doublons éventuels (garder l'ID le plus petit)
        $wpdb->query("UPDATE $slots_table SET service_id = 0 WHERE service_id != 0");
        $wpdb->query(
            "DELETE t1 FROM $slots_table t1
             INNER JOIN $slots_table t2
             WHERE t1.id > t2.id
             AND t1.service_id = t2.service_id
             AND t1.day_of_week = t2.day_of_week
             AND t1.start_time = t2.start_time
             AND t1.end_time = t2.end_time
             AND IFNULL(t1.slot_interval, 60) = IFNULL(t2.slot_interval, 60)"
        );

        // Remove any foreign key on slots.service_id so we can use service_id = 0 for global slots
        // This avoids "Cannot add or update a child row" when service_id = 0 (no service with id 0)
        $fk_name = $wpdb->get_var($wpdb->prepare(
            "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'service_id' AND REFERENCED_TABLE_NAME = %s",
            DB_NAME,
            $slots_table,
            $services_table
        ));
        if ($fk_name) {
            // Drop foreign key
            $wpdb->query("ALTER TABLE $slots_table DROP FOREIGN KEY " . esc_sql($fk_name));
            // Drop any index on the column created by the FK
            $index_name = $wpdb->get_var($wpdb->prepare(
                "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'service_id'",
                DB_NAME,
                $slots_table
            ));
            if ($index_name) {
                $wpdb->query("ALTER TABLE $slots_table DROP INDEX " . esc_sql($index_name));
            }
        }
    }

    private static function init_default_settings() {
        $defaults = array(
            'business_name' => get_bloginfo('name'),
            'business_email' => get_bloginfo('admin_email'),
            'business_address' => '',
            'business_phone' => '',
            'timezone' => get_option('timezone_string', 'UTC'),
            //'appointment_buffer' => 15, // removed from settings UI
            'max_days_ahead' => 30,
            'date_format' => 'j/m/Y',
            'time_format' => 'H:i',
            'color_primary' => '#007cba',
            'color_secondary' => '#f0f0f0',
            'currency_symbol' => '€',
            'currency_position' => 'right',
            'enable_reminders' => 1,
            'reminder_hours' => 24,
            'google_calendar_enabled' => 0,
            'google_calendar_id' => '',
            'google_send_updates' => 'none',
            'google_email_reminders' => 0,
            'turnstile_enabled' => 0,
            'turnstile_site_key' => '',
            'turnstile_secret_key' => '',
        );

        foreach ($defaults as $option => $value) {
            $db_option = 'ts_appointment_' . $option;
            if (false === get_option($db_option)) {
                add_option($db_option, $value);
            }
        }

        // Default email templates
        if (false === get_option('ts_appointment_email_templates')) {
            $default_templates = array(
                'client_new' => array(
                    'subject' => __('Réservation reçue - {business_name}', 'ts-appointment'),
                    'body' => "<p>Bonjour {client_name},</p><p>Nous avons bien reçu votre demande pour <strong>{service_name}</strong> le {appointment_date} à {appointment_time}.</p><p>Nous vous confirmerons bientôt.</p>",
                ),
                'client_confirmation' => array(
                    'subject' => __('Confirmation de rendez-vous - {business_name}', 'ts-appointment'),
                    'body' => "<p>Bonjour {client_name},</p><p>Votre rendez-vous pour <strong>{service_name}</strong> est confirmé pour le {appointment_date} à {appointment_time}.</p>",
                ),
                'admin_new' => array(
                    'subject' => __('Nouveau rendez-vous - {client_name}', 'ts-appointment'),
                    'body' => "<p>Nouveau rendez-vous de {client_name} pour {service_name} le {appointment_date} à {appointment_time}.</p>",
                ),
                'client_cancellation' => array(
                    'subject' => __('Annulation de rendez-vous - {business_name}', 'ts-appointment'),
                    'body' => "<p>Bonjour {client_name},</p><p>Votre rendez-vous du {appointment_date} à {appointment_time} a été annulé.</p>",
                ),
            );
            add_option('ts_appointment_email_templates', wp_json_encode($default_templates));
        }

        // Par défaut: configuration des lieux et formulaire de base
        if (false === get_option('ts_appointment_locations_config')) {
            $default_locations = array(
                array(
                    'key' => 'home',
                    'label' => __('Au domicile du client', 'ts-appointment'),
                            'icon' => '📍',
                    'showBusinessAddress' => false,
                    'requireClientAddress' => true,
                    'fields' => array(
                        array('key' => 'client_address', 'label' => __('Adresse du client', 'ts-appointment'), 'type' => 'textarea', 'required' => true, 'placeholder' => __('Adresse complète', 'ts-appointment'))
                    )
                ),
                array(
                    'key' => 'remote',
                    'label' => __('À distance', 'ts-appointment'),
                            'icon' => '📍',
                    'showBusinessAddress' => false,
                    'requireClientAddress' => false,
                    'fields' => array()
                ),
                array(
                    'key' => 'on_site',
                    'label' => __('Venir à notre adresse', 'ts-appointment'),
                            'icon' => '📍',
                    'showBusinessAddress' => true,
                    'requireClientAddress' => false,
                    'fields' => array()
                )
            );
            add_option('ts_appointment_locations_config', wp_json_encode($default_locations));
        }

        if (false === get_option('ts_appointment_form_schema')) {
            $default_form = array(
                array('key' => 'client_name', 'label' => __('Nom complet', 'ts-appointment'), 'type' => 'text', 'required' => true),
                array('key' => 'client_email', 'label' => __('Email', 'ts-appointment'), 'type' => 'email', 'required' => true),
                array('key' => 'client_phone', 'label' => __('Téléphone', 'ts-appointment'), 'type' => 'tel', 'required' => true),
                array('key' => 'notes', 'label' => __('Notes supplémentaires', 'ts-appointment'), 'type' => 'textarea', 'required' => false)
            );
            add_option('ts_appointment_form_schema', wp_json_encode($default_form));
        }
    }

    public static function maybe_init_defaults() {
        // Re-applique les valeurs par défaut si les options manquent ou sont vides/invalides
        self::init_default_settings();

        $locations_raw = get_option('ts_appointment_locations_config');
        $locations = json_decode($locations_raw, true);
        if (empty($locations) || !is_array($locations)) {
            $default_locations = array(
                array(
                    'key' => 'home',
                    'label' => __('Au domicile du client', 'ts-appointment'),
                    'icon' => '📍',
                    'showBusinessAddress' => false,
                    'requireClientAddress' => true,
                    'fields' => array(
                        array('key' => 'client_address', 'label' => __('Adresse du client', 'ts-appointment'), 'type' => 'textarea', 'required' => true, 'placeholder' => __('Adresse complète', 'ts-appointment'))
                    )
                ),
                array(
                    'key' => 'remote',
                    'label' => __('À distance', 'ts-appointment'),
                    'icon' => '📍',
                    'showBusinessAddress' => false,
                    'requireClientAddress' => false,
                    'fields' => array()
                ),
                array(
                    'key' => 'on_site',
                    'label' => __('Venir à notre adresse', 'ts-appointment'),
                    'icon' => '📍',
                    'showBusinessAddress' => true,
                    'requireClientAddress' => false,
                    'fields' => array()
                )
            );
            update_option('ts_appointment_locations_config', wp_json_encode($default_locations));
        }

        $form_raw = get_option('ts_appointment_form_schema');
        $form = json_decode($form_raw, true);
        if (empty($form) || !is_array($form)) {
            $default_form = array(
                array('key' => 'client_name', 'label' => __('Nom complet', 'ts-appointment'), 'type' => 'text', 'required' => true),
                array('key' => 'client_email', 'label' => __('Email', 'ts-appointment'), 'type' => 'email', 'required' => true),
                array('key' => 'client_phone', 'label' => __('Téléphone', 'ts-appointment'), 'type' => 'tel', 'required' => true),
                array('key' => 'notes', 'label' => __('Notes supplémentaires', 'ts-appointment'), 'type' => 'textarea', 'required' => false)
            );
            update_option('ts_appointment_form_schema', wp_json_encode($default_form));
        }
    }

    public static function insert_service($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'ts_appointment_services';

        if (!isset($data['active'])) {
            $data['active'] = 1; // Default to active
        }
        
        $wpdb->insert($table, $data);
        return $wpdb->insert_id;
    }

    public static function update_service($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'ts_appointment_services';
        return $wpdb->update($table, $data, array('id' => intval($id)));
    }

    public static function get_services($active_only = true) {
        global $wpdb;
        $table = $wpdb->prefix . 'ts_appointment_services';

        if ($active_only) {
            return $wpdb->get_results("SELECT * FROM $table WHERE active = 1 ORDER BY name ASC");
        }

        return $wpdb->get_results("SELECT * FROM $table ORDER BY name ASC");
    }

    public static function get_service($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'ts_appointment_services';
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    public static function get_slots($service_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'ts_appointment_slots';
        if ($service_id) {
            return $wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE service_id = %d ORDER BY day_of_week, start_time", $service_id));
        }
        return $wpdb->get_results("SELECT * FROM $table ORDER BY service_id, day_of_week, start_time");
    }

    public static function insert_slot($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'ts_appointment_slots';
        $defaults = array(
            'max_appointments' => 1,
            'slot_interval' => 60,
            'active' => 1,
        );
        $data = array_merge($defaults, $data);
        $wpdb->insert($table, $data);
        return $wpdb->insert_id;
    }

    public static function get_slot($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'ts_appointment_slots';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    public static function update_slot($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'ts_appointment_slots';
        return $wpdb->update($table, $data, array('id' => intval($id)));
    }

    public static function delete_slot($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'ts_appointment_slots';
        return $wpdb->delete($table, array('id' => $id));
    }

    public static function delete_slots_for_service_day($service_id, $day_of_week) {
        global $wpdb;
        $table = $wpdb->prefix . 'ts_appointment_slots';
        // Slots are now global; delete by day_of_week
        return $wpdb->delete($table, array('day_of_week' => $day_of_week));
    }

    public static function insert_appointment($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'ts_appointment_appointments';
        
        $wpdb->insert($table, $data);
        return $wpdb->insert_id;
    }

    public static function get_appointments($status = null, $limit = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'ts_appointment_appointments';
        
        $query = "SELECT * FROM $table";
        
        if ($status) {
            $query .= $wpdb->prepare(" WHERE status = %s", $status);
        }
        
        $query .= " ORDER BY appointment_date DESC";
        
        if ($limit) {
            $query .= " LIMIT " . intval($limit);
        }
        
        return $wpdb->get_results($query);
    }

    public static function get_appointment($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'ts_appointment_appointments';
        
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));
    }

    public static function update_appointment($id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'ts_appointment_appointments';
        
        return $wpdb->update($table, $data, array('id' => $id));
    }

    public static function delete_appointment($id) {
        global $wpdb;
        $table = $wpdb->prefix . 'ts_appointment_appointments';
        
        return $wpdb->delete($table, array('id' => $id));
    }

    public static function delete_service($id) {
        global $wpdb;
        $services_table = $wpdb->prefix . 'ts_appointment_services';
        return $wpdb->delete($services_table, array('id' => $id));
    }

    public static function duplicate_service($id) {
        global $wpdb;
        $services_table = $wpdb->prefix . 'ts_appointment_services';
        $svc = $wpdb->get_row($wpdb->prepare("SELECT * FROM $services_table WHERE id = %d", $id));
        if (!$svc) return 0;
        $data = array(
            'name' => $svc->name . ' (copie)',
            'description' => $svc->description,
            'duration' => intval($svc->duration),
            'price' => $svc->price,
            'active' => intval($svc->active),
        );
        $wpdb->insert($services_table, $data);
        return $wpdb->insert_id;
    }

    public static function get_available_slots($service_id, $date) {
        global $wpdb;
        $slots_table = $wpdb->prefix . 'ts_appointment_slots';
        $appointments_table = $wpdb->prefix . 'ts_appointment_appointments';
        
        $day_of_week = date('w', strtotime($date));
        
        $slots = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $slots_table 
            WHERE (service_id = 0 OR service_id = %d) AND day_of_week = %d AND active = 1",
            $service_id,
            $day_of_week
        ));

        // Récupérer le service une seule fois et sécuriser la durée
        $service = self::get_service($service_id);
        if (!$service) {
            return array();
        }
        $slot_duration = max(5, intval($service->duration)) * 60;

        $available = array();
        $appointments = $wpdb->get_col($wpdb->prepare(
            "SELECT appointment_time FROM $appointments_table 
            WHERE service_id = %d AND appointment_date = %s AND status != 'cancelled'",
            $service_id,
            $date
        ));

        // Vérifier les conflits Google Calendar si la synchro est activée
        $google_conflicts = array();
        $google_enabled = get_option('ts_appointment_google_calendar_enabled');
        $google_access_token = get_option('ts_appointment_google_access_token');
        $google_calendar_id = get_option('ts_appointment_google_calendar_id');
        
        error_log('TS Appointment get_available_slots: google_enabled=' . $google_enabled . ', has_token=' . (!empty($google_access_token) ? 'yes' : 'no') . ', has_calendar_id=' . (!empty($google_calendar_id) ? 'yes' : 'no'));
        
        if ($google_enabled && !empty($google_access_token) && !empty($google_calendar_id)) {
            $google = new TS_Appointment_Google_Calendar();
            $google_conflicts = $google->get_events_for_date($date);
            error_log('TS Appointment get_available_slots: Google conflicts found = ' . count($google_conflicts));
            foreach ($google_conflicts as $i => $event) {
                error_log('TS Appointment conflict ' . $i . ': ' . date('Y-m-d H:i:s', $event['start_ts']) . ' to ' . date('Y-m-d H:i:s', $event['end_ts']));
            }
        }

        $tz = new DateTimeZone(get_option('ts_appointment_timezone', 'UTC'));

        foreach ($slots as $slot) {
            $current_time = strtotime($slot->start_time);
            $end_time = strtotime($slot->end_time);
            
            // Intervalle d'affichage des créneaux (en minutes), par défaut 60
            $slot_interval = max(5, intval($slot->slot_interval ?? 60)) * 60;

            while ($current_time < $end_time) {
                $time_str = date('H:i', $current_time);
                
                // Vérifier les conflits internes (rendez-vous déjà pris)
                $has_internal_conflict = in_array($time_str, $appointments, true);

                // Vérifier les conflits Google Calendar
                $start_dt = new DateTime($date . ' ' . $time_str, $tz);
                $start_ts = $start_dt->getTimestamp();
                $end_ts_candidate = $start_ts + $slot_duration;
                $has_google_conflict = false;
                if (!empty($google_conflicts)) {
                    foreach ($google_conflicts as $event) {
                        if ($start_ts < $event['end_ts'] && $end_ts_candidate > $event['start_ts']) {
                            $has_google_conflict = true;
                            error_log('TS Appointment: Time ' . $time_str . ' conflicts with Google event');
                            break;
                        }
                    }
                }

                if (!$has_internal_conflict && !$has_google_conflict) {
                    $available[] = $time_str;
                } else {
                    error_log('TS Appointment: Time ' . $time_str . ' excluded - internal=' . ($has_internal_conflict ? 'yes' : 'no') . ', google=' . ($has_google_conflict ? 'yes' : 'no'));
                }
                
                $current_time += $slot_interval;
            }
        }

        return $available;
    }
}
