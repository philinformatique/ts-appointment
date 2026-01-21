<?php
/**
 * Plugin Name: TS Appointment
 * Plugin URI: https://ts-appointment.local
 * Description: Plugin de prise de rendez-vous type Calendly avec synchronisation Google Agenda
 * Version: 1.0.0
 * Update URI: https://github.com/philinformatique/ts-appointment
 * Author: TS Appointment
 * Author URI: https://ts-appointment.local
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
        $this->load_dependencies();
        $this->init_updater();
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
    }

    private function load_dependencies() {
        $required = array(
            'includes/class-database.php',
            'includes/class-google-calendar.php',
            'includes/class-appointment.php',
            'includes/class-admin.php',
            'includes/class-frontend.php',
            'includes/class-rest-api.php',
            'includes/class-email.php',
        );

        $missing = array();
        foreach ($required as $rel) {
            $path = TS_APPOINTMENT_DIR . $rel;
            if (file_exists($path)) {
                require_once $path;
            } else {
                $missing[] = $rel;
            }
        }

        if (!empty($missing)) {
            add_action('admin_notices', function() use ($missing) {
                if (!current_user_can('activate_plugins')) return;
                $list = implode(', ', array_map('esc_html', $missing));
                echo '<div class="notice notice-error"><p>' . sprintf(__('Le plugin TS Appointment est incomplet. Fichiers manquants: %s. Veuillez réinstaller le plugin.', 'ts-appointment'), $list) . '</p></div>';
            });
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
        
        wp_localize_script('ts-appointment-admin', 'tsAppointment', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'restUrl' => rest_url('ts-appointment/v1/'),
            'locationLabels' => $location_labels,
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
    }

    /**
     * Initialize Plugin Update Checker integration if present.
     * Expects the library to be placed in includes/plugin-update-checker/
     */
    private function init_updater() {
        $updater_file = TS_APPOINTMENT_DIR . 'includes/plugin-update-checker/plugin-update-checker.php';
        if (file_exists($updater_file)) {
            require_once $updater_file;
            if (class_exists('Puc_v4_Factory')) {
                try {
                    $updater = Puc_v4_Factory::buildUpdateChecker(
                        'https://github.com/philinformatique/ts-appointment/',
                        __FILE__,
                        'ts-appointment'
                    );
                    $updater->setBranch('main');
                } catch (Exception $e) {
                    // silently fail; don't break plugin
                }
            }
        } else {
            add_action('admin_notices', function() {
                if (!current_user_can('manage_options')) return;
                echo '<div class="notice notice-info"><p>' . __('Pour activer les mises à jour GitHub automatiques, placez la librairie Plugin Update Checker dans includes/plugin-update-checker/.', 'ts-appointment') . '</p></div>';
            });
        }
    }
}

// Initialisation du plugin
function ts_appointment_init() {
    return TS_Appointment::get_instance();
}

add_action('plugins_loaded', 'ts_appointment_init', 0);
