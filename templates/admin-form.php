<div class="wrap ts-appointment-admin">
    <h1><?php _e('Formulaire', 'ts-appointment'); ?></h1>
    <p><?php _e('Ajoutez des champs au formulaire de réservation.', 'ts-appointment'); ?></p>

    <form method="post" class="ts-form-builder">
        <?php wp_nonce_field('ts_appointment_form', 'ts_appointment_nonce'); ?>
        <?php if (!empty($edit_field)): ?>
            <input type="hidden" name="action_type" value="edit" />
            <input type="hidden" name="field_key" value="<?php echo esc_attr($edit_field['key']); ?>" />
        <?php else: ?>
            <input type="hidden" name="action_type" value="add" />
        <?php endif; ?>
        <table class="form-table">
            <tr>
                <th><label for="field_label"><?php _e('Label', 'ts-appointment'); ?></label></th>
                <td><input type="text" id="field_label" name="field_label" class="regular-text" required value="<?php echo !empty($edit_field) ? esc_attr($edit_field['label']) : ''; ?>"></td>
            </tr>
            <tr>
                <th><label for="field_key"><?php _e('Slug (unique)', 'ts-appointment'); ?></label></th>
                <td>
                    <?php if (!empty($edit_field)): ?>
                        <input type="text" id="field_key" name="field_key" class="regular-text" value="<?php echo esc_attr($edit_field['key']); ?>" readonly>
                    <?php else: ?>
                        <input type="text" id="field_key" name="field_key" class="regular-text" placeholder="client_name, client_email" >
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label for="field_type"><?php _e('Type', 'ts-appointment'); ?></label></th>
                <td>
                    <select id="field_type" name="field_type">
                        <?php $ft = !empty($edit_field) ? $edit_field['type'] : 'text'; ?>
                        <option value="text" <?php echo $ft === 'text' ? 'selected' : ''; ?>><?php _e('Texte', 'ts-appointment'); ?></option>
                        <option value="email" <?php echo $ft === 'email' ? 'selected' : ''; ?>><?php _e('Email', 'ts-appointment'); ?></option>
                        <option value="tel" <?php echo $ft === 'tel' ? 'selected' : ''; ?>><?php _e('Téléphone', 'ts-appointment'); ?></option>
                        <option value="number" <?php echo $ft === 'number' ? 'selected' : ''; ?>><?php _e('Nombre', 'ts-appointment'); ?></option>
                        <option value="date" <?php echo $ft === 'date' ? 'selected' : ''; ?>><?php _e('Date', 'ts-appointment'); ?></option>
                        <option value="time" <?php echo $ft === 'time' ? 'selected' : ''; ?>><?php _e('Heure', 'ts-appointment'); ?></option>
                        <option value="textarea" <?php echo $ft === 'textarea' ? 'selected' : ''; ?>><?php _e('Zone de texte', 'ts-appointment'); ?></option>
                        <option value="select" <?php echo $ft === 'select' ? 'selected' : ''; ?>><?php _e('Liste déroulante', 'ts-appointment'); ?></option>
                        <option value="checkbox" <?php echo $ft === 'checkbox' ? 'selected' : ''; ?>><?php _e('Case à cocher', 'ts-appointment'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Obligatoire', 'ts-appointment'); ?></th>
                <td><label><input type="checkbox" name="field_required" value="1" <?php echo (!empty($edit_field) && !empty($edit_field['required'])) ? 'checked' : ''; ?>> <?php _e('Ce champ est requis', 'ts-appointment'); ?></label></td>
            </tr>
            <tr>
                <th><label for="field_options"><?php _e('Options (pour liste déroulante)', 'ts-appointment'); ?></label></th>
                <td><input type="text" id="field_options" name="field_options" class="regular-text" placeholder="Option 1, Option 2" value="<?php echo !empty($edit_field) && !empty($edit_field['options']) ? esc_attr(implode(', ', $edit_field['options'])) : ''; ?>"></td>
            </tr>
            <tr>
                <th><label for="field_visible_locations"><?php _e('Visibilité par lieu', 'ts-appointment'); ?></label></th>
                <td>
                    <?php
                    $locs_json = get_option('ts_appointment_locations_config');
                    $locs = json_decode($locs_json, true);
                    $visible = !empty($edit_field) && !empty($edit_field['visible_locations']) && is_array($edit_field['visible_locations']) ? $edit_field['visible_locations'] : array();
                    if (is_array($locs) && count($locs)) {
                        echo '<select id="field_visible_locations" name="field_visible_locations[]" multiple style="min-width:250px;">';
                        foreach ($locs as $l) {
                            $k = isset($l['key']) ? $l['key'] : '';
                            $lab = isset($l['label']) ? $l['label'] : $k;
                            $sel = in_array($k, $visible, true) ? 'selected' : '';
                            echo '<option value="' . esc_attr($k) . '" ' . $sel . '>' . esc_html($lab) . '</option>';
                        }
                        echo '</select>';
                        echo '<p class="description">' . esc_html__('Laissez vide pour afficher ce champ pour tous les lieux.', 'ts-appointment') . '</p>';
                    } else {
                        echo '<p class="description">' . esc_html__('Aucun lieu configuré.', 'ts-appointment') . '</p>';
                    }
                    ?>
                </td>
            </tr>
        </table>
        <p><button type="submit" class="button button-primary"><?php echo !empty($edit_field) ? __('Enregistrer les modifications', 'ts-appointment') : __('Ajouter un champ', 'ts-appointment'); ?></button></p>
    </form>

    <hr />

    <h2><?php _e('Champs existants', 'ts-appointment'); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Label', 'ts-appointment'); ?></th>
                <th><?php _e('Slug', 'ts-appointment'); ?></th>
                <th><?php _e('Type', 'ts-appointment'); ?></th>
                <th><?php _e('Obligatoire', 'ts-appointment'); ?></th>
                <th><?php _e('Options', 'ts-appointment'); ?></th>
                <th><?php _e('Actions', 'ts-appointment'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($form_fields)) : ?>
                <?php foreach ($form_fields as $field) : ?>
                    <tr>
                        <td><?php echo esc_html($field['label']); ?></td>
                        <td><?php echo esc_html($field['key']); ?></td>
                        <td><?php echo esc_html($field['type']); ?></td>
                        <td><?php echo !empty($field['required']) ? __('Oui', 'ts-appointment') : __('Non', 'ts-appointment'); ?></td>
                        <td>
                            <?php
                            if (isset($field['options']) && is_array($field['options']) && !empty($field['options'])) {
                                echo esc_html(implode(', ', $field['options']));
                            } else {
                                echo '—';
                            }
                            ?>
                        </td>
                        <td>
                            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=ts-appointment-form&edit_field_key=' . urlencode($field['key']))); ?>"><?php _e('Modifier', 'ts-appointment'); ?></a>
                            <form method="post" style="display:inline;">
                                <?php wp_nonce_field('ts_appointment_form', 'ts_appointment_nonce'); ?>
                                <input type="hidden" name="action_type" value="delete" />
                                <input type="hidden" name="field_key" value="<?php echo esc_attr($field['key']); ?>" />
                                <button type="submit" class="button button-small button-link-delete" onclick="return confirm('<?php echo esc_js(__('Supprimer ce champ ?', 'ts-appointment')); ?>');"><?php _e('Supprimer', 'ts-appointment'); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="6"><?php _e('Aucun champ pour le moment.', 'ts-appointment'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
