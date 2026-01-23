<div class="wrap">
    <h1><?php echo esc_html__('Modifier le rendez-vous', 'ts-appointment'); ?></h1>

    <?php if (empty($appointment)) : ?>
        <div class="notice notice-error"><p><?php echo esc_html__('Rendez-vous introuvable.', 'ts-appointment'); ?></p></div>
    <?php else : 
        $client_data = array();
        if (!empty($appointment->client_data)) {
            $tmp = json_decode($appointment->client_data, true);
            if (is_array($tmp)) $client_data = $tmp;
        }
        $form_fields = TS_Appointment_Admin::get_form_fields();
    ?>

    <form method="post">
        <?php wp_nonce_field('ts_appointment_edit_appointment', 'ts_appointment_nonce'); ?>
        <input type="hidden" name="appointment_id" value="<?php echo intval($appointment->id); ?>" />

        <h2><?php echo esc_html__('Informations client', 'ts-appointment'); ?></h2>
        <table class="form-table">
            <tbody>
                <?php foreach ($form_fields as $field) :
                    $key = $field['key'] ?? '';
                    $label = $field['label'] ?? $key;
                    $type = $field['type'] ?? 'text';
                    $val = isset($client_data[$key]) ? $client_data[$key] : '';
                ?>
                <tr>
                    <th><label for="field_<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
                    <td>
                        <?php if ($type === 'textarea') : ?>
                            <textarea name="fields[<?php echo esc_attr($key); ?>]" id="field_<?php echo esc_attr($key); ?>" class="large-text" rows="4"><?php echo esc_textarea($val); ?></textarea>
                        <?php else : ?>
                            <input type="text" name="fields[<?php echo esc_attr($key); ?>]" id="field_<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($val); ?>" class="regular-text" />
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2><?php echo esc_html__('Détails du rendez-vous', 'ts-appointment'); ?></h2>
        <table class="form-table">
            <tbody>
                <tr>
                    <th><label for="appointment_date"><?php echo esc_html__('Date', 'ts-appointment'); ?></label></th>
                    <td><input type="date" name="appointment_date" id="appointment_date" value="<?php echo esc_attr($appointment->appointment_date); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="appointment_time"><?php echo esc_html__('Heure', 'ts-appointment'); ?></label></th>
                    <td><input type="time" name="appointment_time" id="appointment_time" value="<?php echo esc_attr($appointment->appointment_time); ?>" /></td>
                </tr>
                <tr>
                    <th><label for="appointment_type"><?php echo esc_html__('Type', 'ts-appointment'); ?></label></th>
                    <td><input type="text" name="appointment_type" id="appointment_type" value="<?php echo esc_attr($appointment->appointment_type); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="status"><?php echo esc_html__('Statut', 'ts-appointment'); ?></label></th>
                    <td>
                        <select name="status" id="status">
                            <?php $statuses = array('pending' => __('En attente','ts-appointment'),'confirmed'=>__('Confirmé','ts-appointment'),'completed'=>__('Complété','ts-appointment'),'cancelled'=>__('Annulé','ts-appointment'));
                            foreach ($statuses as $k=>$v) : ?>
                                <option value="<?php echo esc_attr($k); ?>" <?php selected($appointment->status, $k); ?>><?php echo esc_html($v); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>

        <p class="submit"><button class="button button-primary" type="submit"><?php echo esc_html__('Enregistrer les modifications', 'ts-appointment'); ?></button>
        <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=ts-appointment&tab=appointments')); ?>"><?php echo esc_html__('Annuler', 'ts-appointment'); ?></a></p>
    </form>

    <?php endif; ?>
</div>
