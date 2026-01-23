<div class="ts-appointment-admin-appointments">
    <div class="appointments-header">
        <h1><?php echo esc_html__('Rendez-vous', 'ts-appointment'); ?></h1>
    </div>

    <?php if (empty($appointments)) : ?>
        <div class="notice notice-info"><p><?php echo esc_html__('Aucun rendez-vous', 'ts-appointment'); ?></p></div>
    <?php else : ?>
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th><?php echo esc_html__('ID', 'ts-appointment'); ?></th>
                    <th><?php echo esc_html__('Client', 'ts-appointment'); ?></th>
                    <th><?php echo esc_html__('Email', 'ts-appointment'); ?></th>
                    <th><?php echo esc_html__('Téléphone', 'ts-appointment'); ?></th>
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
                    <?php
                        $client = array();
                        if (!empty($appointment->client_data)) {
                            $decoded = json_decode($appointment->client_data, true);
                            if (is_array($decoded)) $client = $decoded;
                        }
                        $client_name = isset($client['client_name']) ? $client['client_name'] : '';
                        $client_email = isset($client['client_email']) ? $client['client_email'] : '';
                        $client_phone = isset($client['client_phone']) ? $client['client_phone'] : '';
                    ?>
                    <tr>
                        <td>#<?php echo esc_html($appointment->id); ?></td>
                        <td><?php echo esc_html($client_name); ?></td>
                        <td><?php echo esc_html($client_email); ?></td>
                        <td><?php echo esc_html($client_phone); ?></td>
                        <td><?php echo esc_html(date_i18n('j/m/Y H:i', strtotime($appointment->appointment_date . ' ' . $appointment->appointment_time))); ?></td>
                        <td><?php echo esc_html(isset($type_labels[$appointment->appointment_type]) ? $type_labels[$appointment->appointment_type] : $appointment->appointment_type); ?></td>
                        <td><span class="status-badge status-<?php echo esc_attr($appointment->status); ?>"><?php echo esc_html(isset($status_labels[$appointment->status]) ? $status_labels[$appointment->status] : $appointment->status); ?></span></td>
                        <td>
                            <a href="#" class="button button-small view-appointment" data-id="<?php echo esc_attr($appointment->id); ?>"><?php echo esc_html__('Voir', 'ts-appointment'); ?></a>
                            <?php if ($appointment->status === 'pending') : ?>
                                <a href="#" class="button button-small confirm-appointment" data-id="<?php echo esc_attr($appointment->id); ?>"><?php echo esc_html__('Confirmer', 'ts-appointment'); ?></a>
                            <?php endif; ?>
                            <a href="#" class="button button-small edit-appointment" 
                               data-id="<?php echo esc_attr($appointment->id); ?>"
                               data-client="<?php echo esc_attr(wp_json_encode($client)); ?>"
                               data-date="<?php echo esc_attr($appointment->appointment_date); ?>"
                               data-time="<?php echo esc_attr($appointment->appointment_time); ?>"
                               data-type="<?php echo esc_attr($appointment->appointment_type); ?>"
                               data-status="<?php echo esc_attr($appointment->status); ?>"
                            ><?php echo esc_html__('Modifier', 'ts-appointment'); ?></a>
                            <a href="#" class="button button-small delete-appointment" data-id="<?php echo esc_attr($appointment->id); ?>"><?php echo esc_html__('Supprimer', 'ts-appointment'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
            // Edit modal (hidden) - generate form fields from form_schema
            $form_schema = json_decode(get_option('ts_appointment_form_schema'), true);
        ?>
        <div id="ts-edit-appointment-modal" style="display:none;position:fixed;left:50%;top:10%;transform:translateX(-50%);width:700px;max-width:95%;background:#fff;border:1px solid #ddd;padding:20px;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,0.2);">
            <h2><?php echo esc_html__('Modifier le rendez-vous', 'ts-appointment'); ?></h2>
            <form method="post">
                <?php wp_nonce_field('ts_appointment_appointments','ts_appointment_nonce'); ?>
                <input type="hidden" name="action_type" value="edit">
                <input type="hidden" name="appointment_id" id="ts-edit-appointment-id" value="">
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th><label for="ts-edit-appointment-date"><?php echo esc_html__('Date', 'ts-appointment'); ?></label></th>
                            <td><input type="date" name="appointment_date" id="ts-edit-appointment-date" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="ts-edit-appointment-time"><?php echo esc_html__('Heure', 'ts-appointment'); ?></label></th>
                            <td><input type="time" name="appointment_time" id="ts-edit-appointment-time" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="ts-edit-appointment-type"><?php echo esc_html__('Type', 'ts-appointment'); ?></label></th>
                            <td><input type="text" name="appointment_type" id="ts-edit-appointment-type" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="ts-edit-appointment-status"><?php echo esc_html__('Statut', 'ts-appointment'); ?></label></th>
                            <td>
                                <select name="status" id="ts-edit-appointment-status">
                                    <option value="pending"><?php echo esc_html__('En attente', 'ts-appointment'); ?></option>
                                    <option value="confirmed"><?php echo esc_html__('Confirmé', 'ts-appointment'); ?></option>
                                    <option value="completed"><?php echo esc_html__('Complété', 'ts-appointment'); ?></option>
                                    <option value="cancelled"><?php echo esc_html__('Annulé', 'ts-appointment'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <?php if (is_array($form_schema)) :
                            foreach ($form_schema as $f) :
                                $key = esc_attr($f['key'] ?? '');
                                $label = esc_html($f['label'] ?? $key);
                                $type = $f['type'] ?? 'text';
                                if (empty($key)) continue;
                        ?>
                        <tr>
                            <th><label for="ts-field-<?php echo $key; ?>"><?php echo $label; ?></label></th>
                            <td>
                                <?php if ($type === 'textarea') : ?>
                                    <textarea name="<?php echo $key; ?>" id="ts-field-<?php echo $key; ?>" rows="4" class="large-text"></textarea>
                                <?php elseif ($type === 'select') : ?>
                                    <select name="<?php echo $key; ?>" id="ts-field-<?php echo $key; ?>"></select>
                                <?php else : ?>
                                    <input type="text" name="<?php echo $key; ?>" id="ts-field-<?php echo $key; ?>" class="regular-text">
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
                <p>
                    <button class="button button-primary" type="submit"><?php echo esc_html__('Enregistrer', 'ts-appointment'); ?></button>
                    <button class="button" type="button" id="ts-edit-cancel"><?php echo esc_html__('Annuler', 'ts-appointment'); ?></button>
                </p>
            </form>
        </div>

        <script>
        (function(){
            var $ = window.jQuery;
            $(document).on('click', '.edit-appointment', function(e){
                e.preventDefault();
                var btn = $(this);
                var id = btn.data('id');
                var client = {};
                try { client = JSON.parse(btn.attr('data-client') || '{}'); } catch(err) { client = {}; }
                $('#ts-edit-appointment-id').val(id);
                $('#ts-edit-appointment-date').val(btn.data('date'));
                $('#ts-edit-appointment-time').val(btn.data('time'));
                $('#ts-edit-appointment-type').val(btn.data('type'));
                $('#ts-edit-appointment-status').val(btn.data('status'));
                // populate form_schema fields
                <?php if (is_array($form_schema)) : foreach ($form_schema as $f) : $k = esc_js($f['key']); ?>
                    if (typeof client['<?php echo $k; ?>'] !== 'undefined') {
                        $('#ts-field-<?php echo $k; ?>').val(client['<?php echo $k; ?>']);
                    } else {
                        $('#ts-field-<?php echo $k; ?>').val('');
                    }
                <?php endforeach; endif; ?>
                $('#ts-edit-appointment-modal').show();
            });
            $('#ts-edit-cancel').on('click', function(){ $('#ts-edit-appointment-modal').hide(); });
        })();
        </script>
    <?php endif; ?>
</div>
