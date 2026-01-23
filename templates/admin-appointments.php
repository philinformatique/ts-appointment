<div class="ts-appointment-admin-appointments">
    <div class="appointments-header">
        <h1><?php echo esc_html__('Rendez-vous', 'ts-appointment'); ?></h1>
    </div>

    <?php if (empty($appointments)) : ?>
        <div class="notice notice-info"><p><?php echo esc_html__('Aucun rendez-vous', 'ts-appointment'); ?></p></div>
    <?php else : ?>
        <?php if (!empty($edit_appointment) && isset($edit_appointment->id)) : ?>
            <div class="ts-appointment-edit-form">
                <h2><?php echo esc_html__('Modifier le rendez-vous', 'ts-appointment'); ?> #<?php echo esc_html($edit_appointment->id); ?></h2>
                <form method="post">
                    <?php wp_nonce_field('ts_appointment_edit', 'ts_appointment_nonce'); ?>
                    <input type="hidden" name="appointment_id" value="<?php echo esc_attr($edit_appointment->id); ?>" />
                    <table class="form-table">
                        <tbody>
                            <?php
                            $schema = is_array($form_schema) ? $form_schema : array();
                            foreach ($schema as $field) {
                                $key = $field['key'] ?? '';
                                $label = $field['label'] ?? $key;
                                $type = $field['type'] ?? 'text';
                                $val = '';
                                if (!empty($edit_appointment->client_data) && is_array($edit_appointment->client_data) && isset($edit_appointment->client_data[$key])) {
                                    $val = $edit_appointment->client_data[$key];
                                }
                                echo '<tr><th><label>' . esc_html($label) . '</label></th><td>';
                                if ($type === 'textarea') {
                                    echo '<textarea name="' . esc_attr($key) . '" rows="4" cols="50">' . esc_textarea($val) . '</textarea>';
                                } elseif ($type === 'select' && !empty($field['options'])) {
                                    echo '<select name="' . esc_attr($key) . '">';
                                    foreach ($field['options'] as $opt) {
                                        echo '<option value="' . esc_attr($opt) . '"' . selected($val, $opt, false) . '>' . esc_html($opt) . '</option>';
                                    }
                                    echo '</select>';
                                } else {
                                    echo '<input type="text" name="' . esc_attr($key) . '" value="' . esc_attr($val) . '" class="regular-text" />';
                                }
                                echo '</td></tr>';
                            }
                            // Basic appointment fields
                            ?>
                            <tr><th><label><?php echo esc_html__('Date', 'ts-appointment'); ?></label></th><td><input type="date" name="appointment_date" value="<?php echo esc_attr($edit_appointment->appointment_date); ?>" /></td></tr>
                            <tr><th><label><?php echo esc_html__('Heure', 'ts-appointment'); ?></label></th><td><input type="time" name="appointment_time" value="<?php echo esc_attr($edit_appointment->appointment_time); ?>" /></td></tr>
                            <tr><th><label><?php echo esc_html__('Type', 'ts-appointment'); ?></label></th><td><input type="text" name="appointment_type" value="<?php echo esc_attr($edit_appointment->appointment_type); ?>" /></td></tr>
                            <tr><th><label><?php echo esc_html__('Statut', 'ts-appointment'); ?></label></th><td>
                                <select name="status">
                                    <option value="pending"<?php selected($edit_appointment->status, 'pending'); ?>><?php echo esc_html__('En attente', 'ts-appointment'); ?></option>
                                    <option value="confirmed"<?php selected($edit_appointment->status, 'confirmed'); ?>><?php echo esc_html__('Confirmé', 'ts-appointment'); ?></option>
                                    <option value="completed"<?php selected($edit_appointment->status, 'completed'); ?>><?php echo esc_html__('Complété', 'ts-appointment'); ?></option>
                                    <option value="cancelled"<?php selected($edit_appointment->status, 'cancelled'); ?>><?php echo esc_html__('Annulé', 'ts-appointment'); ?></option>
                                </select>
                            </td></tr>
                        </tbody>
                    </table>
                    <p><button class="button button-primary" type="submit" name="save_appointment" value="1"><?php echo esc_html__('Enregistrer', 'ts-appointment'); ?></button>
                    <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=ts-appointment-list')); ?>"><?php echo esc_html__('Annuler', 'ts-appointment'); ?></a></p>
                </form>
            </div>
        <?php endif; ?>
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('ID', 'ts-appointment'); ?></th>
                    <?php
                    // Render dynamic headers from form schema
                    if (!empty($form_schema) && is_array($form_schema)) {
                        foreach ($form_schema as $field) {
                            echo '<th>' . esc_html($field['label'] ?? $field['key'] ?? '') . '</th>';
                        }
                    }
                    ?>
                    <th><?php echo esc_html__('Date/Heure', 'ts-appointment'); ?></th>
                    <th><?php echo esc_html__('Type', 'ts-appointment'); ?></th>
                    <th><?php echo esc_html__('Statut', 'ts-appointment'); ?></th>
                    <th><?php echo esc_html__('Actions', 'ts-appointment'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appointment) :
                    $status_labels = array(
                        'pending' => __('En attente', 'ts-appointment'),
                        'confirmed' => __('Confirmé', 'ts-appointment'),
                        'completed' => __('Complété', 'ts-appointment'),
                        'cancelled' => __('Annulé', 'ts-appointment'),
                    );
                    $type_labels = array(
                        'on_site' => __('Au bureau', 'ts-appointment'),
                        'remote' => __('À distance', 'ts-appointment'),
                        'home' => __('Chez client', 'ts-appointment'),
                    );
                    ?>
                    <tr>
                        <td>#<?php echo esc_html($appointment->id); ?></td>
                        <?php
                            $client_data = array();
                            if (!empty($appointment->client_data)) {
                                $decoded = json_decode($appointment->client_data, true);
                                if (is_array($decoded)) {
                                    $client_data = $decoded;
                                } else {
                                    // If JSON decode fails, try treating it as a plain value
                                    $client_data = array('_raw' => $appointment->client_data);
                                }
                            }
                            // Debug: show what's in client_data if empty
                            if (empty($client_data) && !empty($appointment->client_data)) {
                                echo '<!-- DEBUG: client_data="' . esc_html($appointment->client_data) . '" -->';
                            }
                            // Render values from schema fields
                            if (!empty($form_schema) && is_array($form_schema)) {
                                foreach ($form_schema as $field) {
                                    $key = $field['key'] ?? '';
                                    $val = isset($client_data[$key]) ? $client_data[$key] : '';
                                    echo '<td>' . esc_html($val) . '</td>';
                                }
                            }
                        ?>
                        <td><?php echo esc_html(date_i18n('j/m/Y H:i', strtotime($appointment->appointment_date . ' ' . $appointment->appointment_time))); ?></td>
                        <td><?php echo esc_html(isset($type_labels[$appointment->appointment_type]) ? $type_labels[$appointment->appointment_type] : $appointment->appointment_type); ?></td>
                        <td><span class="status-badge status-<?php echo esc_attr($appointment->status); ?>"><?php echo esc_html(isset($status_labels[$appointment->status]) ? $status_labels[$appointment->status] : $appointment->status); ?></span></td>
                        <td>
                            <a href="#" class="button button-small view-appointment" data-id="<?php echo esc_attr($appointment->id); ?>"><?php echo esc_html__('Voir', 'ts-appointment'); ?></a>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=ts-appointment-list&amp;edit_id=' . intval($appointment->id))); ?>" class="button button-small edit-appointment"><?php echo esc_html__('Modifier', 'ts-appointment'); ?></a>
                            <?php if ($appointment->status === 'pending') : ?>
                                <a href="#" class="button button-small confirm-appointment" data-id="<?php echo esc_attr($appointment->id); ?>"><?php echo esc_html__('Confirmer', 'ts-appointment'); ?></a>
                            <?php endif; ?>
                            <a href="#" class="button button-small delete-appointment" data-id="<?php echo esc_attr($appointment->id); ?>"><?php echo esc_html__('Supprimer', 'ts-appointment'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
