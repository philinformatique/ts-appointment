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
        $business_address = get_option('ts_appointment_business_address');

        $subject = self::render_email_template_subject('client_new', array(
            'client_name' => $appointment->client_name,
            'service_name' => $service->name,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time,
            'location' => $appointment->appointment_type,
            'business_name' => $business_name,
            'business_address' => $business_address,
            'client_address' => $appointment->client_address,
        ));

        $message = self::render_email_template_body('client_new', array(
            'client_name' => $appointment->client_name,
            'service_name' => $service->name,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time,
            'location' => $appointment->appointment_type,
            'business_name' => $business_name,
            'business_address' => $business_address,
            'appointment_id' => $appointment->id,
            'client_address' => $appointment->client_address,
        ));
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $business_name . ' <' . $business_email . '>',
        );

        // Build attachments array if .ics generation enabled and configured
        $attachments = array();
        $ics_for_raw = get_option('ts_appointment_ics_send_for', '[]');
        $ics_for = json_decode($ics_for_raw, true) ?: array();
        if (get_option('ts_appointment_ics_enabled') && get_option('ts_appointment_ics_attach') && in_array('client_new', $ics_for, true)) {
            $ics_path = self::generate_ics_file($appointment);
            if ($ics_path && file_exists($ics_path)) {
                $attachments[] = $ics_path;
            }
        }

        // Use Mailgun if enabled, otherwise wp_mail. Fallback to wp_mail on failure.
        if (!empty(get_option('ts_appointment_mailgun_enabled')) && self::send_via_mailgun($appointment->client_email, $subject, $message, $headers, $attachments)) {
            // remove temp ics file
            if (!empty($ics_path) && file_exists($ics_path)) @unlink($ics_path);
            return true;
        }
        ts_appointment_log('Using wp_mail for booking notification to ' . $appointment->client_email);
        wp_mail($appointment->client_email, $subject, $message, $headers, $attachments);
        // cleanup temp file
        if (!empty($ics_path) && file_exists($ics_path)) @unlink($ics_path);
        return true;
    }

    public static function send_confirmation($appointment) {
        $service = TS_Appointment_Database::get_service($appointment->service_id);
        $business_email = get_option('ts_appointment_business_email');
        $business_name = get_option('ts_appointment_business_name');
        $business_address = get_option('ts_appointment_business_address');

        $subject = self::render_email_template_subject('client_confirmation', array(
            'client_name' => $appointment->client_name,
            'service_name' => $service->name,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time,
            'location' => $appointment->appointment_type,
            'business_name' => $business_name,
            'business_address' => $business_address,
            'client_address' => $appointment->client_address,
        ));

        $message = self::render_email_template_body('client_confirmation', array(
            'client_name' => $appointment->client_name,
            'service_name' => $service->name,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time,
            'location' => $appointment->appointment_type,
            'business_name' => $business_name,
            'business_address' => $business_address,
            'appointment_id' => $appointment->id,
            'client_address' => $appointment->client_address,
        ));
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $business_name . ' <' . $business_email . '>',
        );

        $attachments = array();
        $ics_for_raw = get_option('ts_appointment_ics_send_for', '[]');
        $ics_for = json_decode($ics_for_raw, true) ?: array();
        if (get_option('ts_appointment_ics_enabled') && get_option('ts_appointment_ics_attach') && in_array('client_confirmation', $ics_for, true)) {
            $ics_path = self::generate_ics_file($appointment);
            if ($ics_path && file_exists($ics_path)) {
                $attachments[] = $ics_path;
            }
        }

        if (!empty(get_option('ts_appointment_mailgun_enabled')) && self::send_via_mailgun($appointment->client_email, $subject, $message, $headers, $attachments)) {
            if (!empty($ics_path) && file_exists($ics_path)) @unlink($ics_path);
            return true;
        }
        ts_appointment_log('Using wp_mail for confirmation to ' . $appointment->client_email);
        wp_mail($appointment->client_email, $subject, $message, $headers, $attachments);
        if (!empty($ics_path) && file_exists($ics_path)) @unlink($ics_path);
        return true;
    }

    public static function send_admin_notification($appointment) {
        $service = TS_Appointment_Database::get_service($appointment->service_id);
        $admin_email = get_option('admin_email');
        $business_name = get_option('ts_appointment_business_name');
        $business_address = get_option('ts_appointment_business_address');

        $subject = self::render_email_template_subject('admin_new', array(
            'client_name' => $appointment->client_name,
            'service_name' => $service->name,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time,
            'location' => $appointment->appointment_type,
            'business_name' => $business_name,
            'business_address' => $business_address,
            'client_address' => $appointment->client_address,
        ));

        $message = self::render_email_template_body('admin_new', array(
            'client_name' => $appointment->client_name,
            'service_name' => $service->name,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time,
            'location' => $appointment->appointment_type,
            'business_name' => $business_name,
            'business_address' => $business_address,
            'client_address' => $appointment->client_address,
        ));
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
        );

        if (!empty(get_option('ts_appointment_mailgun_enabled')) && self::send_via_mailgun($admin_email, $subject, $message, $headers, array())) {
            return true;
        }
        ts_appointment_log('Using wp_mail for admin notification to ' . $admin_email);
        wp_mail($admin_email, $subject, $message, $headers);
        return true;
    }

    public static function send_cancellation($appointment, $reason = '') {
        $service = TS_Appointment_Database::get_service($appointment->service_id);
        $business_email = get_option('ts_appointment_business_email');
        $business_name = get_option('ts_appointment_business_name');
        $business_address = get_option('ts_appointment_business_address');

        $subject = self::render_email_template_subject('client_cancellation', array(
            'client_name' => $appointment->client_name,
            'service_name' => $service->name,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time,
            'location' => $appointment->appointment_type,
            'business_name' => $business_name,
            'business_address' => $business_address,
            'client_address' => $appointment->client_address,
        ));

        $message = self::render_email_template_body('client_cancellation', array(
            'client_name' => $appointment->client_name,
            'service_name' => $service->name,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time,
            'location' => $appointment->appointment_type,
            'business_name' => $business_name,
            'business_address' => $business_address,
            'reason' => $reason,
            'client_address' => $appointment->client_address,
        ));
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $business_name . ' <' . $business_email . '>',
        );

        if (!empty(get_option('ts_appointment_mailgun_enabled')) && self::send_via_mailgun($appointment->client_email, $subject, $message, $headers, array())) {
            return true;
        }
        ts_appointment_log('Using wp_mail for cancellation to ' . $appointment->client_email);
        wp_mail($appointment->client_email, $subject, $message, $headers);
        return true;
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

        // Build cancel button if appointment id is present
        $cancel_button_html = '';
        if (!empty($appointment->id)) {
            $nonce = wp_create_nonce('ts_appointment_cancel_' . intval($appointment->id));
            $cancel_url = admin_url('admin-post.php?action=ts_appointment_cancel_public&appointment_id=' . intval($appointment->id) . '&_wpnonce=' . $nonce);
            $cancel_button_html = '<p style="text-align:center;margin:24px 0"><a href="' . esc_url($cancel_url) . '" style="background:' . esc_attr($color_primary) . ';color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none;display:inline-block">' . esc_html__('Annuler le rendez-vous', 'ts-appointment') . '</a></p>';
        }

        $html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{font-family:Helvetica,Arial,sans-serif;color:#333;margin:0;padding:0}a{color:' . $color_primary . '} .container{max-width:680px;margin:0 auto;background:#f6f7fb;padding:24px} .card{background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.06)} .hero{background:' . $color_primary . ';color:#fff;padding:28px;text-align:center} .content{padding:24px} .meta{display:flex;flex-wrap:wrap;margin:16px 0} .meta .label{flex:0 0 140px;font-weight:700;color:' . $color_primary . '} .meta .value{flex:1} .footer{font-size:13px;color:#777;text-align:center;padding:18px}</style></head><body><div class="container"><div class="card"><div class="hero"><h2>' . esc_html__('Réservation reçue', 'ts-appointment') . '</h2></div><div class="content"><p>' . sprintf(esc_html__('Bonjour %s,', 'ts-appointment'), esc_html($appointment->client_name)) . '</p><p>' . esc_html__('Nous avons bien reçu votre demande de rendez-vous. Nous vous informerons dès que la demande sera confirmée.', 'ts-appointment') . '</p><div class="meta"><div class="label">' . esc_html__('Service', 'ts-appointment') . '</div><div class="value">' . esc_html($service->name) . '</div></div><div class="meta"><div class="label">' . esc_html__('Date', 'ts-appointment') . '</div><div class="value">' . $date_formatted . '</div></div><div class="meta"><div class="label">' . esc_html__('Heure', 'ts-appointment') . '</div><div class="value">' . $time_formatted . '</div></div><div class="meta"><div class="label">' . esc_html__('Lieu', 'ts-appointment') . '</div><div class="value">' . esc_html($appointment_type) . '</div></div>';
        if (!empty($appointment->client_address)) {
            $html .= '<div class="meta"><div class="label">' . esc_html__('Adresse', 'ts-appointment') . '</div><div class="value">' . esc_html($appointment->client_address) . '</div></div>';
        }

        $html .= $cancel_button_html;
        $html .= '<p>' . esc_html__('Merci,', 'ts-appointment') . '<br>' . esc_html($business_name) . '</p></div><div class="footer">' . sprintf(esc_html__('© %s. Tous droits réservés.', 'ts-appointment'), esc_html($business_name)) . '</div></div></div></body></html>';

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

        // Build cancel button if appointment id is present
        $cancel_button_html = '';
        if (!empty($appointment->id)) {
            $nonce = wp_create_nonce('ts_appointment_cancel_' . intval($appointment->id));
            $cancel_url = admin_url('admin-post.php?action=ts_appointment_cancel_public&appointment_id=' . intval($appointment->id) . '&_wpnonce=' . $nonce);
            $cancel_button_html = '<p style="text-align:center;margin:18px 0"><a href="' . esc_url($cancel_url) . '" style="background:' . esc_attr($color_primary) . ';color:#fff;padding:12px 22px;border-radius:6px;text-decoration:none;display:inline-block">' . esc_html__('Annuler le rendez-vous', 'ts-appointment') . '</a></p>';
        }

        $html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{font-family:Helvetica,Arial,sans-serif;color:#333;margin:0;padding:0} .container{max-width:680px;margin:0 auto;background:#f6f7fb;padding:24px} .card{background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.06)} .hero{background:' . $color_primary . ';color:#fff;padding:28px;text-align:center} .content{padding:24px} .meta{display:flex;flex-wrap:wrap;margin:12px 0} .meta .label{flex:0 0 140px;font-weight:700;color:' . $color_primary . '} .meta .value{flex:1} .footer{font-size:13px;color:#777;text-align:center;padding:18px}</style></head><body><div class="container"><div class="card"><div class="hero"><h2>' . esc_html__('Confirmation de rendez-vous', 'ts-appointment') . '</h2></div><div class="content"><p>' . sprintf(esc_html__('Bonjour %s,', 'ts-appointment'), esc_html($appointment->client_name)) . '</p><p>' . sprintf(esc_html__('Votre rendez-vous auprès de %s a été confirmé.', 'ts-appointment'), esc_html($business_name)) . '</p><div class="meta"><div class="label">' . esc_html__('Service', 'ts-appointment') . '</div><div class="value">' . esc_html($service->name) . '</div></div><div class="meta"><div class="label">' . esc_html__('Date', 'ts-appointment') . '</div><div class="value">' . $date_formatted . '</div></div><div class="meta"><div class="label">' . esc_html__('Heure', 'ts-appointment') . '</div><div class="value">' . $time_formatted . '</div></div><div class="meta"><div class="label">' . esc_html__('Lieu', 'ts-appointment') . '</div><div class="value">' . esc_html($appointment_type) . '</div></div>';
        if (!empty($appointment->client_address)) {
            $html .= '<div class="meta"><div class="label">' . esc_html__('Adresse', 'ts-appointment') . '</div><div class="value">' . esc_html($appointment->client_address) . '</div></div>';
        }

        $html .= $cancel_button_html;
        $html .= '<p>' . esc_html__('Merci d\'avoir réservé avec nous!', 'ts-appointment') . '</p></div><div class="footer">' . sprintf(esc_html__('© %s. Tous droits réservés.', 'ts-appointment'), esc_html($business_name)) . '</div></div></div></body></html>';

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
        $html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{font-family:Helvetica,Arial,sans-serif;color:#333;margin:0;padding:0}.container{max-width:680px;margin:0 auto;background:#f6f7fb;padding:24px}.card{background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,0.06)}.hero{background:#c0392b;color:#fff;padding:22px;text-align:center}.content{padding:22px}.footer{font-size:13px;color:#777;text-align:center;padding:18px}</style></head><body><div class="container"><div class="card"><div class="hero"><h2>' . esc_html__('Annulation de rendez-vous', 'ts-appointment') . '</h2></div><div class="content"><p>' . sprintf(esc_html__('Bonjour %s,', 'ts-appointment'), esc_html($appointment->client_name)) . '</p><p>' . sprintf(esc_html__('Votre rendez-vous du %s a été annulé.', 'ts-appointment'), date_i18n('j F Y H:i', strtotime($appointment->appointment_date . ' ' . $appointment->appointment_time))) . '</p>';
        if ($reason) {
            $html .= '<p><strong>' . esc_html__('Raison:', 'ts-appointment') . '</strong> ' . esc_html($reason) . '</p>';
        }
        $html .= '<p>' . esc_html__('Nous espérons vous revoir bientôt.', 'ts-appointment') . '</p></div><div class="footer">' . sprintf(esc_html__('© %s. Tous droits réservés.', 'ts-appointment'), esc_html($business_name)) . '</div></div></div></body></html>';

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

    /**
     * Parse simple conditionals inside templates.
     * Supported syntax: {if location==atelier}...{else}...{endif}
     * Comparison is case-insensitive and trims values.
     */
    private static function process_template_conditionals($body, $context = array()) {
        $pattern = '/\{if\s+([a-zA-Z0-9_]+)\s*(?:==|=)\s*(?:\'([^\']*)\'|"([^\"]*)"|([^\}\s]+))\s*\}(.*?)(?:\{else\}(.*?))?\{endif\}/si';

        $callback = function($m) use ($context) {
            $key = $m[1];
            $value = isset($context[$key]) ? (string)$context[$key] : '';
            $cmp = '';
            if (isset($m[2]) && $m[2] !== '') $cmp = $m[2];
            elseif (isset($m[3]) && $m[3] !== '') $cmp = $m[3];
            elseif (isset($m[4]) && $m[4] !== '') $cmp = $m[4];
            $trueBranch = isset($m[5]) ? $m[5] : '';
            $falseBranch = isset($m[6]) ? $m[6] : '';

            if (strcasecmp(trim($value), trim($cmp)) === 0) {
                return $trueBranch;
            }
            return $falseBranch;
        };

        // Apply repeatedly to handle multiple conditionals
        return preg_replace_callback($pattern, $callback, $body);
    }

    private static function render_email_template_body($key, $context = array()) {
        $templates_raw = get_option('ts_appointment_email_templates');
        $templates = json_decode($templates_raw, true);
        if (is_array($templates) && !empty($templates[$key]['body'])) {
            $body = $templates[$key]['body'];
            // Process simple conditionals before placeholder replacement.
            // Syntax supported: {if location==atelier}...{else}...{endif}
            $body = self::process_template_conditionals($body, $context);
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
            return $body ?? '';
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

    /**
     * Generate a temporary .ics file for an appointment and return its path.
     */
    private static function generate_ics_file($appointment) {
        if (empty($appointment)) return '';

        $duration_min = intval(get_option('ts_appointment_ics_duration', 60));
        $reminder_min = intval(get_option('ts_appointment_ics_reminder_minutes', 30));
        $method = get_option('ts_appointment_ics_method', 'PUBLISH');

        $business_name = get_option('ts_appointment_business_name');
        $business_email = get_option('ts_appointment_business_email');
        $business_address = get_option('ts_appointment_business_address');

        $start_dt = null;
        try {
            $tz = get_option('ts_appointment_timezone') ?: (get_option('timezone_string') ?: 'UTC');
            $dt = new DateTime($appointment->appointment_date . ' ' . $appointment->appointment_time, new DateTimeZone($tz));
            $start_dt = clone $dt;
            $end_dt = clone $dt;
            $end_dt->modify('+' . $duration_min . ' minutes');
        } catch (Exception $e) {
            $start_dt = new DateTime('now', new DateTimeZone('UTC'));
            $end_dt = clone $start_dt;
            $end_dt->modify('+' . $duration_min . ' minutes');
            $tz = 'UTC';
        }

        // Use UTC timestamps for compatibility
        $dtstamp = (new DateTime('now', new DateTimeZone('UTC')))->format('Ymd\THis\Z');
        $dtstart = clone $start_dt;
        $dtstart->setTimezone(new DateTimeZone('UTC'));
        $dtend = clone $end_dt;
        $dtend->setTimezone(new DateTimeZone('UTC'));

        $uid = 'ts-appointment-' . (isset($appointment->id) ? intval($appointment->id) : uniqid()) . '@' . parse_url(home_url(), PHP_URL_HOST);

        $summary = isset($appointment->service_name) ? $appointment->service_name : (isset($appointment->service) ? $appointment->service->name : 'Rendez-vous');
        $description = isset($appointment->client_notes) ? $appointment->client_notes : (isset($appointment->notes) ? $appointment->notes : '');
        $location = $business_address ?: (isset($appointment->location) ? $appointment->location : '');
        $organizer = $business_name ? ($business_name . ' <' . $business_email . '>') : $business_email;

        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//ts-appointment//EN\r\n";
        $ical .= "METHOD:" . esc_html($method) . "\r\n";
        $ical .= "BEGIN:VEVENT\r\n";
        $ical .= "UID:" . $uid . "\r\n";
        $ical .= "DTSTAMP:" . $dtstamp . "\r\n";
        $ical .= "DTSTART:" . $dtstart->format('Ymd\THis\Z') . "\r\n";
        $ical .= "DTEND:" . $dtend->format('Ymd\THis\Z') . "\r\n";
        $ical .= "SUMMARY:" . self::escape_ical_text($summary) . "\r\n";
        if (!empty($description)) $ical .= "DESCRIPTION:" . self::escape_ical_text($description) . "\r\n";
        if (!empty($location)) $ical .= "LOCATION:" . self::escape_ical_text($location) . "\r\n";
        if (!empty($organizer)) $ical .= "ORGANIZER:MAILTO:" . esc_html($business_email) . "\r\n";
        if (!empty($appointment->client_email)) {
            $ical .= "ATTENDEE;CN=" . self::escape_ical_text($appointment->client_name ?? $appointment->client_email) . ":MAILTO:" . esc_html($appointment->client_email) . "\r\n";
        }
        if ($reminder_min > 0) {
            $ical .= "BEGIN:VALARM\r\n";
            $ical .= "TRIGGER:-PT" . intval($reminder_min) . "M\r\n";
            $ical .= "ACTION:DISPLAY\r\n";
            $ical .= "DESCRIPTION:Reminder\r\n";
            $ical .= "END:VALARM\r\n";
        }
        $ical .= "END:VEVENT\r\n";
        $ical .= "END:VCALENDAR\r\n";

        $upload = wp_upload_dir();
        $dir = isset($upload['path']) ? $upload['path'] : sys_get_temp_dir();
        $filename = 'ts-appointment-' . (isset($appointment->id) ? intval($appointment->id) : uniqid()) . '.ics';
        $path = trailingslashit($dir) . $filename;

        try {
            file_put_contents($path, $ical);
            return $path;
        } catch (Exception $e) {
            return '';
        }
    }

    private static function escape_ical_text($text) {
        $replacements = array("\\" => "\\\\", "\n" => "\\n", ";" => "\\;", "," => "\\,");
        return strtr($text, $replacements);
    }

    /**
     * Send message using Mailgun API when configured.
     * Returns true on success, false on failure.
     */
    public static function send_via_mailgun($to, $subject, $html, $headers = array(), $attachments = array()) {
        if (empty(get_option('ts_appointment_mailgun_enabled'))) {
            return false;
        }

        $domain = get_option('ts_appointment_mailgun_domain');
        $api_key = get_option('ts_appointment_mailgun_api_key');
        if (empty($domain) || empty($api_key)) {
            ts_appointment_log('Mailgun not configured: missing domain or api key');
            return false;
        }

        // Determine from header
        $from = get_option('ts_appointment_business_email');
        $from_name = get_option('ts_appointment_business_name');
        foreach ($headers as $h) {
            if (stripos($h, 'From:') === 0) {
                $from = trim(substr($h, strlen('From:')));
                break;
            }
        }

        $url = 'https://api.mailgun.net/v3/' . $domain . '/messages';

        // If there are attachments and curl is available, use curl to send multipart/form-data
        if (!empty($attachments) && function_exists('curl_version') && function_exists('curl_init')) {
            $post = array(
                'from' => $from,
                'to' => is_array($to) ? implode(',', $to) : $to,
                'subject' => $subject,
                'html' => $html,
            );

            // prepare curl files
            foreach ($attachments as $i => $file) {
                if (is_string($file) && file_exists($file)) {
                    if (function_exists('curl_file_create')) {
                        $post['attachment'][] = curl_file_create($file);
                    } else {
                        // Older PHP (<5.5) fallback (may be disabled on some hosts)
                        $post['attachment'][] = '@' . $file;
                    }
                }
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_USERPWD, 'api:' . $api_key);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 20);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($response === false) {
                ts_appointment_log('Mailgun curl error: ' . curl_error($ch));
                curl_close($ch);
                return false;
            }
            curl_close($ch);

            if ($http_code >= 200 && $http_code < 300) {
                return true;
            }
            ts_appointment_log('Mailgun returned HTTP ' . $http_code . ' -- ' . $response);
            return false;
        }

        // Fallback to wp_remote_post without files
        $body = array(
            'from' => $from,
            'to' => is_array($to) ? implode(',', $to) : $to,
            'subject' => $subject,
            'html' => $html,
        );

        $args = array(
            'body' => $body,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode('api:' . $api_key),
            ),
            'timeout' => 20,
        );

        $resp = wp_remote_post($url, $args);
        if (is_wp_error($resp)) {
            ts_appointment_log('Mailgun request error: ' . $resp->get_error_message());
            return false;
        }

        $code = wp_remote_retrieve_response_code($resp);
        if ($code >= 200 && $code < 300) {
            return true;
        }

        ts_appointment_log('Mailgun returned HTTP ' . $code . ' -- ' . wp_remote_retrieve_body($resp));
        return false;
    }

    /**
     * Intercept WordPress wp_mail calls when global Mailgun override is enabled.
     * Called via the 'pre_wp_mail' filter. If Mailgun sends successfully, return true
     * to short-circuit wp_mail; otherwise return null to continue normal flow.
     */
    public static function mailgun_pre_wp_mail($null, $atts) {
        if (empty(get_option('ts_appointment_mailgun_global_enabled'))) {
            return null;
        }

        // $atts expected to be an array with keys: to, subject, message, headers, attachments
        $to = isset($atts['to']) ? $atts['to'] : '';
        $subject = isset($atts['subject']) ? $atts['subject'] : '';
        $message = isset($atts['message']) ? $atts['message'] : '';
        $headers = isset($atts['headers']) ? $atts['headers'] : array();

        $attachments = isset($atts['attachments']) ? $atts['attachments'] : array();
        $sent = self::send_via_mailgun($to, $subject, $message, (array)$headers, (array)$attachments);
        if ($sent) {
            return true; // short-circuit wp_mail, indicates success
        }
        // otherwise allow normal wp_mail to run
        return null;
    }
}
