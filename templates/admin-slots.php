<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ts-appointment-admin">
    <h1><?php _e('Créneaux', 'ts-appointment'); ?></h1>
    <p><?php _e('Planifiez les créneaux disponibles par jour ; ils s\'appliquent à tous les services.', 'ts-appointment'); ?></p>

    <form method="post" class="ts-slot-form">
        <?php wp_nonce_field('ts_appointment_slots', 'ts_appointment_nonce'); ?>
        <?php if (!empty($edit_slot)): ?>
            <input type="hidden" name="action_type" value="edit" />
            <input type="hidden" name="slot_id" value="<?php echo intval($edit_slot->id); ?>" />
        <?php else: ?>
            <input type="hidden" name="action_type" value="add" />
        <?php endif; ?>
        <table class="form-table">
            <!-- Les créneaux sont globaux et s'appliquent à tous les services -->
            <tr>
                <th><label><?php _e('Jours de la semaine', 'ts-appointment'); ?></label></th>
                <td>
                    <?php
                        $selected_days = array();
                        if (!empty($edit_slot)) { $selected_days[] = intval($edit_slot->day_of_week); }
                    ?>
                    <label style="margin-right:10px;"><input type="checkbox" name="slot_days[]" value="1" <?php echo in_array(1, $selected_days) ? 'checked' : ''; ?>> <?php _e('Lundi', 'ts-appointment'); ?></label>
                    <label style="margin-right:10px;"><input type="checkbox" name="slot_days[]" value="2" <?php echo in_array(2, $selected_days) ? 'checked' : ''; ?>> <?php _e('Mardi', 'ts-appointment'); ?></label>
                    <label style="margin-right:10px;"><input type="checkbox" name="slot_days[]" value="3" <?php echo in_array(3, $selected_days) ? 'checked' : ''; ?>> <?php _e('Mercredi', 'ts-appointment'); ?></label>
                    <label style="margin-right:10px;"><input type="checkbox" name="slot_days[]" value="4" <?php echo in_array(4, $selected_days) ? 'checked' : ''; ?>> <?php _e('Jeudi', 'ts-appointment'); ?></label>
                    <label style="margin-right:10px;"><input type="checkbox" name="slot_days[]" value="5" <?php echo in_array(5, $selected_days) ? 'checked' : ''; ?>> <?php _e('Vendredi', 'ts-appointment'); ?></label>
                    <label style="margin-right:10px;"><input type="checkbox" name="slot_days[]" value="6" <?php echo in_array(6, $selected_days) ? 'checked' : ''; ?>> <?php _e('Samedi', 'ts-appointment'); ?></label>
                    <label style="margin-right:10px;"><input type="checkbox" name="slot_days[]" value="0" <?php echo in_array(0, $selected_days) ? 'checked' : ''; ?>> <?php _e('Dimanche', 'ts-appointment'); ?></label>
                    <p class="description"><?php _e('Sélectionnez un ou plusieurs jours.', 'ts-appointment'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="slot_start_time"><?php _e('Heure de début', 'ts-appointment'); ?></label></th>
                <td>
                    <input type="time" id="slot_start_time" name="slot_start_time" value="<?php echo !empty($edit_slot) ? esc_attr($edit_slot->start_time) : '08:00'; ?>" step="300">
                </td>
            </tr>
            <tr>
                <th><label for="slot_end_time"><?php _e('Heure de fin', 'ts-appointment'); ?></label></th>
                <td>
                    <input type="time" id="slot_end_time" name="slot_end_time" value="<?php echo !empty($edit_slot) ? esc_attr($edit_slot->end_time) : '20:00'; ?>" step="300">
                    <p class="description"><?php _e('Les créneaux générés seront compris entre ces deux heures.', 'ts-appointment'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="slot_duration"><?php _e('Durée d\'un rendez-vous (minutes)', 'ts-appointment'); ?></label></th>
                <td>
                    <input type="number" id="slot_duration" name="slot_duration" min="5" step="5" value="<?php echo !empty($edit_slot) ? intval($edit_slot->duration ?? 60) : 60; ?>" class="small-text">
                    <p class="description"><?php _e('Ex: 60 pour 1h. La plage horaire respecte l\'heure de début et de fin définies ci-dessus.', 'ts-appointment'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="slot_interval"><?php _e('Interval (minutes)', 'ts-appointment'); ?></label></th>
                <td>
                    <select id="slot_interval" name="slot_interval" class="small-text">
                        <option value="15"><?php _e('15 minutes', 'ts-appointment'); ?></option>
                        <option value="30"><?php _e('30 minutes', 'ts-appointment'); ?></option>
                        <option value="45"><?php _e('45 minutes', 'ts-appointment'); ?></option>
                        <option value="60" <?php echo (empty($edit_slot) || (isset($edit_slot->slot_interval) && intval($edit_slot->slot_interval) === 60)) ? 'selected' : ''; ?>><?php _e('60 minutes (1 heure)', 'ts-appointment'); ?></option>
                        <option value="90"><?php _e('90 minutes', 'ts-appointment'); ?></option>
                        <option value="120"><?php _e('120 minutes (2 heures)', 'ts-appointment'); ?></option>
                    </select>
                    <p class="description"><?php _e('Intervalle d\'affichage des créneaux. Ex: 30 min affichera 10:00, 10:30, 11:00, 11:30...', 'ts-appointment'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Actif', 'ts-appointment'); ?></th>
                <td>
                    <label><input type="checkbox" name="slot_active" value="1" <?php echo (!empty($edit_slot) && $edit_slot->active) || empty($edit_slot) ? 'checked' : ''; ?>> <?php _e('Disponible', 'ts-appointment'); ?></label>
                </td>
            </tr>
        </table>
        <p><button type="submit" class="button button-primary"><?php echo !empty($edit_slot) ? __('Enregistrer les modifications', 'ts-appointment') : __('Ajouter un créneau', 'ts-appointment'); ?></button></p>
    </form>

    <hr />

    <h2><?php _e('Créneaux existants', 'ts-appointment'); ?></h2>

    <form method="post" class="ts-slots-bulk-form">
        <?php wp_nonce_field('ts_appointment_slots', 'ts_appointment_nonce'); ?>
        <input type="hidden" name="action_type" value="bulk">
        <div style="margin-bottom:10px; display:flex; gap:8px; align-items:center;">
            <select name="bulk_action" style="min-width:140px;">
                <option value=""><?php echo esc_html__('Action en masse', 'ts-appointment'); ?></option>
                <option value="delete"><?php echo esc_html__('Supprimer', 'ts-appointment'); ?></option>
                <option value="activate"><?php echo esc_html__('Activer', 'ts-appointment'); ?></option>
                <option value="deactivate"><?php echo esc_html__('Désactiver', 'ts-appointment'); ?></option>
                <option value="set_interval"><?php echo esc_html__('Définir interval (min)', 'ts-appointment'); ?></option>
            </select>
            <input type="number" name="bulk_interval" min="5" step="5" placeholder="Interval (min)" class="small-text">
            <button type="submit" class="button button-secondary"><?php echo esc_html__('Appliquer', 'ts-appointment'); ?></button>
        </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><input type="checkbox" id="ts_slot_select_all"></th>
                <th><?php _e('Jour', 'ts-appointment'); ?></th>
                <th><?php _e('Plage', 'ts-appointment'); ?></th>
                <th><?php _e('Interval', 'ts-appointment'); ?></th>
                <th><?php _e('Actif', 'ts-appointment'); ?></th>
                <th><?php _e('Actions', 'ts-appointment'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($slots)): ?>
                <?php
                $days = array(
                    0 => __('Dimanche', 'ts-appointment'),
                    1 => __('Lundi', 'ts-appointment'),
                    2 => __('Mardi', 'ts-appointment'),
                    3 => __('Mercredi', 'ts-appointment'),
                    4 => __('Jeudi', 'ts-appointment'),
                    5 => __('Vendredi', 'ts-appointment'),
                    6 => __('Samedi', 'ts-appointment'),
                );
                $service_names = array();
                foreach ($services as $srv) { $service_names[$srv->id] = $srv->name; }
                ?>
                <?php foreach ($slots as $slot): ?>
                    <tr>
                        <td><input type="checkbox" name="slot_ids[]" value="<?php echo intval($slot->id); ?>"></td>
                        <td><?php echo esc_html($days[intval($slot->day_of_week)] ?? $slot->day_of_week); ?></td>
                        <td><?php echo esc_html($slot->start_time . ' - ' . $slot->end_time); ?></td>
                        <td><?php echo esc_html((isset($slot->slot_interval) ? intval($slot->slot_interval) : 60) . ' min'); ?></td>
                        <td><?php echo $slot->active ? __('Oui', 'ts-appointment') : __('Non', 'ts-appointment'); ?></td>
                        <td>
                            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=ts-appointment-slots&edit_slot_id=' . intval($slot->id))); ?>"><?php _e('Modifier', 'ts-appointment'); ?></a>
                            <form method="post" style="display:inline">
                                <?php wp_nonce_field('ts_appointment_slots', 'ts_appointment_nonce'); ?>
                                <input type="hidden" name="action_type" value="delete">
                                <input type="hidden" name="slot_id" value="<?php echo esc_attr($slot->id); ?>">
                                <button type="submit" class="button button-link-delete" onclick="return confirm('<?php echo esc_js(__('Supprimer ce créneau ?', 'ts-appointment')); ?>');"><?php _e('Supprimer', 'ts-appointment'); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6"><?php _e('Aucun créneau configuré pour le moment.', 'ts-appointment'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </form>

    <script>
    (function(){
        const selectAll = document.getElementById('ts_slot_select_all');
        if (!selectAll) return;
        selectAll.addEventListener('change', function(){
            const checked = this.checked;
            document.querySelectorAll('input[name="slot_ids[]"]').forEach(cb => cb.checked = checked);
        });
    })();
    </script>
</div>