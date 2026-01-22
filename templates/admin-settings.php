<div class="ts-appointment-admin-settings">
    <div class="settings-header">
        <h1><?php echo esc_html__('Paramètres', 'ts-appointment'); ?></h1>
    </div>

    <form method="post" class="settings-form">
        <?php wp_nonce_field('ts_appointment_settings', 'ts_appointment_nonce'); ?>

        <div class="settings-section">
            <h2><?php echo esc_html__('Informations entreprise', 'ts-appointment'); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="business_name"><?php echo esc_html__('Nom', 'ts-appointment'); ?></label></th>
                    <td>
                        <input type="text" id="business_name" name="business_name" value="<?php echo esc_attr(get_option('ts_appointment_business_name')); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="business_email"><?php echo esc_html__('Email', 'ts-appointment'); ?></label></th>
                    <td>
                        <input type="email" id="business_email" name="business_email" value="<?php echo esc_attr(get_option('ts_appointment_business_email')); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="business_phone"><?php echo esc_html__('Téléphone', 'ts-appointment'); ?></label></th>
                    <td>
                        <input type="tel" id="business_phone" name="business_phone" value="<?php echo esc_attr(get_option('ts_appointment_business_phone')); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="business_address"><?php echo esc_html__('Adresse', 'ts-appointment'); ?></label></th>
                    <td>
                        <textarea id="business_address" name="business_address" class="large-text code" rows="3"><?php echo esc_textarea(get_option('ts_appointment_business_address')); ?></textarea>
                    </td>
                </tr>
            </table>
        </div>

        <div class="settings-section">
            <h2><?php echo esc_html__('Paramètres rendez-vous', 'ts-appointment'); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="timezone"><?php echo esc_html__('Fuseau horaire', 'ts-appointment'); ?></label></th>
                    <td>
                        <select id="timezone" name="timezone" class="regular-text">
                            <?php
                            $timezones = timezone_identifiers_list();
                            $current = get_option('ts_appointment_timezone', get_option('timezone_string'));
                            foreach ($timezones as $tz) {
                                echo '<option value="' . esc_attr($tz) . '"' . selected($current, $tz) . '>' . esc_html($tz) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="max_days_ahead"><?php echo esc_html__('Jours max à l\'avance', 'ts-appointment'); ?></label></th>
                    <td>
                        <input type="number" id="max_days_ahead" name="max_days_ahead" value="<?php echo esc_attr(get_option('ts_appointment_max_days_ahead', 30)); ?>" class="small-text">
                    </td>
                </tr>
                <!-- 'Délai entre rendez-vous' removed per request -->
                <tr>
                    <th scope="row"><label for="date_format"><?php echo esc_html__('Format de date', 'ts-appointment'); ?></label></th>
                    <td>
                        <input type="text" id="date_format" name="date_format" value="<?php echo esc_attr(get_option('ts_appointment_date_format', 'j/m/Y')); ?>" class="regular-text">
                        <p class="description"><?php echo esc_html__('Exemples : j/m/Y, d/m/Y, Y-m-d', 'ts-appointment'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="time_format"><?php echo esc_html__('Format d\'heure', 'ts-appointment'); ?></label></th>
                    <td>
                        <input type="text" id="time_format" name="time_format" value="<?php echo esc_attr(get_option('ts_appointment_time_format', 'H:i')); ?>" class="regular-text">
                        <p class="description"><?php echo esc_html__('Exemples : H:i (24h), g:i A (12h)', 'ts-appointment'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="settings-section">
            <h2><?php echo esc_html__('Apparence', 'ts-appointment'); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="color_primary"><?php echo esc_html__('Couleur primaire', 'ts-appointment'); ?></label></th>
                    <td>
                        <input type="color" id="color_primary" name="color_primary" value="<?php echo esc_attr(get_option('ts_appointment_color_primary', '#007cba')); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="color_secondary"><?php echo esc_html__('Couleur secondaire', 'ts-appointment'); ?></label></th>
                    <td>
                        <input type="color" id="color_secondary" name="color_secondary" value="<?php echo esc_attr(get_option('ts_appointment_color_secondary', '#f0f0f0')); ?>">
                    </td>
                </tr>
            </table>
        </div>

        <div class="settings-section">
            <h2><?php echo esc_html__('Paramètres monétaires', 'ts-appointment'); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row"><label for="currency_symbol"><?php echo esc_html__('Symbole monétaire', 'ts-appointment'); ?></label></th>
                    <td>
                        <input type="text" id="currency_symbol" name="currency_symbol" value="<?php echo esc_attr(get_option('ts_appointment_currency_symbol', '€')); ?>" class="small-text" maxlength="5">
                        <p class="description"><?php echo esc_html__('Ex: €, $, CHF, etc.', 'ts-appointment'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="currency_position"><?php echo esc_html__('Position du symbole', 'ts-appointment'); ?></label></th>
                    <td>
                        <select id="currency_position" name="currency_position">
                            <option value="left" <?php selected(get_option('ts_appointment_currency_position', 'right'), 'left'); ?>><?php echo esc_html__('Gauche ($ 50)', 'ts-appointment'); ?></option>
                            <option value="right" <?php selected(get_option('ts_appointment_currency_position', 'right'), 'right'); ?>><?php echo esc_html__('Droite (50 €)', 'ts-appointment'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <div class="settings-section">
            <h2><?php echo esc_html__('Notifications', 'ts-appointment'); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="enable_reminders"><?php echo esc_html__('Activer les rappels', 'ts-appointment'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="enable_reminders" name="enable_reminders" value="1" <?php checked(get_option('ts_appointment_enable_reminders'), 1); ?>>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="reminder_hours"><?php echo esc_html__('Rappel (heures avant)', 'ts-appointment'); ?></label></th>
                    <td>
                        <input type="number" id="reminder_hours" name="reminder_hours" value="<?php echo esc_attr(get_option('ts_appointment_reminder_hours', 24)); ?>" class="small-text">
                    </td>
                </tr>
            </table>
        </div>

        <div class="settings-section">
            <h2><?php echo esc_html__('Google Calendar', 'ts-appointment'); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="google_calendar_enabled"><?php echo esc_html__('Activer la synchronisation', 'ts-appointment'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="google_calendar_enabled" name="google_calendar_enabled" value="1" <?php checked(get_option('ts_appointment_google_calendar_enabled'), 1); ?>>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="google_send_updates"><?php echo esc_html__('Notifications Google Agenda', 'ts-appointment'); ?></label></th>
                    <td>
                        <select id="google_send_updates" name="google_send_updates">
                            <option value="none" <?php selected(get_option('ts_appointment_google_send_updates', 'none'), 'none'); ?>><?php echo esc_html__('Ne pas envoyer (none)', 'ts-appointment'); ?></option>
                            <option value="externalOnly" <?php selected(get_option('ts_appointment_google_send_updates', 'none'), 'externalOnly'); ?>><?php echo esc_html__('Envoyer aux invités externes seulement (externalOnly)', 'ts-appointment'); ?></option>
                            <option value="all" <?php selected(get_option('ts_appointment_google_send_updates', 'none'), 'all'); ?>><?php echo esc_html__('Envoyer à tous (all)', 'ts-appointment'); ?></option>
                        </select>
                        <p class="description"><?php echo esc_html__('Contrôle du paramètre sendUpdates passé à l\'API Google Calendar pour éviter l\'envoi d\'invitations automatiques.', 'ts-appointment'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="google_email_reminders"><?php echo esc_html__('Rappels email dans Google', 'ts-appointment'); ?></label></th>
                    <td>
                        <label><input type="checkbox" id="google_email_reminders" name="google_email_reminders" value="1" <?php checked(get_option('ts_appointment_google_email_reminders'), 0, false); ?>> <?php echo esc_html__('Autoriser les rappels par email depuis l\'événement Google (par défaut désactivé)', 'ts-appointment'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="google_client_id"><?php echo esc_html__('Client ID', 'ts-appointment'); ?></label></th>
                    <td>
                        <input type="text" id="google_client_id" name="google_client_id" value="<?php echo esc_attr(get_option('ts_appointment_google_client_id')); ?>" class="regular-text">
                        <p class="description"><?php echo esc_html__('Obtenu depuis Google Cloud Console', 'ts-appointment'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="google_client_secret"><?php echo esc_html__('Client Secret', 'ts-appointment'); ?></label></th>
                    <td>
                        <input type="password" id="google_client_secret" name="google_client_secret" value="<?php echo esc_attr(get_option('ts_appointment_google_client_secret')); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="google_calendar_id"><?php echo esc_html__('ID Calendrier', 'ts-appointment'); ?></label></th>
                    <td>
                        <input type="text" id="google_calendar_id" name="google_calendar_id" value="<?php echo esc_attr(get_option('ts_appointment_google_calendar_id')); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php echo esc_html__('Compte Google', 'ts-appointment'); ?></th>
                    <td>
                        <?php
                        $client_id    = get_option('ts_appointment_google_client_id');
                        $client_secret= get_option('ts_appointment_google_client_secret');
                        $access_token = get_option('ts_appointment_google_access_token');

                        if ($client_id && $client_secret) {
                            if ($access_token) {
                                echo '<p><strong>' . esc_html__('Statut : Connecté', 'ts-appointment') . '</strong></p>';
                                $disconnect_url = esc_url(admin_url('admin.php?page=ts-appointment-settings&action=google_disconnect'));
                                echo '<a href="' . $disconnect_url . '" class="button button-secondary">' . esc_html__('Déconnecter le compte Google', 'ts-appointment') . '</a>';
                            } else {
                                $auth_url = esc_url(admin_url('admin.php?page=ts-appointment-settings&action=google_auth'));
                                echo '<a href="' . $auth_url . '" class="button button-primary">' . esc_html__('Lier mon compte Google', 'ts-appointment') . '</a>';
                                echo '<p class="description" style="margin-top:8px">' . esc_html__('Une nouvelle fenêtre s’ouvrira pour autoriser l’accès à votre Google Agenda.', 'ts-appointment') . '</p>';
                            }
                        } else {
                            echo '<p class="description">' . esc_html__('Veuillez saisir le Client ID et le Client Secret pour activer la liaison du compte.', 'ts-appointment') . '</p>';
                        }
                        ?>
                    </td>
                </tr>
            </table>
        </div>

        <div class="settings-section">
            <h2><?php echo esc_html__('Sécurité', 'ts-appointment'); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="turnstile_enabled"><?php echo esc_html__('Activer Cloudflare Turnstile', 'ts-appointment'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="turnstile_enabled" name="turnstile_enabled" value="1" <?php checked(get_option('ts_appointment_turnstile_enabled'), 1); ?>>
                        <p class="description"><?php echo esc_html__('Ajoute une protection anti-robot sur le formulaire public.', 'ts-appointment'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="turnstile_site_key"><?php echo esc_html__('Site Key', 'ts-appointment'); ?></label></th>
                    <td>
                        <input type="text" id="turnstile_site_key" name="turnstile_site_key" value="<?php echo esc_attr(get_option('ts_appointment_turnstile_site_key')); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="turnstile_secret_key"><?php echo esc_html__('Secret Key', 'ts-appointment'); ?></label></th>
                    <td>
                        <input type="password" id="turnstile_secret_key" name="turnstile_secret_key" value="<?php echo esc_attr(get_option('ts_appointment_turnstile_secret_key')); ?>" class="regular-text">
                        <p class="description"><?php echo esc_html__('Conservez la clé secrète privée. Requis pour vérifier les jetons côté serveur.', 'ts-appointment'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="debug_enabled"><?php echo esc_html__('Activer le mode debug (logs)', 'ts-appointment'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="debug_enabled" name="debug_enabled" value="1" <?php checked(get_option('ts_appointment_debug_enabled'), 1); ?>>
                        <p class="description"><?php echo esc_html__('Active l’enregistrement des logs du plugin et ajoute une entrée "Logs" dans la barre latérale admin (visible uniquement quand activé).', 'ts-appointment'); ?></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="settings-section">
            <h2><?php echo esc_html__('Mailgun (envoi d\'emails)', 'ts-appointment'); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="mailgun_enabled"><?php echo esc_html__('Activer Mailgun pour les emails du plugin', 'ts-appointment'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="mailgun_enabled" name="mailgun_enabled" value="1" <?php checked(get_option('ts_appointment_mailgun_enabled'), 1); ?>>
                        <p class="description"><?php echo esc_html__('Utiliser Mailgun pour les emails envoyés par ce plugin (confirmation, notifications).', 'ts-appointment'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="mailgun_global_enabled"><?php echo esc_html__('Remplacer globalement wp_mail', 'ts-appointment'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="mailgun_global_enabled" name="mailgun_global_enabled" value="1" <?php checked(get_option('ts_appointment_mailgun_global_enabled'), 1); ?>>
                        <p class="description"><?php echo esc_html__('Si activé, toutes les utilisations de wp_mail() seront routées via l\'API Mailgun.', 'ts-appointment'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="mailgun_domain"><?php echo esc_html__('Domaine Mailgun', 'ts-appointment'); ?></label></th>
                    <td>
                        <input type="text" id="mailgun_domain" name="mailgun_domain" value="<?php echo esc_attr(get_option('ts_appointment_mailgun_domain')); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="mailgun_api_key"><?php echo esc_html__('API Key', 'ts-appointment'); ?></label></th>
                    <td>
                        <input type="password" id="mailgun_api_key" name="mailgun_api_key" value="<?php echo esc_attr(get_option('ts_appointment_mailgun_api_key')); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="mailgun_test_to"><?php echo esc_html__('Email de test', 'ts-appointment'); ?></label></th>
                    <td>
                        <input type="email" id="mailgun_test_to" name="mailgun_test_to" value="<?php echo esc_attr(get_option('admin_email')); ?>" class="regular-text">
                        <p class="description"><?php echo esc_html__('Adresse email à utiliser pour le test d\'envoi Mailgun.', 'ts-appointment'); ?></p>
                        <p><button class="button button-secondary" type="submit" name="mailgun_send_test" value="1"><?php echo esc_html__('Envoyer un email de test', 'ts-appointment'); ?></button></p>
                    </td>
                </tr>
            </table>
        </div>

        <?php submit_button(); ?>
    </form>
</div>
