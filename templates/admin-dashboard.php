<div class="ts-appointment-dashboard">
    <div class="dashboard-header">
        <h1><?php echo esc_html__('Tableau de bord', 'ts-appointment'); ?></h1>
    </div>

    <div class="dashboard-stats">
        <div class="stat-card">
            <div class="stat-number"><?php echo count($appointments); ?></div>
            <div class="stat-label"><?php echo esc_html__('Rendez-vous récents', 'ts-appointment'); ?></div>
        </div>
        <div class="stat-card highlight">
            <div class="stat-number"><?php echo $pending_count; ?></div>
            <div class="stat-label"><?php echo esc_html__('En attente', 'ts-appointment'); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $confirmed_count; ?></div>
            <div class="stat-label"><?php echo esc_html__('Confirmés', 'ts-appointment'); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $completed_count; ?></div>
            <div class="stat-label"><?php echo esc_html__('Complétés', 'ts-appointment'); ?></div>
        </div>
    </div>

    <div class="dashboard-content">
        <div class="appointments-list">
            <h2><?php echo esc_html__('Prochains rendez-vous', 'ts-appointment'); ?></h2>
            
            <?php if (empty($appointments)) : ?>
                <p><?php echo esc_html__('Aucun rendez-vous', 'ts-appointment'); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Client', 'ts-appointment'); ?></th>
                            <th><?php echo esc_html__('Service', 'ts-appointment'); ?></th>
                            <th><?php echo esc_html__('Date/Heure', 'ts-appointment'); ?></th>
                            <th><?php echo esc_html__('Type', 'ts-appointment'); ?></th>
                            <th><?php echo esc_html__('Statut', 'ts-appointment'); ?></th>
                            <th><?php echo esc_html__('Actions', 'ts-appointment'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appointment) :
                            $service = TS_Appointment_Database::get_service($appointment->service_id);
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
                                <td><?php echo esc_html(TS_Appointment_Email::get_client_value($appointment, 'client_name')); ?></td>
                                <td><?php echo esc_html($service->name); ?></td>
                                <td><?php echo esc_html(date_i18n('j/m/Y H:i', strtotime($appointment->appointment_date . ' ' . $appointment->appointment_time))); ?></td>
                                <td><?php echo esc_html(isset($type_labels[$appointment->appointment_type]) ? $type_labels[$appointment->appointment_type] : $appointment->appointment_type); ?></td>
                                <td><span class="status-badge status-<?php echo esc_attr($appointment->status); ?>"><?php echo esc_html(isset($status_labels[$appointment->status]) ? $status_labels[$appointment->status] : $appointment->status); ?></span></td>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=ts-appointment-list&action=edit&id=' . $appointment->id)); ?>" class="button button-small"><?php echo esc_html__('Voir', 'ts-appointment'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
