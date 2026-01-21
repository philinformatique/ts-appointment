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
            <strong>{client_name}</strong>, <strong>{service_name}</strong>, <strong>{appointment_date}</strong>, <strong>{appointment_time}</strong>, <strong>{location}</strong>, <strong>{business_name}</strong>
        </p>

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
        // Sample placeholders
        var sample = {
            '{client_name}':'Jean Dupont',
            '{service_name}':'Consultation',
            '{appointment_date}':'15/03/2026',
            '{appointment_time}':'14:00',
            '{location}':'Au cabinet',
            '{business_name}':document.title || 'Mon entreprise'
        };
        Object.keys(sample).forEach(function(p){
            subject = subject.replace(new RegExp(p,'g'), sample[p]);
            body = body.replace(new RegExp(p,'g'), sample[p]);
        });
        document.getElementById('ts-email-preview-content').innerHTML = '<h3>'+subject+'</h3>' + body;
        document.getElementById('ts-email-preview').style.display = 'block';
        window.scrollTo(0, document.getElementById('ts-email-preview').offsetTop - 20);
    }
    </script>

<?php
// Handle saving when POSTed (controller also accepts in display handler)
?>
