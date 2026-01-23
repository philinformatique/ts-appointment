<?php
/**
 * Interface d'administration
 */

if (!defined('ABSPATH')) {
    exit;
}

class TS_Appointment_Admin {
    
    public static function display_dashboard() {
        $appointments = TS_Appointment_Database::get_appointments(null, 10);
        $services = TS_Appointment_Database::get_services();
        $pending_count = count(TS_Appointment_Database::get_appointments('pending'));
        $confirmed_count = count(TS_Appointment_Database::get_appointments('confirmed'));
        $completed_count = count(TS_Appointment_Database::get_appointments('completed'));

        include TS_APPOINTMENT_DIR . 'templates/admin-dashboard.php';
    }

    public static function display_appointments() {
        $appointments = TS_Appointment_Database::get_appointments();

        include TS_APPOINTMENT_DIR . 'templates/admin-appointments.php';
    }

    public static function display_settings() {
        // Gestion des actions OAuth Google
        if (isset($_GET['action'])) {
            $action = sanitize_text_field($_GET['action']);
            if ($action === 'google_auth') {
                $google = new TS_Appointment_Google_Calendar();
                $url = $google->get_auth_url();
                wp_redirect($url);
                exit;
            } elseif ($action === 'google_callback') {
                if (isset($_GET['code'])) {
                    $code = sanitize_text_field($_GET['code']);
                    ts_appointment_log('TS Appointment: Google callback received with code=' . substr($code, 0, 20) . '...', 'debug');
                    $google = new TS_Appointment_Google_Calendar();
                    $ok = $google->get_access_token($code);
                    if ($ok) {
                        ts_appointment_log('TS Appointment: Google authentication successful', 'debug');
                        echo '<div class="notice notice-success"><p>' . __('Compte Google lié avec succès.', 'ts-appointment') . '</p></div>';
                    } else {
                        ts_appointment_log('TS Appointment: Google authentication failed', 'error');
                        echo '<div class="notice notice-error"><p>' . __('Échec de la liaison du compte Google. Vérifiez vos paramètres Client ID/Secret et réessayez.', 'ts-appointment') . '</p></div>';
                    }
                } else {
                    ts_appointment_log('TS Appointment: Google callback received but no code in GET params', 'warning');
                    if (isset($_GET['error'])) {
                        ts_appointment_log('TS Appointment: Google error: ' . sanitize_text_field($_GET['error']), 'error');
                    }
                    echo '<div class="notice notice-error"><p>' . __('Code d’autorisation manquant.', 'ts-appointment') . '</p></div>';
                }
            } elseif ($action === 'google_disconnect') {
                delete_option('ts_appointment_google_access_token');
                delete_option('ts_appointment_google_refresh_token');
                echo '<div class="notice notice-success"><p>' . __('Compte Google déconnecté.', 'ts-appointment') . '</p></div>';
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ts_appointment_nonce'])) {
            if (wp_verify_nonce($_POST['ts_appointment_nonce'], 'ts_appointment_settings')) {
                self::save_settings();

                // If Mailgun test button pressed, attempt to send test email
                if (!empty($_POST['mailgun_send_test'])) {
                    $test_to = sanitize_email($_POST['mailgun_test_to'] ?? get_option('admin_email'));
                    $subject = __('Test email Mailgun', 'ts-appointment');
                    $body = __('Ceci est un email de test envoyé via l\'API Mailgun.', 'ts-appointment');
                    $headers = array('Content-Type: text/html; charset=UTF-8');
                    $sent = false;
                    if (class_exists('TS_Appointment_Email')) {
                        $sent = TS_Appointment_Email::send_via_mailgun($test_to, $subject, $body, $headers, array());
                    }
                    if ($sent) {
                        echo '<div class="notice notice-success"><p>' . __('Email de test envoyé avec succès via Mailgun.', 'ts-appointment') . '</p></div>';
                    } else {
                        echo '<div class="notice notice-error"><p>' . __('Echec de l\'envoi via Mailgun. Vérifiez la configuration et consultez les logs.', 'ts-appointment') . '</p></div>';
                    }
                }

                // If admin requested to force GitHub check, clear cached transient and update_plugins transient
                if (!empty($_POST['force_github_check'])) {
                    delete_transient('ts_appointment_github_release');
                    // remove update_plugins site transient so WP will re-check
                    if (function_exists('delete_site_transient')) {
                        delete_site_transient('update_plugins');
                    } else {
                        // fallback
                        delete_transient('update_plugins');
                    }
                    echo '<div class="notice notice-success"><p>' . __('Vérification GitHub forcée — cache vidé. Allez sur Mises à jour pour actualiser.', 'ts-appointment') . '</p></div>';
                }

                // If admin requested to perform a git pull (useful for local debug environments)
                if (!empty($_POST['git_pull'])) {
                    if (!current_user_can('manage_options')) {
                        echo '<div class="notice notice-error"><p>' . __('Permission refusée.', 'ts-appointment') . '</p></div>';
                    } else {
                        $git_dir = rtrim(TS_APPOINTMENT_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.git';
                        if (!is_dir($git_dir) && !file_exists($git_dir)) {
                            echo '<div class="notice notice-error"><p>' . __('Impossible d\'exécuter git pull : ce dossier ne semble pas être un dépôt Git.', 'ts-appointment') . '</p></div>';
                        } else {
                            $real = realpath(TS_APPOINTMENT_DIR);
                            // Use fetch + reset --hard to align working tree with remote for debugging
                            $output = '';
                            $status = null;
                            $real_esc = escapeshellarg($real);
                            $fetch_cmd = 'git -C ' . $real_esc . ' fetch --all --prune 2>&1';
                            $upstream_cmd = 'git -C ' . $real_esc . ' rev-parse --abbrev-ref --symbolic-full-name @{u} 2>&1';
                            $branch_cmd = 'git -C ' . $real_esc . ' rev-parse --abbrev-ref HEAD 2>&1';

                            $cmd_output = array();

                            $run = function($c) use (&$cmd_output, &$status) {
                                if (function_exists('shell_exec')) {
                                    $res = shell_exec($c);
                                    $cmd_output[] = trim((string)$res);
                                } elseif (function_exists('exec')) {
                                    $out_arr = array();
                                    exec($c, $out_arr, $status);
                                    $cmd_output[] = trim(implode("\n", $out_arr));
                                } elseif (function_exists('passthru')) {
                                    ob_start();
                                    passthru($c, $status);
                                    $cmd_output[] = trim(ob_get_clean());
                                } else {
                                    $cmd_output[] = 'shell disabled';
                                }
                            };

                            // fetch
                            $run($fetch_cmd);
                            // try to get upstream ref
                            $upstream = null;
                            if (function_exists('shell_exec')) {
                                $upstream = trim((string)shell_exec($upstream_cmd));
                            } elseif (function_exists('exec')) {
                                $tmp = array();
                                @exec($upstream_cmd, $tmp, $status);
                                $upstream = trim(implode("\n", $tmp));
                            }
                            // fallback to current branch
                            if (empty($upstream) || strpos($upstream, 'fatal:') === 0) {
                                if (function_exists('shell_exec')) {
                                    $current = trim((string)shell_exec($branch_cmd));
                                } elseif (function_exists('exec')) {
                                    $tmp = array();
                                    @exec($branch_cmd, $tmp, $status);
                                    $current = trim(implode("\n", $tmp));
                                } else {
                                    $current = 'master';
                                }
                                $reset_target = 'origin/' . $current;
                            } else {
                                $reset_target = $upstream;
                            }

                            $reset_cmd = 'git -C ' . $real_esc . ' reset --hard ' . escapeshellarg($reset_target) . ' 2>&1';
                            $run($reset_cmd);

                            $output = implode("\n----\n", $cmd_output);

                            if ($output !== null) {
                                $out = trim((string)$output);
                                if (strlen($out) > 10000) {
                                    $out = substr($out, 0, 10000) . "\n... (truncated)";
                                }
                                echo '<div class="notice notice-info"><p>' . __('Sortie de git pull :', 'ts-appointment') . '</p><pre style="white-space:pre-wrap;">' . esc_html($out) . '</pre></div>';
                            }
                        }
                    }
                }

                echo '<div class="notice notice-success"><p>' . __('Paramètres enregistrés avec succès.', 'ts-appointment') . '</p></div>';
            }
        }

        include TS_APPOINTMENT_DIR . 'templates/admin-settings.php';
    }

    public static function display_locations() {
        TS_Appointment_Database::maybe_init_defaults();
        $locations = self::get_locations_config();
        $edit_loc = null;
        if (!empty($_GET['edit_loc_key'])) {
            $key = sanitize_key($_GET['edit_loc_key']);
            foreach ($locations as $loc) {
                if (isset($loc['key']) && $loc['key'] === $key) {
                    $edit_loc = $loc;
                    break;
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ts_appointment_nonce'])) {
            if (wp_verify_nonce($_POST['ts_appointment_nonce'], 'ts_appointment_locations')) {
                $action = sanitize_text_field($_POST['action_type'] ?? '');

                if ($action === 'add') {
                    $label = sanitize_text_field($_POST['loc_label'] ?? '');
                    $key   = sanitize_key($_POST['loc_key'] ?? '');
                    if (empty($key) && !empty($label)) {
                        $key = sanitize_key($label);
                    }
                    // options removed: loc_show_business, loc_require_client
                    $icon = isset($_POST['loc_icon']) ? sanitize_text_field($_POST['loc_icon']) : '📍';
                    $note = isset($_POST['loc_note']) ? wp_kses_post(wp_unslash($_POST['loc_note'])) : '';

                    if (empty($label)) {
                        echo '<div class="notice notice-error"><p>' . __('Le nom du lieu est obligatoire.', 'ts-appointment') . '</p></div>';
                    } elseif (empty($key)) {
                        echo '<div class="notice notice-error"><p>' . __('Le slug est obligatoire.', 'ts-appointment') . '</p></div>';
                    } elseif (self::location_exists($locations, $key)) {
                        echo '<div class="notice notice-error"><p>' . __('Ce slug existe déjà.', 'ts-appointment') . '</p></div>';
                    } else {
                        $locations[] = array(
                            'key' => $key,
                            'label' => $label,
                            'icon' => $icon,
                            'note' => $note,
                            'fields' => array(),
                        );
                        self::save_locations_config($locations);
                        echo '<div class="notice notice-success"><p>' . __('Lieu ajouté.', 'ts-appointment') . '</p></div>';
                    }
                }

                if ($action === 'edit' && !empty($_POST['loc_key'])) {
                    $edit_key = sanitize_key($_POST['loc_key']);
                    $label = sanitize_text_field($_POST['loc_label'] ?? '');
                    // options removed: loc_show_business, loc_require_client
                    $icon = isset($_POST['loc_icon']) ? sanitize_text_field($_POST['loc_icon']) : '📍';
                    $note = isset($_POST['loc_note']) ? wp_kses_post(wp_unslash($_POST['loc_note'])) : '';

                    foreach ($locations as $i => $loc) {
                        if (isset($loc['key']) && $loc['key'] === $edit_key) {
                            $locations[$i]['label'] = $label;
                            $locations[$i]['icon'] = $icon;
                            $locations[$i]['note'] = $note;
                            self::save_locations_config($locations);
                            echo '<div class="notice notice-success"><p>' . __('Lieu modifié.', 'ts-appointment') . '</p></div>';
                            break;
                        }
                    }
                }

                if ($action === 'delete' && !empty($_POST['loc_key'])) {
                    $del_key = sanitize_key($_POST['loc_key']);
                    $before = count($locations);
                    $locations = array_values(array_filter($locations, function($loc) use ($del_key) {
                        return isset($loc['key']) && $loc['key'] !== $del_key;
                    }));
                    if (count($locations) < $before) {
                        self::save_locations_config($locations);
                        echo '<div class="notice notice-success"><p>' . __('Lieu supprimé.', 'ts-appointment') . '</p></div>';
                    }
                }
            }
        }

        // Rafraîchir après modifications
        $locations = self::get_locations_config();
        include TS_APPOINTMENT_DIR . 'templates/admin-locations.php';
    }

    public static function display_logs() {
        if (!current_user_can('manage_options')) return;

        $log_file = TS_APPOINTMENT_DIR . 'debug.log';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ts_appointment_nonce']) && isset($_POST['clear_debug_log'])) {
            if (wp_verify_nonce($_POST['ts_appointment_nonce'], 'ts_appointment_settings')) {
                if (file_exists($log_file)) {
                    @unlink($log_file);
                }
                echo '<div class="notice notice-success"><p>' . __('Logs supprimés.', 'ts-appointment') . '</p></div>';
            }
        }

        $contents = '';
        if (file_exists($log_file)) {
            $raw = file_get_contents($log_file);
            // Limit size to avoid huge output
            if (strlen($raw) > 200000) {
                $raw = substr($raw, -200000);
                $raw = "... (truncated) ...\n" . $raw;
            }
            $contents = esc_html($raw);
        } else {
            $contents = esc_html(__('Aucun log disponible.', 'ts-appointment'));
        }

        echo '<div class="wrap"><h1>' . esc_html__('Logs TS Appointment', 'ts-appointment') . '</h1>';
        echo '<form method="post">';
        wp_nonce_field('ts_appointment_settings', 'ts_appointment_nonce');
        echo '<p><button class="button" type="submit" name="clear_debug_log" value="1">' . esc_html__('Supprimer les logs', 'ts-appointment') . '</button></p>';
        echo '<pre style="background:#fff;border:1px solid #ddd;padding:12px;white-space:pre-wrap;word-break:break-word;max-height:600px;overflow:auto">' . $contents . '</pre>';
        echo '</form></div>';
    }

    public static function display_form_builder() {
        TS_Appointment_Database::maybe_init_defaults();
        $form_fields = self::get_form_fields();
        $edit_field = null;
        if (!empty($_GET['edit_field_key'])) {
            $edit_key = sanitize_key($_GET['edit_field_key']);
            foreach ($form_fields as $f) {
                if (isset($f['key']) && $f['key'] === $edit_key) {
                    $edit_field = $f;
                    break;
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ts_appointment_nonce'])) {
            if (wp_verify_nonce($_POST['ts_appointment_nonce'], 'ts_appointment_form')) {
                $action = sanitize_text_field($_POST['action_type'] ?? '');

                if ($action === 'add' || $action === 'edit') {
                    $is_edit = ($action === 'edit');
                    $edit_key = $is_edit ? sanitize_key($_POST['field_key'] ?? '') : '';
                    $label = sanitize_text_field($_POST['field_label'] ?? '');
                    $key   = sanitize_key($_POST['field_key'] ?? '');
                    if (empty($key) && !empty($label)) {
                        $key = sanitize_key($label);
                    }
                    $type = sanitize_text_field($_POST['field_type'] ?? 'text');
                    $required = isset($_POST['field_required']) ? 1 : 0;
                    $options_raw = sanitize_text_field($_POST['field_options'] ?? '');
                    $options_arr = array();
                    if ($type === 'select' && !empty($options_raw)) {
                        $options_arr = array_filter(array_map('trim', explode(',', $options_raw)));
                    }

                    $allowed_types = array('text','email','tel','number','date','time','textarea','select','checkbox');
                    if (!in_array($type, $allowed_types, true)) {
                        $type = 'text';
                    }

                    if (empty($label)) {
                        echo '<div class="notice notice-error"><p>' . __('Le label est obligatoire.', 'ts-appointment') . '</p></div>';
                    } elseif (empty($key)) {
                        echo '<div class="notice notice-error"><p>' . __('Le slug est obligatoire.', 'ts-appointment') . '</p></div>';
                    } elseif (self::form_field_exists($form_fields, $key) && !$is_edit) {
                        echo '<div class="notice notice-error"><p>' . __('Ce slug existe déjà.', 'ts-appointment') . '</p></div>';
                    } else {
                        $new_field = array(
                            'key' => $key,
                            'label' => $label,
                            'type' => $type,
                            'required' => (bool) $required,
                            'options' => $type === 'select' ? $options_arr : array(),
                            'visible_locations' => array(),
                        );
                        // Capture visibility per location if provided (multiselect)
                        if (isset($_POST['field_visible_locations']) && is_array($_POST['field_visible_locations'])) {
                            $vis = array_map('sanitize_text_field', $_POST['field_visible_locations']);
                            $new_field['visible_locations'] = array_values($vis);
                        }
                        if ($is_edit) {
                            // Replace existing field
                            foreach ($form_fields as $i => $f) {
                                if (isset($f['key']) && $f['key'] === $edit_key) {
                                    $form_fields[$i] = $new_field;
                                    break;
                                }
                            }
                        } else {
                            $form_fields[] = $new_field;
                        }
                        self::save_form_fields($form_fields);
                        echo '<div class="notice notice-success"><p>' . ($is_edit ? __('Champ modifié.', 'ts-appointment') : __('Champ ajouté.', 'ts-appointment')) . '</p></div>';
                    }
                }

                if ($action === 'delete' && !empty($_POST['field_key'])) {
                    $del_key = sanitize_key($_POST['field_key']);
                    $before = count($form_fields);
                    $form_fields = array_values(array_filter($form_fields, function($f) use ($del_key) {
                        return isset($f['key']) && $f['key'] !== $del_key;
                    }));
                    if (count($form_fields) < $before) {
                        self::save_form_fields($form_fields);
                        echo '<div class="notice notice-success"><p>' . __('Champ supprimé.', 'ts-appointment') . '</p></div>';
                    }
                }
            }
        }

        // Rafraîchir après modifications
        $form_fields = self::get_form_fields();
        include TS_APPOINTMENT_DIR . 'templates/admin-form.php';
    }

    public static function display_slots() {
        // S'assurer que les tables existent pour éviter des insertions silencieuses
        TS_Appointment_Database::maybe_create_tables();

        $slots = TS_Appointment_Database::get_slots();
        // If editing a slot, provide it to the template
        $edit_slot = null;
        if (!empty($_GET['edit_slot_id'])) {
            $edit_id = intval($_GET['edit_slot_id']);
            $edit_slot = TS_Appointment_Database::get_slot($edit_id);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ts_appointment_nonce'])) {
            if (wp_verify_nonce($_POST['ts_appointment_nonce'], 'ts_appointment_slots')) {
                $action = sanitize_text_field($_POST['action_type'] ?? '');

                if ($action === 'add' || $action === 'edit') {
                    $is_edit = ($action === 'edit');
                    $edit_id = $is_edit ? intval($_POST['slot_id'] ?? 0) : 0;
                    // Slots are global and apply to all services; use service_id = 0
                    $service_id = 0;
                    $days = isset($_POST['slot_days']) && is_array($_POST['slot_days']) ? array_map('intval', $_POST['slot_days']) : array();
                    if ($is_edit && !empty($days)) {
                        // When editing, only consider the first selected day (slot is a single record)
                        $days = array($days[0]);
                    }
                    $duration = max(5, intval($_POST['slot_duration'] ?? 60));
                    $interval = max(5, intval($_POST['slot_interval'] ?? 60));
                    $start_time = sanitize_text_field($_POST['slot_start_time'] ?? '08:00');
                    $end_time = sanitize_text_field($_POST['slot_end_time'] ?? '20:00');
                    $active = isset($_POST['slot_active']) ? 1 : 0;

                    $time_pattern = '/^([01]\d|2[0-3]):[0-5]\d$/';
                    if (!preg_match($time_pattern, $start_time)) {
                        $start_time = '08:00';
                    }
                    if (!preg_match($time_pattern, $end_time)) {
                        $end_time = '20:00';
                    }

                    $start_ts = strtotime($start_time);
                    $end_ts = strtotime($end_time);

                    if (empty($days)) {
                        echo '<div class="notice notice-error"><p>' . __('Sélectionnez au moins un jour.', 'ts-appointment') . '</p></div>';
                    } elseif ($start_ts === false || $end_ts === false || $start_ts >= $end_ts) {
                        echo '<div class="notice notice-error"><p>' . __('L\'heure de début doit être antérieure à l\'heure de fin.', 'ts-appointment') . '</p></div>';
                    } else {
                        // Slots are global; do not update individual service durations here

                        $created = 0;
                        $errors = array();

                        foreach ($days as $day_of_week) {
                            // For add: remove existing slots for this day so we replace them.
                            if (!$is_edit) {
                                TS_Appointment_Database::delete_slots_for_service_day($service_id, $day_of_week);
                            }
                            if ($is_edit && $edit_id) {
                                $updated = TS_Appointment_Database::update_slot($edit_id, array(
                                    'day_of_week' => $day_of_week,
                                    'start_time' => $start_time,
                                    'end_time' => $end_time,
                                    'max_appointments' => 1,
                                    'slot_interval' => $interval,
                                    'active' => $active,
                                ));
                                $insert_id = $updated ? $edit_id : 0;
                            } else {
                                $insert_id = TS_Appointment_Database::insert_slot(array(
                                    'service_id' => 0,
                                    'day_of_week' => $day_of_week,
                                    'start_time' => $start_time,
                                    'end_time' => $end_time,
                                    'max_appointments' => 1,
                                    'slot_interval' => $interval,
                                    'active' => $active,
                                ));
                            }

                            if ($insert_id) {
                                $created++;
                            } else {
                                global $wpdb;
                                $msg = !empty($wpdb->last_error) ? $wpdb->last_error : sprintf(__('Insertion impossible pour le jour %s.', 'ts-appointment'), $day_of_week);
                                $errors[] = $msg;
                            }
                        }

                        if (!empty($errors)) {
                            echo '<div class="notice notice-error"><p>' . __('Certains créneaux n\'ont pas pu être enregistrés : ', 'ts-appointment') . esc_html(implode(' | ', $errors)) . '</p></div>';
                        }

                        if ($created > 0) {
                            echo '<div class="notice notice-success"><p>' . __('Créneaux ajoutés/actualisés.', 'ts-appointment') . '</p></div>';
                        }
                        $slots = TS_Appointment_Database::get_slots();
                    }
                }

                if ($action === 'delete' && !empty($_POST['slot_id'])) {
                    $sid = intval($_POST['slot_id']);
                    TS_Appointment_Database::delete_slot($sid);
                    echo '<div class="notice notice-success"><p>' . __('Créneau supprimé.', 'ts-appointment') . '</p></div>';
                    $slots = TS_Appointment_Database::get_slots();
                }

                // Bulk actions: apply to selected slot IDs
                if ($action === 'bulk' && !empty($_POST['slot_ids']) && is_array($_POST['slot_ids'])) {
                    $slot_ids = array_map('intval', $_POST['slot_ids']);
                    $bulk_action = sanitize_text_field($_POST['bulk_action'] ?? '');
                    $affected = 0;
                    foreach ($slot_ids as $sid) {
                        if ($bulk_action === 'delete') {
                            TS_Appointment_Database::delete_slot($sid);
                            $affected++;
                        } elseif ($bulk_action === 'activate') {
                            TS_Appointment_Database::update_slot($sid, array('active' => 1));
                            $affected++;
                        } elseif ($bulk_action === 'deactivate') {
                            TS_Appointment_Database::update_slot($sid, array('active' => 0));
                            $affected++;
                        } elseif ($bulk_action === 'set_interval' && isset($_POST['bulk_interval'])) {
                            $interval = max(5, intval($_POST['bulk_interval']));
                            TS_Appointment_Database::update_slot($sid, array('slot_interval' => $interval));
                            $affected++;
                        }
                    }
                    if ($affected > 0) {
                        echo '<div class="notice notice-success"><p>' . sprintf(__('%d créneaux modifiés.', 'ts-appointment'), $affected) . '</p></div>';
                        $slots = TS_Appointment_Database::get_slots();
                    }
                }
            }
        }

        include TS_APPOINTMENT_DIR . 'templates/admin-slots.php';
    }

    public static function display_services() {
        // If editing a service, prepare data for the template
        $edit_service = null;
        if (!empty($_GET['edit_service_id'])) {
            $edit_id = intval($_GET['edit_service_id']);
            $edit_service = TS_Appointment_Database::get_service($edit_id);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ts_appointment_nonce'])) {
            if (wp_verify_nonce($_POST['ts_appointment_nonce'], 'ts_appointment_services')) {
                // Add service
                if (isset($_POST['action_type']) && $_POST['action_type'] === 'add') {
                    $price_by_location = array();
                    if (isset($_POST['price_by_location']) && is_array($_POST['price_by_location'])) {
                        foreach ($_POST['price_by_location'] as $loc_key => $price_val) {
                            $price_by_location[sanitize_key($loc_key)] = floatval($price_val);
                        }
                    }

                    // duration: accept value + unit
                    $dur_val = isset($_POST['service_duration_value']) ? intval($_POST['service_duration_value']) : intval($_POST['service_duration'] ?? 60);
                    $dur_unit = isset($_POST['service_duration_unit']) ? sanitize_text_field($_POST['service_duration_unit']) : 'minute';
                    $mult = 1;
                    if ($dur_unit === 'hour') $mult = 60;
                    if ($dur_unit === 'day') $mult = 1440;
                    $duration_minutes = max(1, $dur_val * $mult);

                    $data = array(
                        'name' => sanitize_text_field($_POST['service_name'] ?? ''),
                        'description' => sanitize_textarea_field($_POST['service_description'] ?? ''),
                        'duration' => intval($duration_minutes),
                        'price' => !empty($price_by_location) ? wp_json_encode($price_by_location) : '0',
                        'active' => isset($_POST['service_active']) ? 1 : 0,
                    );
                    if (!empty($data['name'])) {
                        $insert_id = TS_Appointment_Database::insert_service($data);
                        if ($insert_id) {
                            echo '<div class="notice notice-success"><p>' . __('Service ajouté.', 'ts-appointment') . '</p></div>';
                        } else {
                            global $wpdb;
                            $err = !empty($wpdb->last_error) ? $wpdb->last_error : __('Erreur inconnue lors de l’ajout.', 'ts-appointment');
                            echo '<div class="notice notice-error"><p>' . sprintf(__('Erreur lors de l’ajout du service: %s', 'ts-appointment'), esc_html($err)) . '</p></div>';
                        }
                    } else {
                        echo '<div class="notice notice-error"><p>' . __('Le nom du service est obligatoire.', 'ts-appointment') . '</p></div>';
                    }
                }
                // Edit service
                if (isset($_POST['action_type']) && $_POST['action_type'] === 'edit' && !empty($_POST['service_id'])) {
                    $sid = intval($_POST['service_id']);
                    $price_by_location = array();
                    if (isset($_POST['price_by_location']) && is_array($_POST['price_by_location'])) {
                        foreach ($_POST['price_by_location'] as $loc_key => $price_val) {
                            $price_by_location[sanitize_key($loc_key)] = floatval($price_val);
                        }
                    }
                    // duration: accept value + unit
                    $dur_val = isset($_POST['service_duration_value']) ? intval($_POST['service_duration_value']) : intval($_POST['service_duration'] ?? 60);
                    $dur_unit = isset($_POST['service_duration_unit']) ? sanitize_text_field($_POST['service_duration_unit']) : 'minute';
                    $mult = 1;
                    if ($dur_unit === 'hour') $mult = 60;
                    if ($dur_unit === 'day') $mult = 1440;
                    $duration_minutes = max(1, $dur_val * $mult);

                    $data = array(
                        'name' => sanitize_text_field($_POST['service_name'] ?? ''),
                        'description' => sanitize_textarea_field($_POST['service_description'] ?? ''),
                        'duration' => intval($duration_minutes),
                        'price' => !empty($price_by_location) ? wp_json_encode($price_by_location) : '0',
                        'active' => isset($_POST['service_active']) ? 1 : 0,
                    );
                    TS_Appointment_Database::update_service($sid, $data);
                    echo '<div class="notice notice-success"><p>' . __('Service modifié.', 'ts-appointment') . '</p></div>';
                }
                // Delete service
                if (isset($_POST['action_type']) && $_POST['action_type'] === 'delete' && !empty($_POST['service_id'])) {
                    $sid = intval($_POST['service_id']);
                    TS_Appointment_Database::delete_service($sid);
                    echo '<div class="notice notice-success"><p>' . __('Service supprimé.', 'ts-appointment') . '</p></div>';
                }

                // Duplicate service
                if (isset($_POST['action_type']) && $_POST['action_type'] === 'duplicate' && !empty($_POST['service_id'])) {
                    $sid = intval($_POST['service_id']);
                    $new_id = TS_Appointment_Database::duplicate_service($sid);
                    if ($new_id) {
                        echo '<div class="notice notice-success"><p>' . __('Service dupliqué.', 'ts-appointment') . '</p></div>';
                    } else {
                        echo '<div class="notice notice-error"><p>' . __('Duplication impossible.', 'ts-appointment') . '</p></div>';
                    }
                }
            }
        }

        $services = TS_Appointment_Database::get_services(false);
        include TS_APPOINTMENT_DIR . 'templates/admin-services.php';
    }

    public static function display_email_templates() {
        // Ensure defaults
        TS_Appointment_Database::maybe_init_defaults();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ts_appointment_nonce'])) {
            if (wp_verify_nonce($_POST['ts_appointment_nonce'], 'ts_appointment_email_templates') && isset($_POST['action_type']) && $_POST['action_type'] === 'save_templates') {
                $templates = array();
                if (isset($_POST['templates']) && is_array($_POST['templates'])) {
                    foreach ($_POST['templates'] as $k => $tpl) {
                        $sub = sanitize_text_field($tpl['subject'] ?? '');
                        // Body contains HTML => allow safe tags
                        $body = isset($tpl['body']) ? wp_kses_post(wp_unslash($tpl['body'])) : '';
                        $templates[$k] = array('subject' => $sub, 'body' => $body);
                    }
                }
                update_option('ts_appointment_email_templates', wp_json_encode($templates));
                echo '<div class="notice notice-success"><p>' . __('Templates enregistrés.', 'ts-appointment') . '</p></div>';
            }
        }

        include TS_APPOINTMENT_DIR . 'templates/admin-emails.php';
    }

    private static function save_settings() {
        $settings = array(
            'business_name',
            'business_email',
            'business_address',
            'business_phone',
            'timezone',
            'max_days_ahead',
            'date_format',
            'time_format',
            'color_primary',
            'color_secondary',
            'currency_symbol',
            'currency_position',
            'enable_reminders',
            'reminder_hours',
            'google_calendar_enabled',
            'google_calendar_id',
            'google_client_id',
            'google_client_secret',
            'google_send_updates',
            'google_email_reminders',
            // iCalendar (.ics) options
            'ics_enabled',
            'ics_attach',
            'ics_duration',
            'ics_reminder_minutes',
            'ics_method',
            'turnstile_enabled',
            'turnstile_site_key',
            'turnstile_secret_key',
            'debug_enabled',
            // Mailgun settings
            'mailgun_enabled',
            'mailgun_global_enabled',
            'mailgun_domain',
            'mailgun_api_key',
            // JSON configs
            'locations_config',
            'form_schema',
        );

        foreach ($settings as $setting) {
            if (isset($_POST[$setting])) {
                if (in_array($setting, array('locations_config', 'form_schema'), true)) {
                    $raw = wp_unslash($_POST[$setting]);
                    update_option('ts_appointment_' . $setting, $raw);
                } else {
                    update_option('ts_appointment_' . $setting, sanitize_text_field($_POST[$setting]));
                }
            }
        }

        // Save selected email types that should receive .ics (array from checkboxes)
        if (isset($_POST['ics_send_for']) && is_array($_POST['ics_send_for'])) {
            $vals = array_map('sanitize_text_field', $_POST['ics_send_for']);
            update_option('ts_appointment_ics_send_for', wp_json_encode(array_values($vals)));
        } else {
            // Ensure option exists (store empty array when none selected)
            update_option('ts_appointment_ics_send_for', wp_json_encode(array()));
        }

        // Gestion explicite des cases à cocher pour éviter de conserver un état activé quand décochées
            $checkboxes = array('enable_reminders', 'google_calendar_enabled', 'turnstile_enabled', 'debug_enabled', 'mailgun_enabled', 'mailgun_global_enabled', 'ics_enabled', 'ics_attach');
        foreach ($checkboxes as $checkbox) {
            if (!isset($_POST[$checkbox])) {
                update_option('ts_appointment_' . $checkbox, 0);
            }
        }

        // If debug has been disabled, remove the debug log file
        try {
            $prev = get_option('ts_appointment_debug_enabled', 0);
        } catch (Exception $e) {
            $prev = 0;
        }
        $new = isset($_POST['debug_enabled']) ? 1 : 0;
        if ($prev && !$new) {
            $log_file = TS_APPOINTMENT_DIR . 'debug.log';
            if (file_exists($log_file)) {
                @unlink($log_file);
            }
        }
    }

    private static function get_locations_config() {
        $json = get_option('ts_appointment_locations_config');
        $data = json_decode($json, true);
        return is_array($data) ? $data : array();
    }

    private static function save_locations_config($locations) {
        update_option('ts_appointment_locations_config', wp_json_encode(array_values($locations)));
    }

    private static function location_exists($locations, $key) {
        foreach ($locations as $loc) {
            if (isset($loc['key']) && $loc['key'] === $key) {
                return true;
            }
        }
        return false;
    }

    private static function get_form_fields() {
        $json = get_option('ts_appointment_form_schema');
        $data = json_decode($json, true);
        return is_array($data) ? $data : array();
    }

    private static function save_form_fields($fields) {
        update_option('ts_appointment_form_schema', wp_json_encode(array_values($fields)));
    }

    private static function form_field_exists($fields, $key) {
        foreach ($fields as $field) {
            if (isset($field['key']) && $field['key'] === $key) {
                return true;
            }
        }
        return false;
    }
}
