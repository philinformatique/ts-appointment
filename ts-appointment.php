<?php
/**
 * Plugin Name: TS Appointment
 * Plugin URI: https://techno-solution.ca/ts-appointment
 * Description: Plugin de prise de rendez-vous avec synchronisation Google Agenda
 * Version: 1.0.0
 * Author: TS Appointment
 * Author URI: https://techno-solution.ca
 * Tested up to: 6.9
 * Text Domain: ts-appointment
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Constantes du plugin
define('TS_APPOINTMENT_VERSION', '1.0.0');
define('TS_APPOINTMENT_DIR', plugin_dir_path(__FILE__));
define('TS_APPOINTMENT_URL', plugin_dir_url(__FILE__));
define('TS_APPOINTMENT_BASENAME', plugin_basename(__FILE__));

// Classe principale du plugin
class TS_Appointment {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->init_hooks();
        // Ensure helpers (logger, small utilities) are available early
        if (file_exists(TS_APPOINTMENT_DIR . 'includes/helpers.php')) {
            require_once TS_APPOINTMENT_DIR . 'includes/helpers.php';
        }
        $this->load_dependencies();
    }

    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('plugins_loaded', array($this, 'maybe_create_tables'), 5);
        add_action('plugins_loaded', array($this, 'maybe_init_defaults'), 6);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        // Public cancel endpoint (admin-post) for client cancellation links
        add_action('admin_post_ts_appointment_cancel_public', array($this, 'handle_public_cancel'));
        add_action('admin_post_nopriv_ts_appointment_cancel_public', array($this, 'handle_public_cancel'));
    }

    public static function handle_public_cancel() {
        $id = isset($_REQUEST['appointment_id']) ? intval($_REQUEST['appointment_id']) : 0;
        $nonce = isset($_REQUEST['_wpnonce']) ? sanitize_text_field($_REQUEST['_wpnonce']) : '';
        $ct = isset($_REQUEST['ct']) ? sanitize_text_field($_REQUEST['ct']) : '';
        // If it's a GET request and verification passes, show a confirmation form
        $verified_get = false;
        if ($id && $nonce && wp_verify_nonce($nonce, 'ts_appointment_cancel_' . $id)) {
            $verified_get = true;
        } elseif ($id && $ct && class_exists('TS_Appointment_Email') && TS_Appointment_Email::validate_cancel_token($id, $ct)) {
            $verified_get = true;
        }

        if (!$id || !$verified_get) {
            wp_die(__('Lien d\'annulation invalide ou expiré.', 'ts-appointment'));
        }

        // If POST with confirm flag, perform cancellation (verify again for POST)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm']) && intval($_POST['confirm']) === 1) {
            $post_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
            $post_nonce = isset($_POST['_wpnonce']) ? sanitize_text_field($_POST['_wpnonce']) : '';
            $post_ct = isset($_POST['ct']) ? sanitize_text_field($_POST['ct']) : '';

            $verified_post = false;
            if ($post_id && $post_nonce && wp_verify_nonce($post_nonce, 'ts_appointment_cancel_' . $post_id)) {
                $verified_post = true;
            } elseif ($post_id && $post_ct && class_exists('TS_Appointment_Email') && TS_Appointment_Email::validate_cancel_token($post_id, $post_ct)) {
                $verified_post = true;
            }

            if (!$post_id || !$verified_post) {
                wp_die(__('Autorisation d\'annulation invalide.', 'ts-appointment'));
            }

            $ok = TS_Appointment_Manager::cancel_appointment($post_id, __('Annulation par le client via email', 'ts-appointment'));
            if ($ok) {
                wp_safe_redirect(site_url('/?ts_appointment_cancelled=1'));
                exit;
            }
            wp_die(__('Impossible d\'annuler le rendez-vous.', 'ts-appointment'));
        }

        // Otherwise show a simple confirmation page
        $appointment = TS_Appointment_Database::get_appointment($id);
        $appt_date = $appointment ? esc_html(date_i18n(get_option('ts_appointment_date_format', 'j/m/Y') . ' ' . get_option('ts_appointment_time_format', 'H:i'), strtotime($appointment->appointment_date . ' ' . $appointment->appointment_time))) : '';
        $business_name = get_option('ts_appointment_business_name');
        $cancel_action = esc_url(admin_url('admin-post.php?action=ts_appointment_cancel_public'));
        $color_primary = get_option('ts_appointment_color_primary', '#007cba');
        
        // Render professional responsive confirmation HTML
        echo '<!doctype html>
<html lang="' . esc_attr(get_bloginfo('language')) . '">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html__('Confirmation d\'annulation', 'ts-appointment') . '</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
        }
        
        .container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        
        h1 {
            font-size: 28px;
            color: #222;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .subtitle {
            color: #666;
            font-size: 14px;
            margin-top: 8px;
        }
        
        .content {
            background: #f9f9f9;
            border-left: 4px solid ' . esc_attr($color_primary) . ';
            padding: 20px;
            margin-bottom: 32px;
            border-radius: 4px;
        }
        
        .appointment-info {
            margin-bottom: 16px;
        }
        
        .appointment-info:last-child {
            margin-bottom: 0;
        }
        
        .info-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .info-value {
            font-size: 16px;
            color: #222;
            margin-top: 4px;
            font-weight: 500;
        }
        
        .warning-text {
            color: #c0392b;
            font-size: 14px;
            margin-top: 20px;
            padding: 16px;
            background: #fff5f5;
            border-radius: 4px;
            border: 1px solid #feb2b2;
        }
        
        .warning-text strong {
            color: #a93226;
        }
        
        .actions {
            display: flex;
            gap: 12px;
            flex-direction: column;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
            -webkit-appearance: none;
        }
        
        .btn-confirm {
            background: #c0392b;
            color: white;
            order: 2;
        }
        
        .btn-confirm:hover {
            background: #a93226;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(192, 57, 43, 0.3);
        }
        
        .btn-cancel {
            background: #ecf0f1;
            color: #222;
            order: 1;
        }
        
        .btn-cancel:hover {
            background: #d5dbdb;
            transform: translateY(-2px);
        }
        
        .footer {
            text-align: center;
            font-size: 12px;
            color: #999;
            margin-top: 24px;
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 24px;
            }
            
            h1 {
                font-size: 24px;
            }
            
            .content {
                padding: 16px;
            }
            
            .info-value {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="icon">⚠️</div>
            <h1>' . esc_html__('Confirmer l\'annulation', 'ts-appointment') . '</h1>
            <p class="subtitle">' . esc_html__('Veuillez vérifier les informations ci-dessous', 'ts-appointment') . '</p>
        </div>
        
        <div class="content">
            <div class="appointment-info">
                <div class="info-label">' . esc_html__('Entreprise', 'ts-appointment') . '</div>
                <div class="info-value">' . esc_html($business_name ?: get_bloginfo('name')) . '</div>
            </div>';
            
            if ($appointment) {
                echo '<div class="appointment-info">
                    <div class="info-label">' . esc_html__('Numéro de rendez-vous', 'ts-appointment') . '</div>
                    <div class="info-value">#' . intval($appointment->id) . '</div>
                </div>
                <div class="appointment-info">
                    <div class="info-label">' . esc_html__('Date et heure', 'ts-appointment') . '</div>
                    <div class="info-value">' . esc_html($appt_date) . '</div>
                </div>';
            }
            
            echo '<div class="warning-text">
                <strong>' . esc_html__('Attention :', 'ts-appointment') . '</strong><br>
                ' . esc_html__('Cette action est irréversible. Une fois annulé, le créneau sera de nouveau disponible.', 'ts-appointment') . '
            </div>
        </div>
        
        <form method="post" action="' . $cancel_action . '">
            <input type="hidden" name="appointment_id" value="' . esc_attr($id) . '" />';
            
            if (!empty($nonce)) echo '<input type="hidden" name="_wpnonce" value="' . esc_attr($nonce) . '" />';
            if (!empty($ct)) echo '<input type="hidden" name="ct" value="' . esc_attr($ct) . '" />';
            
            echo '<input type="hidden" name="confirm" value="1" />
            
            <div class="actions">
                <a href="' . esc_url(site_url()) . '" class="btn btn-cancel">' . esc_html__('Retour', 'ts-appointment') . '</a>
                <button type="submit" class="btn btn-confirm">' . esc_html__('Oui, annuler le rendez-vous', 'ts-appointment') . '</button>
            </div>
        </form>
        
        <div class="footer">
            <p>' . esc_html__('Vous recevrez une confirmation par email une fois l\'annulation traitée.', 'ts-appointment') . '</p>
        </div>
    </div>
</body>
</html>';
        exit;
    }

    private function load_dependencies() {
        require_once TS_APPOINTMENT_DIR . 'includes/class-database.php';
        require_once TS_APPOINTMENT_DIR . 'includes/class-google-calendar.php';
        require_once TS_APPOINTMENT_DIR . 'includes/class-appointment.php';
        require_once TS_APPOINTMENT_DIR . 'includes/class-admin.php';
        require_once TS_APPOINTMENT_DIR . 'includes/class-frontend.php';
        require_once TS_APPOINTMENT_DIR . 'includes/class-rest-api.php';
        require_once TS_APPOINTMENT_DIR . 'includes/class-email.php';
        // If Mailgun global override is enabled, intercept wp_mail calls
        if (class_exists('TS_Appointment_Email')) {
            add_filter('pre_wp_mail', array('TS_Appointment_Email', 'mailgun_pre_wp_mail'), 10, 2);
        }
    }

    public function activate() {
        TS_Appointment_Database::create_tables();
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function maybe_create_tables() {
        TS_Appointment_Database::maybe_create_tables();
    }

    public function maybe_init_defaults() {
        TS_Appointment_Database::maybe_init_defaults();
    }

    public function load_textdomain() {
        load_plugin_textdomain('ts-appointment', false, dirname(TS_APPOINTMENT_BASENAME) . '/languages');
    }

    public function enqueue_frontend_assets() {
        // Only load frontend assets on pages that contain the shortcodes to avoid slowing other pages.
        $should_enqueue = false;
        if (is_singular()) {
            global $post;
            if ($post && isset($post->post_content)) {
                if (has_shortcode($post->post_content, 'ts_appointment_form') || has_shortcode($post->post_content, 'ts_appointment_calendar')) {
                    $should_enqueue = true;
                }
            }
        }
        // Also allow forcing via filter
        $should_enqueue = apply_filters('ts_appointment_force_enqueue_frontend', $should_enqueue);
        if (!$should_enqueue) return;

        wp_enqueue_style('ts-appointment-frontend', TS_APPOINTMENT_URL . 'assets/css/frontend.css', array(), TS_APPOINTMENT_VERSION);

        $turnstile_enabled = (bool) get_option('ts_appointment_turnstile_enabled');
        $turnstile_site_key = get_option('ts_appointment_turnstile_site_key');
        $turnstile_secret_key = get_option('ts_appointment_turnstile_secret_key');

        if ($turnstile_enabled && $turnstile_site_key && $turnstile_secret_key) {
            wp_enqueue_script(
                'cloudflare-turnstile',
                'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit',
                array(),
                null,
                true
            );
        }

        $deps = array('jquery');
        if (wp_script_is('cloudflare-turnstile', 'registered')) {
            $deps[] = 'cloudflare-turnstile';
        }

        wp_enqueue_script('ts-appointment-frontend', TS_APPOINTMENT_URL . 'assets/js/frontend.js', $deps, TS_APPOINTMENT_VERSION, true);

        // Load en_US map when site locale is en_US or en_CA; otherwise use default French
        $runtime_i18n = $default_i18n;
        if (in_array(get_locale(), array('en_US','en_CA'))) {
            $php_i18n_file = TS_APPOINTMENT_DIR . 'languages/en_US.php';
            if (file_exists($php_i18n_file)) {
                $maybe = include $php_i18n_file;
                if (is_array($maybe)) {
                    $runtime_i18n = $maybe;
                }
            }
        }

        // Prepare locationRequiresAddress mapping for frontend
        $frontend_locations_json = get_option('ts_appointment_locations_config');
        $frontend_locations = json_decode($frontend_locations_json, true);
        $location_requires = array();
        if (is_array($frontend_locations)) {
            foreach ($frontend_locations as $loc) {
                if (isset($loc['key'])) {
                    // client address requirement removed — set to 0 for all
                    $location_requires[$loc['key']] = 0;
                }
            }
        }

        wp_localize_script('ts-appointment-frontend', 'tsAppointment', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            // REST nonce pour authentification cookie quand l'utilisateur est connecté
            'restNonce' => wp_create_nonce('wp_rest'),
            'restUrl' => rest_url('ts-appointment/v1/'),
            'dateFormat' => get_option('ts_appointment_date_format', 'Y-m-d'),
            'timeFormat' => get_option('ts_appointment_time_format', 'H:i'),
            'maxDaysAhead' => intval(get_option('ts_appointment_max_days_ahead', 30)),
            'currencySymbol' => get_option('ts_appointment_currency_symbol', '€'),
            'currencyPosition' => get_option('ts_appointment_currency_position', 'right'),
            'turnstileEnabled' => ($turnstile_enabled && $turnstile_site_key && $turnstile_secret_key) ? 1 : 0,
            'turnstileSiteKey' => $turnstile_site_key,
            'i18n' => $runtime_i18n,
            'locationRequiresAddress' => $location_requires,
            'googleMapsApiKey' => get_option('ts_appointment_google_maps_api_key', ''),
            'debug' => (bool) get_option('ts_appointment_debug_enabled', false),
        ));
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'ts-appointment') === false) {
            return;
        }
        
        wp_enqueue_style('ts-appointment-admin', TS_APPOINTMENT_URL . 'assets/css/admin.css', array(), TS_APPOINTMENT_VERSION);
        wp_enqueue_script('ts-appointment-admin', TS_APPOINTMENT_URL . 'assets/js/admin.js', array('jquery'), TS_APPOINTMENT_VERSION, true);
        
        // Prepare location labels for JavaScript
        $locations_json = get_option('ts_appointment_locations_config');
        $locations = json_decode($locations_json, true);
        $location_labels = array();
        if (is_array($locations)) {
            foreach ($locations as $loc) {
                if (isset($loc['key']) && isset($loc['label'])) {
                    $location_labels[$loc['key']] = $loc['label'];
                }
            }
        }
        
        // Default admin i18n (French)
        $default_admin_i18n = array(
            'Êtes-vous sûr?' => 'Êtes-vous sûr?',
            'Rendez-vous confirmé' => 'Rendez-vous confirmé',
            'Erreur lors de la confirmation' => 'Erreur lors de la confirmation',
            'Erreur serveur' => 'Erreur serveur',
            'Rendez-vous supprimé' => 'Rendez-vous supprimé',
            'Erreur lors de la suppression' => 'Erreur lors de la suppression',
            'Erreur lors du chargement' => 'Erreur lors du chargement',
            'Détails du rendez-vous' => 'Détails du rendez-vous',
            'Nom:' => 'Nom:',
            'Email:' => 'Email:',
            'Téléphone:' => 'Téléphone:',
            'Date:' => 'Date:',
            'Type:' => 'Type:',
            'Adresse:' => 'Adresse:',
            'Notes:' => 'Notes:',
            'Statut:' => 'Statut:',
            'Fermer' => 'Fermer',
            'En attente' => 'En attente',
            'Confirmé' => 'Confirmé',
            'Complété' => 'Complété',
            'Annulé' => 'Annulé'
        );

        $admin_i18n = $default_admin_i18n;
        if (in_array(get_locale(), array('en_US','en_CA'))) {
            $php_i18n_file = TS_APPOINTMENT_DIR . 'languages/en_US.php';
            if (file_exists($php_i18n_file)) {
                $maybe = include $php_i18n_file;
                if (is_array($maybe)) {
                    $admin_i18n = $maybe;
                }
            }
        }

        wp_localize_script('ts-appointment-admin', 'tsAppointment', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'restUrl' => rest_url('ts-appointment/v1/'),
            'locationLabels' => $location_labels,
            'i18n' => $admin_i18n
        ));
    }

    public function add_admin_menu() {
        add_menu_page(
            __('TS Appointment', 'ts-appointment'),
            __('Rendez-vous', 'ts-appointment'),
            'manage_options',
            'ts-appointment',
            array('TS_Appointment_Admin', 'display_dashboard'),
            'dashicons-calendar-alt',
            25
        );

        add_submenu_page(
            'ts-appointment',
            __('Tableau de bord', 'ts-appointment'),
            __('Tableau de bord', 'ts-appointment'),
            'manage_options',
            'ts-appointment',
            array('TS_Appointment_Admin', 'display_dashboard')
        );

        add_submenu_page(
            'ts-appointment',
            __('Rendez-vous', 'ts-appointment'),
            __('Rendez-vous', 'ts-appointment'),
            'manage_options',
            'ts-appointment-list',
            array('TS_Appointment_Admin', 'display_appointments')
        );

        add_submenu_page(
            'ts-appointment',
            __('Paramètres', 'ts-appointment'),
            __('Paramètres', 'ts-appointment'),
            'manage_options',
            'ts-appointment-settings',
            array('TS_Appointment_Admin', 'display_settings')
        );

        add_submenu_page(
            'ts-appointment',
            __('Lieux', 'ts-appointment'),
            __('Lieux', 'ts-appointment'),
            'manage_options',
            'ts-appointment-locations',
            array('TS_Appointment_Admin', 'display_locations')
        );

        add_submenu_page(
            'ts-appointment',
            __('Emails', 'ts-appointment'),
            __('Emails', 'ts-appointment'),
            'manage_options',
            'ts-appointment-emails',
            array('TS_Appointment_Admin', 'display_email_templates')
        );

        add_submenu_page(
            'ts-appointment',
            __('Email Logs', 'ts-appointment'),
            __('Email Logs', 'ts-appointment'),
            'manage_options',
            'ts-appointment-email-logs',
            array('TS_Appointment_Admin', 'display_email_logs')
        );

        add_submenu_page(
            'ts-appointment',
            __('Formulaire', 'ts-appointment'),
            __('Formulaire', 'ts-appointment'),
            'manage_options',
            'ts-appointment-form',
            array('TS_Appointment_Admin', 'display_form_builder')
        );
        add_submenu_page(
            'ts-appointment',
            __('Créneaux', 'ts-appointment'),
            __('Créneaux', 'ts-appointment'),
            'manage_options',
            'ts-appointment-slots',
            array('TS_Appointment_Admin', 'display_slots')
        );
        add_submenu_page(
            'ts-appointment',
            __('Services', 'ts-appointment'),
            __('Services', 'ts-appointment'),
            'manage_options',
            'ts-appointment-services',
            array('TS_Appointment_Admin', 'display_services')
        );

        // Add Logs submenu when debug option is enabled
        $debug_enabled = false;
        if (function_exists('get_option')) {
            $debug_enabled = (bool) get_option('ts_appointment_debug_enabled', false);
        }
        if ($debug_enabled) {
            add_submenu_page(
                'ts-appointment',
                __('Logs', 'ts-appointment'),
                __('Logs', 'ts-appointment'),
                'manage_options',
                'ts-appointment-logs',
                array('TS_Appointment_Admin', 'display_logs')
            );
        }
    }
}

// Initialisation du plugin
function ts_appointment_init() {
    return TS_Appointment::get_instance();
}

add_action('plugins_loaded', 'ts_appointment_init', 0);

// -----------------------------
// GitHub updater (basic)
// -----------------------------
if (!defined('TS_APPOINTMENT_GITHUB_REPO')) {
    define('TS_APPOINTMENT_GITHUB_REPO', 'philinformatique/ts-appointment');
}

/**
 * Check GitHub for a newer release and inject plugin update info into WP update transient.
 */
