<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ts-appointment-admin">
    <h1><?php _e('Services', 'ts-appointment'); ?></h1>
    <p><?php _e('Gérez vos services, tarifs et durées.', 'ts-appointment'); ?></p>

    <form method="post" class="ts-service-form">
        <?php wp_nonce_field('ts_appointment_services', 'ts_appointment_nonce'); ?>
        <?php if (!empty($edit_service)): ?>
            <input type="hidden" name="action_type" value="edit" />
            <input type="hidden" name="service_id" value="<?php echo intval($edit_service->id); ?>" />
        <?php else: ?>
            <input type="hidden" name="action_type" value="add" />
        <?php endif; ?>
        <table class="form-table">
            <tr>
                <th><label for="service_name"><?php _e('Nom', 'ts-appointment'); ?></label></th>
                <td><input type="text" id="service_name" name="service_name" class="regular-text" required value="<?php echo !empty($edit_service) ? esc_attr($edit_service->name) : ''; ?>"></td>
            </tr>
            <tr>
                <th><label for="service_description"><?php _e('Description', 'ts-appointment'); ?></label></th>
                <td><textarea id="service_description" name="service_description" class="large-text" rows="3"><?php echo !empty($edit_service) ? esc_textarea($edit_service->description) : ''; ?></textarea></td>
            </tr>
            <tr>
                <th><label for="service_duration"><?php _e('Durée (minutes)', 'ts-appointment'); ?></label></th>
                <td><input type="number" id="service_duration" name="service_duration" min="5" step="5" value="<?php echo !empty($edit_service) ? intval($edit_service->duration) : 60; ?>"></td>
            </tr>
            <tr>
                <th><?php _e('Prix par lieu', 'ts-appointment'); ?></th>
                <td>
                    <?php
                    $currency_symbol = get_option('ts_appointment_currency_symbol', '€');
                    $locations_json = get_option('ts_appointment_locations_config');
                    $locations = json_decode($locations_json, true);
                    if (!empty($locations) && is_array($locations)) {
                        echo '<table class="widefat"><tbody>';
                        foreach ($locations as $loc) {
                            $loc_key = esc_attr($loc['key']);
                            $loc_label = esc_html($loc['label']);
                            $value = 0;
                            if (!empty($edit_service)) {
                                $prices = json_decode($edit_service->price, true);
                                if (is_array($prices) && isset($prices[$loc_key])) {
                                    $value = $prices[$loc_key];
                                }
                            }
                            echo '<tr>';
                            echo '<td style="width:40%;">' . $loc_label . '</td>';
                            echo '<td><input type="number" name="price_by_location[' . $loc_key . ']" min="0" step="0.01" value="' . esc_attr($value) . '" style="width:120px;"> ' . esc_html($currency_symbol) . '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                    } else {
                        echo '<p class="description">' . __('Aucun lieu défini. Allez dans Lieux pour en ajouter.', 'ts-appointment') . '</p>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Actif', 'ts-appointment'); ?></th>
                <td>
                    <label><input type="checkbox" name="service_active" value="1" <?php echo !empty($edit_service) && $edit_service->active ? 'checked' : (!isset($edit_service) ? 'checked' : ''); ?>> <?php _e('Disponible à la réservation', 'ts-appointment'); ?></label>
                </td>
            </tr>
        </table>
        <p><button type="submit" class="button button-primary"><?php echo !empty($edit_service) ? __('Enregistrer les modifications', 'ts-appointment') : __('Ajouter un service', 'ts-appointment'); ?></button></p>
    </form>

    <hr />

    <h2><?php _e('Liste des services', 'ts-appointment'); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Nom', 'ts-appointment'); ?></th>
                <th><?php _e('Durée', 'ts-appointment'); ?></th>
                <th><?php _e('Prix par lieu', 'ts-appointment'); ?></th>
                <th><?php _e('Actif', 'ts-appointment'); ?></th>
                <th><?php _e('Actions', 'ts-appointment'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($services)) : ?>
                <?php foreach ($services as $service) : ?>
                    <tr>
                        <td><?php echo esc_html($service->name); ?></td>
                        <td><?php echo intval($service->duration); ?> <?php _e('min', 'ts-appointment'); ?></td>
                        <td>
                            <?php
                            $currency_symbol = get_option('ts_appointment_currency_symbol', '€');
                            $currency_position = get_option('ts_appointment_currency_position', 'right');
                            $prices = json_decode($service->price, true);
                            if (is_array($prices) && !empty($prices)) {
                                $parts = array();
                                foreach ($prices as $loc_key => $price_val) {
                                    $formatted_price = number_format_i18n(floatval($price_val), 2);
                                    $price_display = $currency_position === 'left' ? $currency_symbol . ' ' . $formatted_price : $formatted_price . ' ' . $currency_symbol;
                                    $parts[] = esc_html($loc_key) . ': ' . $price_display;
                                }
                                echo implode('<br>', $parts);
                            } else {
                                $formatted_price = number_format_i18n(floatval($service->price), 2);
                                $price_display = $currency_position === 'left' ? $currency_symbol . ' ' . $formatted_price : $formatted_price . ' ' . $currency_symbol;
                                echo $price_display;
                            }
                            ?>
                        </td>
                        <td><?php echo $service->active ? __('Oui', 'ts-appointment') : __('Non', 'ts-appointment'); ?></td>
                        <td>
                            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=ts-appointment-services&edit_service_id=' . intval($service->id))); ?>"><?php _e('Modifier', 'ts-appointment'); ?></a>
                            <form method="post" style="display:inline;">
                                <?php wp_nonce_field('ts_appointment_services', 'ts_appointment_nonce'); ?>
                                <input type="hidden" name="action_type" value="duplicate" />
                                <input type="hidden" name="service_id" value="<?php echo intval($service->id); ?>" />
                                <button type="submit" class="button" title="<?php echo esc_attr__('Dupliquer', 'ts-appointment'); ?>"><?php _e('Dupliquer', 'ts-appointment'); ?></button>
                            </form>
                            <form method="post" style="display:inline; margin-left:6px;">
                                <?php wp_nonce_field('ts_appointment_services', 'ts_appointment_nonce'); ?>
                                <input type="hidden" name="action_type" value="delete" />
                                <input type="hidden" name="service_id" value="<?php echo intval($service->id); ?>" />
                                <button type="submit" class="button button-small button-link-delete" onclick="return confirm('<?php echo esc_js(__('Supprimer ce service ?', 'ts-appointment')); ?>');"><?php _e('Supprimer', 'ts-appointment'); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="5"><?php _e('Aucun service pour le moment.', 'ts-appointment'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
