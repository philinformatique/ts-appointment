<?php
/**
 * Gestion des emails
 */

if (!defined('ABSPATH')) {
    exit;
}

class TS_Appointment_Email {
    
    public static function send_booking_notification($appointment) {
        $service = TS_Appointment_Database::get_service($appointment->service_id);
        $business_email = get_option('ts_appointment_business_email');
        $business_name = get_option('ts_appointment_business_name');

        $subject = self::render_email_template_subject('client_new', array(
            'client_name' => $appointment->client_name,
            'service_name' => $service->name,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time,
            'location' => $appointment->appointment_type,
            'business_name' => $business_name,
        ));

        $message = self::render_email_template_body('client_new', array(
            'client_name' => $appointment->client_name,
            'service_name' => $service->name,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time,
            'location' => $appointment->appointment_type,
            'business_name' => $business_name,
            'appointment_id' => $appointment->id,
        ));
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $business_name . ' <' . $business_email . '>',
        );

        wp_mail($appointment->client_email, $subject, $message, $headers);
    }

    public static function send_confirmation($appointment) {
        $service = TS_Appointment_Database::get_service($appointment->service_id);
        $business_email = get_option('ts_appointment_business_email');
        $business_name = get_option('ts_appointment_business_name');

        $subject = self::render_email_template_subject('client_confirmation', array(
            'client_name' => $appointment->client_name,
            'service_name' => $service->name,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time,
            'location' => $appointment->appointment_type,
            'business_name' => $business_name,
        ));

        $message = self::render_email_template_body('client_confirmation', array(
            'client_name' => $appointment->client_name,
            'service_name' => $service->name,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time,
            'location' => $appointment->appointment_type,
            'business_name' => $business_name,
            'appointment_id' => $appointment->id,
        ));
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $business_name . ' <' . $business_email . '>',
        );

        wp_mail($appointment->client_email, $subject, $message, $headers);
    }

    public static function send_admin_notification($appointment) {
        $service = TS_Appointment_Database::get_service($appointment->service_id);
        $admin_email = get_option('admin_email');
        $business_name = get_option('ts_appointment_business_name');

        $subject = self::render_email_template_subject('admin_new', array(
            'client_name' => $appointment->client_name,
            'service_name' => $service->name,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time,
            'location' => $appointment->appointment_type,
            'business_name' => $business_name,
        ));

        $message = self::render_email_template_body('admin_new', array(
            'client_name' => $appointment->client_name,
            'service_name' => $service->name,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time,
            'location' => $appointment->appointment_type,
            'business_name' => $business_name,
        ));
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
        );

        wp_mail($admin_email, $subject, $message, $headers);
    }

    public static function send_cancellation($appointment, $reason = '') {
        $service = TS_Appointment_Database::get_service($appointment->service_id);
        $business_email = get_option('ts_appointment_business_email');
        $business_name = get_option('ts_appointment_business_name');

        $subject = self::render_email_template_subject('client_cancellation', array(
            'client_name' => $appointment->client_name,
            'service_name' => $service->name,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time,
            'location' => $appointment->appointment_type,
            'business_name' => $business_name,
        ));

        $message = self::render_email_template_body('client_cancellation', array(
            'client_name' => $appointment->client_name,
            'service_name' => $service->name,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time,
            'location' => $appointment->appointment_type,
            'business_name' => $business_name,
            'reason' => $reason,
        ));
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $business_name . ' <' . $business_email . '>',
        );

        wp_mail($appointment->client_email, $subject, $message, $headers);
    }

    private static function get_booking_notification_template($appointment, $service, $business_name) {
        // Get location label dynamically from config
        $locations_json = get_option('ts_appointment_locations_config');
        $locations = json_decode($locations_json, true);
        $appointment_type = $appointment->appointment_type;
        if (is_array($locations)) {
            foreach ($locations as $loc) {
                if (isset($loc['key']) && $loc['key'] === $appointment->appointment_type) {
                    $appointment_type = $loc['label'];
                    break;
                }
            }
        }
        
        $date_formatted = date_i18n('j F Y', strtotime($appointment->appointment_date));
        $time_formatted = date_i18n('H:i', strtotime($appointment->appointment_time));

        $color_primary = get_option('ts_appointment_color_primary', '#007cba');

        $html = '
        <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .email-container { max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px; }
                    .header { background: ' . $color_primary . '; color: white; padding: 30px; text-align: center; border-radius: 5px 5px 0 0; }
                    .content { background: white; padding: 30px; }
                    .appointment-details { background: #f5f5f5; padding: 20px; margin: 20px 0; border-left: 4px solid ' . $color_primary . '; }
                    .detail-row { display: flex; padding: 10px 0; border-bottom: 1px solid #eee; }
                    .detail-row:last-child { border-bottom: none; }
                    .detail-label { font-weight: bold; width: 150px; color: ' . $color_primary . '; }
                    .detail-value { flex: 1; }
                    .footer { background: #f5f5f5; padding: 20px; text-align: center; border-radius: 0 0 5px 5px; font-size: 12px; color: #666; }
                    .alert { background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 12px; border-radius: 4px; margin: 15px 0; }
                </style>
            </head>
            <body>
                <div class="email-container">
                    <div class="header">
                        <h1>' . __('Réservation reçue', 'ts-appointment') . '</h1>
                    </div>
                    <div class="content">
                        <p>' . sprintf(__('Bonjour %s,', 'ts-appointment'), esc_html($appointment->client_name)) . '</p>
                        <p>' . sprintf(__('Nous avons bien reçu votre demande de rendez-vous. Nous la traiterons rapidement et vous enverrons une confirmation.', 'ts-appointment')) . '</p>
                        
                        <div class="alert">
                            <strong>' . __('Statut:', 'ts-appointment') . '</strong> ' . __('En attente de confirmation', 'ts-appointment') . '
                        </div>
                        
                        <div class="appointment-details">
                            <div class="detail-row">
                                <div class="detail-label">' . __('Service', 'ts-appointment') . ':</div>
                                <div class="detail-value">' . esc_html($service->name) . '</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">' . __('Date', 'ts-appointment') . ':</div>
                                <div class="detail-value">' . $date_formatted . '</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">' . __('Heure', 'ts-appointment') . ':</div>
                                <div class="detail-value">' . $time_formatted . '</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">' . __('Type', 'ts-appointment') . ':</div>
                                <div class="detail-value">' . $appointment_type . '</div>
                            </div>
                            ' . ($appointment->client_address ? '
                            <div class="detail-row">
                                <div class="detail-label">' . __('Adresse', 'ts-appointment') . ':</div>
                                <div class="detail-value">' . esc_html($appointment->client_address) . '</div>
                            </div>
                            ' : '') . '
                        </div>
                        
                        <p>' . __('Merci!', 'ts-appointment') . '</p>
                    </div>
                    <div class="footer">
                        <p>' . sprintf(__('© %s - Tous droits réservés', 'ts-appointment'), esc_html($business_name)) . '</p>
                    </div>
                </div>
            </body>
        </html>';

        return $html;
    }

    private static function get_confirmation_email_template($appointment, $service, $business_name) {
        // Get location label dynamically from config
        $locations_json = get_option('ts_appointment_locations_config');
        $locations = json_decode($locations_json, true);
        $appointment_type = $appointment->appointment_type;
        if (is_array($locations)) {
            foreach ($locations as $loc) {
                if (isset($loc['key']) && $loc['key'] === $appointment->appointment_type) {
                    $appointment_type = $loc['label'];
                    break;
                }
            }
        }
        
        $date_formatted = date_i18n('j F Y', strtotime($appointment->appointment_date));
        $time_formatted = date_i18n('H:i', strtotime($appointment->appointment_time));

        $color_primary = get_option('ts_appointment_color_primary', '#007cba');

        $html = '
        <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .email-container { max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px; }
                    .header { background: ' . $color_primary . '; color: white; padding: 30px; text-align: center; border-radius: 5px 5px 0 0; }
                    .content { background: white; padding: 30px; }
                    .appointment-details { background: #f5f5f5; padding: 20px; margin: 20px 0; border-left: 4px solid ' . $color_primary . '; }
                    .detail-row { display: flex; padding: 10px 0; border-bottom: 1px solid #eee; }
                    .detail-row:last-child { border-bottom: none; }
                    .detail-label { font-weight: bold; width: 150px; color: ' . $color_primary . '; }
                    .detail-value { flex: 1; }
                    .footer { background: #f5f5f5; padding: 20px; text-align: center; border-radius: 0 0 5px 5px; font-size: 12px; color: #666; }
                    .button { display: inline-block; background: ' . $color_primary . '; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                </style>
            </head>
            <body>
                <div class="email-container">
                    <div class="header">
                        <h1>' . __('Confirmation de rendez-vous', 'ts-appointment') . '</h1>
                    </div>
                    <div class="content">
                        <p>' . sprintf(__('Bonjour %s,', 'ts-appointment'), esc_html($appointment->client_name)) . '</p>
                        <p>' . sprintf(__('Votre rendez-vous auprès de %s a été confirmé.', 'ts-appointment'), esc_html($business_name)) . '</p>
                        
                        <div class="appointment-details">
                            <div class="detail-row">
                                <div class="detail-label">' . __('Service', 'ts-appointment') . ':</div>
                                <div class="detail-value">' . esc_html($service->name) . '</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">' . __('Date', 'ts-appointment') . ':</div>
                                <div class="detail-value">' . $date_formatted . '</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">' . __('Heure', 'ts-appointment') . ':</div>
                                <div class="detail-value">' . $time_formatted . '</div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">' . __('Type', 'ts-appointment') . ':</div>
                                <div class="detail-value">' . $appointment_type . '</div>
                            </div>
                            ' . ($appointment->client_address ? '
                            <div class="detail-row">
                                <div class="detail-label">' . __('Adresse', 'ts-appointment') . ':</div>
                                <div class="detail-value">' . esc_html($appointment->client_address) . '</div>
                            </div>
                            ' : '') . '
                        </div>
                        
                        <p>' . __('Merci d\'avoir réservé avec nous!', 'ts-appointment') . '</p>
                    </div>
                    <div class="footer">
                        <p>' . sprintf(__('© %s - Tous droits réservés', 'ts-appointment'), esc_html($business_name)) . '</p>
                    </div>
                </div>
            </body>
        </html>';

        return $html;
    }

    private static function get_admin_notification_template($appointment, $service, $business_name) {
        // Get location label dynamically from config
        $locations_json = get_option('ts_appointment_locations_config');
        $locations = json_decode($locations_json, true);
        $appointment_type = $appointment->appointment_type;
        if (is_array($locations)) {
            foreach ($locations as $loc) {
                if (isset($loc['key']) && $loc['key'] === $appointment->appointment_type) {
                    $appointment_type = $loc['label'];
                    break;
                }
            }
        }
        $date_formatted = date_i18n('j F Y', strtotime($appointment->appointment_date));
        $time_formatted = date_i18n('H:i', strtotime($appointment->appointment_time));

        $html = '
        <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .email-container { max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px; }
                    .header { background: #333; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                    .content { background: white; padding: 20px; }
                    .detail-row { display: flex; padding: 10px 0; border-bottom: 1px solid #eee; }
                    .detail-label { font-weight: bold; width: 150px; }
                    .footer { background: #f5f5f5; padding: 20px; text-align: center; border-radius: 0 0 5px 5px; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class="email-container">
                    <div class="header">
                        <h1>' . __('Nouveau rendez-vous', 'ts-appointment') . '</h1>
                    </div>
                    <div class="content">
                        <h2>' . sprintf(__('Rendez-vous de %s', 'ts-appointment'), esc_html($appointment->client_name)) . '</h2>
                        
                        <div class="detail-row">
                            <div class="detail-label">' . __('Client', 'ts-appointment') . ':</div>
                            <div>' . esc_html($appointment->client_name) . '</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">' . __('Email', 'ts-appointment') . ':</div>
                            <div>' . esc_html($appointment->client_email) . '</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">' . __('Téléphone', 'ts-appointment') . ':</div>
                            <div>' . esc_html($appointment->client_phone) . '</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">' . __('Service', 'ts-appointment') . ':</div>
                            <div>' . esc_html($service->name) . '</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">' . __('Date', 'ts-appointment') . ':</div>
                            <div>' . $date_formatted . ' à ' . $time_formatted . '</div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">' . __('Type', 'ts-appointment') . ':</div>
                            <div>' . $appointment_type . '</div>
                        </div>
                        ' . ($appointment->client_address ? '
                        <div class="detail-row">
                            <div class="detail-label">' . __('Adresse', 'ts-appointment') . ':</div>
                            <div>' . esc_html($appointment->client_address) . '</div>
                        </div>
                        ' : '') . '
                        ' . ($appointment->notes ? '
                        <div class="detail-row">
                            <div class="detail-label">' . __('Notes', 'ts-appointment') . ':</div>
                            <div>' . esc_html($appointment->notes) . '</div>
                        </div>
                        ' : '') . '
                    </div>
                    <div class="footer">
                        <p>' . sprintf(__('Connectez-vous au <a href="%s">tableau de bord</a> pour gérer ce rendez-vous.', 'ts-appointment'), admin_url('admin.php?page=ts-appointment-list')) . '</p>
                    </div>
                </div>
            </body>
        </html>';

        return $html;
    }

    private static function get_cancellation_email_template($appointment, $service, $business_name, $reason) {
        $html = '
        <html>
            <head>
                <meta charset="UTF-8">
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .email-container { max-width: 600px; margin: 0 auto; background: #f9f9f9; padding: 20px; }
                    .header { background: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
                    .content { background: white; padding: 20px; }
                    .footer { background: #f5f5f5; padding: 20px; text-align: center; border-radius: 0 0 5px 5px; }
                </style>
            </head>
            <body>
                <div class="email-container">
                    <div class="header">
                        <h1>' . __('Annulation de rendez-vous', 'ts-appointment') . '</h1>
                    </div>
                    <div class="content">
                        <p>' . sprintf(__('Bonjour %s,', 'ts-appointment'), esc_html($appointment->client_name)) . '</p>
                        <p>' . sprintf(__('Votre rendez-vous du %s a été annulé.', 'ts-appointment'), date_i18n('j F Y H:i', strtotime($appointment->appointment_date . ' ' . $appointment->appointment_time))) . '</p>
                        ' . ($reason ? '<p><strong>' . __('Raison:', 'ts-appointment') . '</strong> ' . esc_html($reason) . '</p>' : '') . '
                        <p>' . __('Nous espérons vous revoir bientôt.', 'ts-appointment') . '</p>
                    </div>
                    <div class="footer">
                        <p>' . sprintf(__('© %s - Tous droits réservés', 'ts-appointment'), esc_html($business_name)) . '</p>
                    </div>
                </div>
            </body>
        </html>';

        return $html;
    }

    private static function render_email_template_subject($key, $context = array()) {
        $templates_raw = get_option('ts_appointment_email_templates');
        $templates = json_decode($templates_raw, true);
        if (is_array($templates) && !empty($templates[$key]['subject'])) {
            $subject = $templates[$key]['subject'];
            foreach ($context as $k => $v) {
                $subject = str_replace('{' . $k . '}', $v, $subject);
            }
            return wp_strip_all_tags($subject);
        }
        // Fallback
        return '';
    }

    private static function render_email_template_body($key, $context = array()) {
        $templates_raw = get_option('ts_appointment_email_templates');
        $templates = json_decode($templates_raw, true);
        if (is_array($templates) && !empty($templates[$key]['body'])) {
            $body = $templates[$key]['body'];
            foreach ($context as $k => $v) {
                // basic context replacements (escaped)
                $body = str_replace('{' . $k . '}', esc_html($v), $body);
            }

            // Special placeholders: cancel URL/button (allow HTML for button)
            if (!empty($context['appointment_id'])) {
                $appt_id = intval($context['appointment_id']);
                $nonce = wp_create_nonce('ts_appointment_cancel_' . $appt_id);
                $cancel_url = admin_url('admin-post.php?action=ts_appointment_cancel_public&appointment_id=' . $appt_id . '&_wpnonce=' . $nonce);
                $body = str_replace('{cancel_url}', esc_url($cancel_url), $body);
                $btn_text = __('Annuler le rendez-vous', 'ts-appointment');
                $button_html = '<a href="' . esc_url($cancel_url) . '" style="display:inline-block;background:#c0392b;color:#fff;padding:10px 16px;border-radius:4px;text-decoration:none;">' . esc_html($btn_text) . '</a>';
                // allow raw HTML for cancel button placeholder
                $body = str_replace('{cancel_button}', $button_html, $body);
            }
            return $body;
        }
        // Fallback to previous methods if no template defined
        switch ($key) {
            case 'client_new':
                return self::get_booking_notification_template($context['appointment'] ?? (object)array(), TS_Appointment_Database::get_service($context['service_id'] ?? 0), $context['business_name'] ?? '');
            case 'client_confirmation':
                return self::get_confirmation_email_template($context['appointment'] ?? (object)array(), TS_Appointment_Database::get_service($context['service_id'] ?? 0), $context['business_name'] ?? '');
            case 'admin_new':
                return self::get_admin_notification_template($context['appointment'] ?? (object)array(), TS_Appointment_Database::get_service($context['service_id'] ?? 0), $context['business_name'] ?? '');
            case 'client_cancellation':
                return self::get_cancellation_email_template($context['appointment'] ?? (object)array(), TS_Appointment_Database::get_service($context['service_id'] ?? 0), $context['business_name'] ?? '', $context['reason'] ?? '');
        }
        return '';
    }
}