function ts_appointment_check_for_update($transient) {
    if (empty($transient) || empty($transient->checked)) {
        return $transient;
    }

    $plugin_basename = TS_APPOINTMENT_BASENAME;
    $current_version = TS_APPOINTMENT_VERSION;

    // Cache GitHub response for 1 hour to avoid rate limits
    $cache_key = 'ts_appointment_github_release';
    $release = get_transient($cache_key);
    if ($release === false) {
        $api = 'https://api.github.com/repos/' . TS_APPOINTMENT_GITHUB_REPO . '/releases/latest';
        $response = wp_remote_get($api, array(
            'headers' => array('User-Agent' => 'WordPress/' . get_bloginfo('version') . ' ts-appointment'),
            'timeout' => 15,
        ));
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body);
            if (is_object($data) && !empty($data->tag_name)) {
                $release = $data;
                set_transient($cache_key, $release, HOUR_IN_SECONDS);
            } else {
                // fallback: try tags endpoint
                $tags_api = 'https://api.github.com/repos/' . TS_APPOINTMENT_GITHUB_REPO . '/tags';
                $r2 = wp_remote_get($tags_api, array('headers' => array('User-Agent' => 'WordPress/' . get_bloginfo('version') . ' ts-appointment'), 'timeout' => 15));
                if (!is_wp_error($r2) && wp_remote_retrieve_response_code($r2) === 200) {
                    $tags = json_decode(wp_remote_retrieve_body($r2));
                    if (is_array($tags) && !empty($tags[0]->name)) {
                        $release = new stdClass();
                        $release->tag_name = $tags[0]->name;
                        $release->zipball_url = $tags[0]->zipball_url ?? '';
                        set_transient($cache_key, $release, HOUR_IN_SECONDS);
                    }
                }
            }
        }
    }

    if (empty($release) || empty($release->tag_name)) {
        return $transient;
    }

    // Normalize tag (strip leading v)
    $remote_version = ltrim($release->tag_name, "vV");
    if (version_compare($remote_version, $current_version, '>')) {
        $package = !empty($release->zipball_url) ? $release->zipball_url : 'https://github.com/' . TS_APPOINTMENT_GITHUB_REPO . '/archive/refs/tags/' . $release->tag_name . '.zip';

        $update = new stdClass();
        $update->slug = dirname(TS_APPOINTMENT_BASENAME);
        $update->new_version = $remote_version;
        $update->url = 'https://github.com/' . TS_APPOINTMENT_GITHUB_REPO;
        $update->package = $package;

        $transient->response[$plugin_basename] = $update;
    }

    return $transient;
}
add_filter('pre_set_site_transient_update_plugins', 'ts_appointment_check_for_update');

