<div class="wrap ts-appointment-admin">
    <h1><?php _e('Lieux', 'ts-appointment'); ?></h1>
    <p><?php _e('Ajoutez vos lieux et précisez les obligations d’adresse.', 'ts-appointment'); ?></p>

    <form method="post" class="ts-locations-form">
        <?php wp_nonce_field('ts_appointment_locations', 'ts_appointment_nonce'); ?>
        <?php if (!empty($edit_loc)): ?>
            <input type="hidden" name="action_type" value="edit" />
            <input type="hidden" name="loc_key" value="<?php echo esc_attr($edit_loc['key']); ?>" />
        <?php else: ?>
            <input type="hidden" name="action_type" value="add" />
        <?php endif; ?>
        <table class="form-table">
            <tr>
                <th><label for="loc_label"><?php _e('Nom du lieu', 'ts-appointment'); ?></label></th>
                <td><input type="text" id="loc_label" name="loc_label" class="regular-text" required value="<?php echo !empty($edit_loc) ? esc_attr($edit_loc['label']) : ''; ?>"></td>
            </tr>
            <tr>
                <th><label for="loc_key"><?php _e('Slug (unique)', 'ts-appointment'); ?></label></th>
                <td>
                    <?php if (!empty($edit_loc)): ?>
                        <input type="text" id="loc_key" name="loc_key" class="regular-text" value="<?php echo esc_attr($edit_loc['key']); ?>" readonly>
                    <?php else: ?>
                        <input type="text" id="loc_key" name="loc_key" class="regular-text" placeholder="on_site, remote, home">
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label for="loc_icon"><?php _e('Icône (emoji ou texte)', 'ts-appointment'); ?></label></th>
                <td><input type="text" id="loc_icon" name="loc_icon" class="regular-text" placeholder="📍" value="<?php echo !empty($edit_loc) ? esc_attr($edit_loc['icon'] ?? '📍') : '📍'; ?>"></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Options', 'ts-appointment'); ?></th>
                <td>
                    <label><input type="checkbox" name="loc_show_business" value="1"> <?php _e('Afficher l’adresse de l’entreprise pour ce lieu', 'ts-appointment'); ?></label><br>
                    <label><input type="checkbox" name="loc_require_client" value="1"> <?php _e('Demander l’adresse du client', 'ts-appointment'); ?></label>
                </td>
            </tr>
            <tr>
                <th><label for="loc_note"><?php _e('Note (affichée aux clients)', 'ts-appointment'); ?></label></th>
                <td>
                    <?php
                    $note_content = !empty($edit_loc) ? ($edit_loc['note'] ?? '') : '';
                    $editor_settings = array(
                        'textarea_name' => 'loc_note',
                        'textarea_rows' => 6,
                        'media_buttons' => false,
                        'teeny' => true,
                    );
                    wp_editor($note_content, 'loc_note_editor', $editor_settings);
                    ?>
                    <p class="description"><?php echo esc_html__('Ex: Apportez votre carte d’identité', 'ts-appointment'); ?></p>
                </td>
            </tr>
        </table>
        <p><button type="submit" class="button button-primary"><?php _e('Ajouter un lieu', 'ts-appointment'); ?></button></p>
    </form>

    <hr />

    <h2><?php _e('Liste des lieux', 'ts-appointment'); ?></h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th><?php _e('Nom', 'ts-appointment'); ?></th>
                <th><?php _e('Slug', 'ts-appointment'); ?></th>
                <th><?php _e('Icône', 'ts-appointment'); ?></th>
                <th><?php _e('Adresse entreprise', 'ts-appointment'); ?></th>
                <th><?php _e('Adresse client requise', 'ts-appointment'); ?></th>
                <th><?php _e('Note', 'ts-appointment'); ?></th>
                <th><?php _e('Actions', 'ts-appointment'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($locations)) : ?>
                <?php foreach ($locations as $loc) : ?>
                    <tr>
                        <td><?php echo esc_html($loc['label']); ?></td>
                        <td><?php echo esc_html($loc['key']); ?></td>
                        <td><?php echo !empty($loc['icon']) ? esc_html($loc['icon']) : '📍'; ?></td>
                        <td><?php echo !empty($loc['showBusinessAddress']) ? __('Oui', 'ts-appointment') : __('Non', 'ts-appointment'); ?></td>
                        <td><?php echo !empty($loc['requireClientAddress']) ? __('Oui', 'ts-appointment') : __('Non', 'ts-appointment'); ?></td>
                        <td><?php echo !empty($loc['note']) ? wp_kses_post($loc['note']) : ''; ?></td>
                        <td>
                            <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=ts-appointment-locations&edit_loc_key=' . urlencode($loc['key']))); ?>"><?php _e('Modifier', 'ts-appointment'); ?></a>
                            <form method="post" style="display:inline;">
                                <?php wp_nonce_field('ts_appointment_locations', 'ts_appointment_nonce'); ?>
                                <input type="hidden" name="action_type" value="delete" />
                                <input type="hidden" name="loc_key" value="<?php echo esc_attr($loc['key']); ?>" />
                                <button type="submit" class="button button-small button-link-delete" onclick="return confirm('<?php echo esc_js(__('Supprimer ce lieu ?', 'ts-appointment')); ?>');"><?php _e('Supprimer', 'ts-appointment'); ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="7"><?php _e('Aucun lieu pour le moment.', 'ts-appointment'); ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
