<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap ts-appointment-admin">
    <h1><?php _e('Email Logs', 'ts-appointment'); ?></h1>

    <?php wp_nonce_field('ts_appointment_email_logs', 'ts_appointment_nonce'); ?>

    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('ID', 'ts-appointment'); ?></th>
                <th><?php _e('Date', 'ts-appointment'); ?></th>
                <th><?php _e('Type', 'ts-appointment'); ?></th>
                <th><?php _e('Recipient', 'ts-appointment'); ?></th>
                <th><?php _e('Subject', 'ts-appointment'); ?></th>
                <th><?php _e('Status', 'ts-appointment'); ?></th>
                <th><?php _e('Actions', 'ts-appointment'); ?></th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($logs)) : ?>
            <tr><td colspan="7"><?php _e('Aucun log trouvé.', 'ts-appointment'); ?></td></tr>
        <?php else: foreach ($logs as $l): ?>
            <tr>
                <td><?php echo intval($l->id); ?></td>
                <td><?php echo esc_html($l->created_at); ?></td>
                <td><?php echo esc_html($l->type); ?></td>
                <td><?php echo esc_html($l->recipient); ?></td>
                <td><?php echo esc_html(mb_strimwidth($l->subject, 0, 80, '...')); ?></td>
                <td><?php echo esc_html($l->status); ?></td>
                <td>
                    <a href="<?php echo esc_url(add_query_arg('view_log', $l->id)); ?>"><?php _e('Voir', 'ts-appointment'); ?></a>
                    | 
                    <a href="<?php echo esc_url(add_query_arg('edit_log', $l->id)); ?>"><?php _e('Edit Appoint.', 'ts-appointment'); ?></a>
                    | 
                    <form style="display:inline" method="post">
                        <?php wp_nonce_field('ts_appointment_email_logs', 'ts_appointment_nonce'); ?>
                        <input type="hidden" name="log_id" value="<?php echo intval($l->id); ?>">
                        <button type="submit" name="resend_log" value="1" class="button-link"><?php _e('Renvoyer', 'ts-appointment'); ?></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>

    <?php
    // View details
    if (!empty($_GET['view_log'])) {
        $view_id = intval($_GET['view_log']);
        $log = TS_Appointment_Database::get_log($view_id);
        if ($log) {
            $ctx = json_decode($log->context, true);
            $atts = json_decode($log->attachments, true);
            echo '<h2>' . sprintf(__('Log #%d', 'ts-appointment'), $log->id) . '</h2>';
            echo '<p><strong>' . __('Date:', 'ts-appointment') . '</strong> ' . esc_html($log->created_at) . '</p>';
            echo '<p><strong>' . __('Type:', 'ts-appointment') . '</strong> ' . esc_html($log->type) . '</p>';
            echo '<p><strong>' . __('Recipient:', 'ts-appointment') . '</strong> ' . esc_html($log->recipient) . '</p>';
            echo '<p><strong>' . __('Status:', 'ts-appointment') . '</strong> ' . esc_html($log->status) . '</p>';
            if (!empty($log->error_message)) echo '<p><strong>' . __('Error:', 'ts-appointment') . '</strong> ' . esc_html($log->error_message) . '</p>';
            echo '<h3>' . __('Subject', 'ts-appointment') . '</h3>';
            echo '<div style="background:#fff;border:1px solid #ddd;padding:12px;">' . esc_html($log->subject) . '</div>';
            echo '<h3>' . __('Body', 'ts-appointment') . '</h3>';
            echo '<div style="background:#fff;border:1px solid #ddd;padding:12px;">' . wp_kses_post($log->body) . '</div>';
            if (!empty($atts)) {
                echo '<h3>' . __('Attachments', 'ts-appointment') . '</h3>';
                echo '<ul>';
                foreach ($atts as $a) {
                    echo '<li>' . esc_html($a) . '</li>';
                }
                echo '</ul>';
            }
        }
    }

    // Edit appointment
    if (!empty($_GET['edit_log'])) {
        $edit_id = intval($_GET['edit_log']);
        $log = TS_Appointment_Database::get_log($edit_id);
        $appt = null;
        if ($log && !empty($log->appointment_id)) $appt = TS_Appointment_Database::get_appointment($log->appointment_id);
        if ($appt) {
            $form_schema = json_decode(get_option('ts_appointment_form_schema'), true);
            echo '<h2>' . __('Modifier rendez-vous', 'ts-appointment') . ' #' . intval($appt->id) . '</h2>';
            echo '<form method="post">';
            wp_nonce_field('ts_appointment_email_logs', 'ts_appointment_nonce');
            echo '<input type="hidden" name="appointment_id" value="' . intval($appt->id) . '">';
            echo '<table class="form-table">';
            if (is_array($form_schema)) {
                foreach ($form_schema as $f) {
                    if (empty($f['key'])) continue;
                    $k = $f['key'];
                    $label = isset($f['label']) ? $f['label'] : $k;
                    $val = isset($appt->{$k}) ? $appt->{$k} : '';
                    echo '<tr><th><label>' . esc_html($label) . '</label></th><td>';
                    if ($f['type'] === 'textarea') {
                        echo '<textarea name="' . esc_attr($k) . '" rows="4" cols="60">' . esc_textarea($val) . '</textarea>';
                    } else {
                        echo '<input type="text" name="' . esc_attr($k) . '" value="' . esc_attr($val) . '" class="regular-text">';
                    }
                    echo '</td></tr>';
                }
            }
            // Status selector
            echo '<tr><th>' . __('Statut', 'ts-appointment') . '</th><td><select name="status">';
            $statuses = array('pending','confirmed','completed','cancelled');
            foreach ($statuses as $s) {
                $sel = ($appt->status === $s) ? 'selected' : '';
                echo '<option value="' . esc_attr($s) . '" ' . $sel . '>' . esc_html($s) . '</option>';
            }
            echo '</select></td></tr>';
            echo '</table>';
            echo '<p><button type="submit" name="save_appointment" value="1" class="button button-primary">' . __('Enregistrer', 'ts-appointment') . '</button></p>';
            echo '</form>';
        } else {
            echo '<p>' . __('Rendez-vous non trouvé.', 'ts-appointment') . '</p>';
        }
    }
    ?>

</div>
