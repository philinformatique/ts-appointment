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
                    $client_data = array();
                    if (!empty($appointment->client_data)) {
                        $tmp = json_decode($appointment->client_data, true);
                        if (is_array($tmp)) $client_data = $tmp;
                    }
                    $client_name = $client_data['client_name'] ?? '';
                    $client_email = $client_data['client_email'] ?? '';
                    $client_phone = $client_data['client_phone'] ?? '';
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
                        <td><?php echo esc_html($client_name); ?></td>
                        <td><?php echo esc_html($client_email); ?></td>
                        <td><?php echo esc_html($client_phone); ?></td>
                        <td><?php echo esc_html(date_i18n('j/m/Y H:i', strtotime($appointment->appointment_date . ' ' . $appointment->appointment_time))); ?></td>
                        <td><?php echo esc_html(isset($type_labels[$appointment->appointment_type]) ? $type_labels[$appointment->appointment_type] : $appointment->appointment_type); ?></td>
                        <td><span class="status-badge status-<?php echo esc_attr($appointment->status); ?>"><?php echo esc_html(isset($status_labels[$appointment->status]) ? $status_labels[$appointment->status] : $appointment->status); ?></span></td>
                        <td>
                            <a href="#" class="button button-small view-appointment" data-id="<?php echo esc_attr($appointment->id); ?>"><?php echo esc_html__('Voir', 'ts-appointment'); ?></a>
                            <a href="<?php echo esc_url(add_query_arg(array('page'=>'ts-appointment-list','action'=>'edit_appointment','id'=>$appointment->id), admin_url('admin.php'))); ?>" class="button button-small edit-appointment"><?php echo esc_html__('Modifier', 'ts-appointment'); ?></a>
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