/**
 * Provide plugin information for the plugin details modal via GitHub release notes.
 */
function ts_appointment_plugins_api_handler($result, $action, $args) {
    if ($action !== 'plugin_information') return $result;
    $plugin_basename = TS_APPOINTMENT_BASENAME;

    // If requested plugin matches ours
    if (!empty($args->slug) && (strpos($plugin_basename, $args->slug) === false) && ($args->slug !== dirname($plugin_basename))) {
        return $result;
    }

    // Fetch cached release
    $release = get_transient('ts_appointment_github_release');
    if ($release === false) {
        // Force a check
        ts_appointment_check_for_update(get_site_transient('update_plugins'));
        $release = get_transient('ts_appointment_github_release');
    }
    if (empty($release)) return $result;

    $remote_version = ltrim($release->tag_name, "vV");
    $plugin_info = new stdClass();
    $plugin_info->name = 'TS Appointment';
    $plugin_info->slug = dirname($plugin_basename);
    $plugin_info->version = $remote_version;
    $plugin_info->author = 'philinformatique';
    $plugin_info->homepage = 'https://github.com/' . TS_APPOINTMENT_GITHUB_REPO;
    $plugin_info->download_link = !empty($release->zipball_url) ? $release->zipball_url : 'https://github.com/' . TS_APPOINTMENT_GITHUB_REPO . '/archive/refs/tags/' . $release->tag_name . '.zip';
    $plugin_info->sections = array();
    $plugin_info->sections['description'] = isset($release->body) ? wp_kses_post($release->body) : '';

    return $plugin_info;
}
add_filter('plugins_api', 'ts_appointment_plugins_api_handler', 20, 3);
