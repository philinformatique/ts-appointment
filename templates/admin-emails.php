<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ts-appointment-admin">
    <h1><?php _e('Templates emails', 'ts-appointment'); ?></h1>
    <p><?php _e('Personnalisez les emails envoyés aux clients et à l\'administration. Utilisez les placeholders listés ci-dessous.', 'ts-appointment'); ?></p>

    <?php $templates_raw = get_option('ts_appointment_email_templates'); $templates = json_decode($templates_raw, true); ?>

    <form method="post">
        <?php wp_nonce_field('ts_appointment_email_templates', 'ts_appointment_nonce'); ?>
        <input type="hidden" name="action_type" value="save_templates">

        <h2><?php _e('Placeholders disponibles', 'ts-appointment'); ?></h2>
        <p class="description">
            <?php
            // Build placeholder list from form schema plus common placeholders
            $placeholders = array();
            $form_schema_raw = get_option('ts_appointment_form_schema');
            $form_schema = json_decode($form_schema_raw, true);
            if (is_array($form_schema)) {
                foreach ($form_schema as $f) {
                    if (!empty($f['key'])) $placeholders[] = '{' . esc_html($f['key']) . '}';
                }
            }
            $common = array('{service_name}','{appointment_date}','{appointment_time}','{location}','{business_name}','{business_address}','{appointment_id}','{cancel_url}','{cancel_button}','{edit_url}','{edit_button}','{reason}');
            $placeholders = array_merge($placeholders, $common);
            echo implode(', ', array_map(function($p){ return '<strong>' . $p . '</strong>'; }, $placeholders));
            ?>
        </p>
        <p class="description"><strong><?php _e('Exemple conditionnel', 'ts-appointment'); ?> :</strong> {if location==atelier}Texte spécial pour l\'atelier{else}Texte par défaut{endif}</p>

        <?php foreach (array('client_new' => __('Email client - nouvelle demande', 'ts-appointment'), 'client_confirmation' => __('Email client - confirmation', 'ts-appointment'), 'admin_new' => __('Email admin - nouvelle demande', 'ts-appointment'), 'client_cancellation' => __('Email client - annulation', 'ts-appointment')) as $key => $label):
            $tpl = isset($templates[$key]) ? $templates[$key] : array('subject' => '', 'body' => '');
        ?>
            <h3><?php echo esc_html($label); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label for="<?php echo esc_attr($key . '_subject'); ?>"><?php _e('Sujet', 'ts-appointment'); ?></label></th>
                    <td><input type="text" id="<?php echo esc_attr($key . '_subject'); ?>" name="templates[<?php echo esc_attr($key); ?>][subject]" class="regular-text" value="<?php echo esc_attr($tpl['subject']); ?>"></td>
                </tr>
                <tr>
                    <th><label><?php _e('Contenu (HTML)', 'ts-appointment'); ?></label></th>
                    <td>
                        <?php
                        $editor_id = $key . '_body';
                        $content = $tpl['body'];
                        wp_editor($content, $editor_id, array('textarea_name' => 'templates[' . $key . '][body]', 'teeny' => false, 'media_buttons' => false, 'textarea_rows' => 8));
                        ?>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <button type="button" class="button" data-template-key="<?php echo esc_attr($key); ?>" onclick="tsAppointmentPreviewTemplate(this);"><?php echo esc_html__('Aperçu', 'ts-appointment'); ?></button>
                    </td>
                </tr>
            </table>
        <?php endforeach; ?>

        <?php submit_button(__('Enregistrer les templates', 'ts-appointment')); ?>
    </form>

    <div id="ts-email-preview" style="margin-top:20px; display:none;">
        <h2><?php _e('Aperçu', 'ts-appointment'); ?></h2>
        <div id="ts-email-preview-content" style="border:1px solid #ddd; padding:15px; background:#fff;"></div>
    </div>

    <script>
    function tsAppointmentPreviewTemplate(btn) {
        var key = btn.getAttribute('data-template-key');
        // Get subject and body from inputs/editors
        var subject = document.querySelector('[name="templates['+key+'][subject]"]').value || '';
        var editor = window.tinymce && tinymce.get(key + '_body');
        var body = '';
        if (editor) {
            body = editor.getContent();
        } else {
            var ta = document.querySelector('[name="templates['+key+'][body]"]');
            if (ta) body = ta.value;
        }
        // Sample placeholders (generated from PHP schema)
        var sample = <?php
            $sample = array();
            if (is_array($form_schema)) {
                foreach ($form_schema as $f) {
                    if (empty($f['key'])) continue;
                    // choose a sample value based on type when possible
                    $type = isset($f['type']) ? $f['type'] : 'text';
                    $key = '{' . $f['key'] . '}';
                    switch ($type) {
                        case 'email': $sample[$key] = 'jean@example.com'; break;
                        case 'tel': $sample[$key] = '0123456789'; break;
                        case 'textarea': $sample[$key] = 'Remarque exemple'; break;
                        default: $sample[$key] = isset($f['label']) ? $f['label'] . ' exemple' : 'Exemple'; break;
                    }
                }
            }
            // common placeholders
            $sample['{service_name}'] = 'Consultation';
            $sample['{appointment_date}'] = '15/03/2026';
            $sample['{appointment_time}'] = '14:00';
            $sample['{location}'] = 'Au cabinet';
            $sample['{business_name}'] = get_bloginfo('name');
            $sample['{business_address}'] = get_option('ts_appointment_business_address');
            $sample['{appointment_id}'] = '12345';
            $sample['{reason}'] = 'Raison fournie';
            $sample['{cancel_url}'] = 'https://example.com/cancel?appt=12345';
            $sample['{cancel_button}'] = '[Bouton d\'annulation]';
            $sample['{edit_url}'] = admin_url('admin.php?page=ts-appointment-list&edit_id=12345');
            $sample['{edit_button}'] = '[Bouton d\'édition]';
            echo wp_json_encode($sample);
        ?>;
        Object.keys(sample).forEach(function(p){
            subject = subject.replace(new RegExp(p,'g'), sample[p]);
            body = body.replace(new RegExp(p,'g'), sample[p]);
        });
        document.getElementById('ts-email-preview-content').innerHTML = '<h3>'+subject+'</h3>' + body;
        document.getElementById('ts-email-preview').style.display = 'block';
        window.scrollTo(0, document.getElementById('ts-email-preview').offsetTop - 20);
    }
    
    // Live preview: update preview as user types (WYSIWYG-aware)
    (function(){
        var sample = <?php
            $live_sample = array();
            if (is_array($form_schema)) {
                foreach ($form_schema as $f) {
                    if (empty($f['key'])) continue;
                    $type = isset($f['type']) ? $f['type'] : 'text';
                    $k = '{' . $f['key'] . '}';
                    switch ($type) {
                        case 'email': $live_sample[$k] = 'jean@example.com'; break;
                        case 'tel': $live_sample[$k] = '0123456789'; break;
                        case 'textarea': $live_sample[$k] = 'Remarque exemple'; break;
                        default: $live_sample[$k] = isset($f['label']) ? $f['label'] . ' exemple' : 'Exemple'; break;
                    }
                }
            }
            $live_sample['{service_name}'] = 'Consultation';
            $live_sample['{appointment_date}'] = '15/03/2026';
            $live_sample['{appointment_time}'] = '14:00';
            $live_sample['{location}'] = 'Au cabinet';
            $live_sample['{business_name}'] = get_bloginfo('name');
            $live_sample['{business_address}'] = get_option('ts_appointment_business_address');
            $live_sample['{edit_url}'] = admin_url('admin.php?page=ts-appointment-list&edit_id=12345');
            $live_sample['{edit_button}'] = '[Bouton d\'édition]';
            echo wp_json_encode($live_sample);
        ?>;

        function applySample(str){
            Object.keys(sample).forEach(function(p){
                str = str.replace(new RegExp(p,'g'), sample[p]);
            });
            return str;
        }

        function updateLivePreviewFor(key){
            var subjEl = document.querySelector('[name="templates['+key+'][subject]"]');
            var subject = subjEl ? subjEl.value : '';
            var editor = window.tinymce && tinymce.get(key + '_body');
            var body = '';
            if (editor) {
                body = editor.getContent();
            } else {
                var ta = document.querySelector('[name="templates['+key+'][body]"]');
                if (ta) body = ta.value;
            }

            subject = applySample(subject);
            body = applySample(body);

            var previewInner = '<div style="border-top:4px solid <?php echo esc_attr(get_option('ts_appointment_color_primary', '#007cba')); ?>; padding:18px; background:#fff; color:#333; font-family:Arial, Helvetica, sans-serif;">';
            previewInner += '<h3 style="color:<?php echo esc_attr(get_option('ts_appointment_color_primary', '#007cba')); ?>;">'+subject+'</h3>' + body + '</div>';
            document.getElementById('ts-email-preview-content').innerHTML = previewInner;
            document.getElementById('ts-email-preview').style.display = 'block';
        }

        // Bind inputs and editors
        document.addEventListener('DOMContentLoaded', function(){
            var keys = ['client_new','client_confirmation','admin_new','client_cancellation'];
            keys.forEach(function(k){
                var subj = document.querySelector('[name="templates['+k+'][subject]"]');
                if (subj) subj.addEventListener('input', function(){ updateLivePreviewFor(k); });

                var ta = document.querySelector('[name="templates['+k+'][body]"]');
                if (ta) ta.addEventListener('input', function(){ updateLivePreviewFor(k); });

                // If TinyMCE present, bind to its change event
                if (window.tinymce) {
                    var editor = tinymce.get(k + '_body');
                    if (editor) {
                        editor.on('change keyup', function(){ updateLivePreviewFor(k); });
                    }
                }

                // initial render
                updateLivePreviewFor(k);
            });
        });
    })();
    </script>

<?php
// Handle saving when POSTed (controller also accepts in display handler)
?>
