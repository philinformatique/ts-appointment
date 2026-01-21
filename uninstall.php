<?php
/**
 * Désinstallation du plugin - Suppression des données
 * Ce fichier est exécuté automatiquement lors de la suppression du plugin
 */

// Sécurité : vérifier que c'est bien WordPress qui appelle ce fichier
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Supprimer les tables
$tables = array(
    $wpdb->prefix . 'ts_appointment_services',
    $wpdb->prefix . 'ts_appointment_slots',
    $wpdb->prefix . 'ts_appointment_appointments',
    $wpdb->prefix . 'ts_appointment_settings',
    $wpdb->prefix . 'ts_appointment_google_sync',
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

// Supprimer toutes les options du plugin
$options = array(
    'ts_appointment_business_name',
    'ts_appointment_business_email',
    'ts_appointment_business_address',
    'ts_appointment_business_phone',
    'ts_appointment_timezone',
    'ts_appointment_max_days_ahead',
    'ts_appointment_color_primary',
    'ts_appointment_color_secondary',
    'ts_appointment_currency_symbol',
    'ts_appointment_currency_position',
    'ts_appointment_enable_reminders',
    'ts_appointment_reminder_hours',
    'ts_appointment_google_calendar_enabled',
    'ts_appointment_google_calendar_id',
    'ts_appointment_google_client_id',
    'ts_appointment_google_client_secret',
    'ts_appointment_google_access_token',
    'ts_appointment_google_refresh_token',
    'ts_appointment_locations_config',
    'ts_appointment_form_schema',
);

foreach ($options as $option) {
    delete_option($option);
}

// Nettoyer les métadonnées transients si nécessaire
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ts_appointment_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_ts_appointment_%'");
