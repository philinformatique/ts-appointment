/**
 * Configuration pour l'environnement de développement
 * Fichier: config.php
 */

if (!defined('TS_APPOINTMENT_CONFIG_LOADED')) {
    define('TS_APPOINTMENT_CONFIG_LOADED', true);

    // ==================== Mode Debug ====================
    define('TS_APPOINTMENT_DEBUG', false); // Mettez à true pour développement
    
    // ==================== Cache ====================
    define('TS_APPOINTMENT_CACHE_ENABLED', true);
    define('TS_APPOINTMENT_CACHE_TTL', 3600); // 1 heure
    
    // ==================== Google Calendar ====================
    define('TS_APPOINTMENT_GOOGLE_API_TIMEOUT', 30);
    define('TS_APPOINTMENT_GOOGLE_API_RETRIES', 3);
    
    // ==================== Email ====================
    define('TS_APPOINTMENT_EMAIL_RETRY_LIMIT', 3);
    define('TS_APPOINTMENT_EMAIL_RETRY_INTERVAL', 60); // secondes
    
    // ==================== Sécurité ====================
    define('TS_APPOINTMENT_NONCE_LIFETIME', 86400); // 24 heures
    define('TS_APPOINTMENT_MAX_UPLOAD_SIZE', 5242880); // 5MB
    
    // ==================== Paramètres par défaut ====================
    if (!function_exists('ts_appointment_get_default_settings')) {
        function ts_appointment_get_default_settings() {
            return array(
                'business_name' => get_bloginfo('name'),
                'business_email' => get_bloginfo('admin_email'),
                'business_phone' => '',
                'business_address' => '',
                'timezone' => get_option('timezone_string', 'UTC'),
                'max_days_ahead' => 30,
                // 'appointment_buffer' removed from settings UI
                'enable_reminders' => 1,
                'reminder_hours' => 24,
                'color_primary' => '#007cba',
                'color_secondary' => '#f0f0f0',
                'google_calendar_enabled' => 0,
                'date_format' => get_option('date_format', 'j/m/Y'),
                'time_format' => get_option('time_format', 'H:i'),
            );
        }
    }

    // ==================== Fonctions utilitaires ====================
    // Logging helper is provided by includes/helpers.php. Do not redefine here to avoid duplication.

    if (!function_exists('ts_appointment_cache_get')) {
        function ts_appointment_cache_get($key) {
            if (!TS_APPOINTMENT_CACHE_ENABLED) return false;
            return get_transient('ts_appointment_' . $key);
        }
    }

    if (!function_exists('ts_appointment_cache_set')) {
        function ts_appointment_cache_set($key, $value, $ttl = null) {
            if (!TS_APPOINTMENT_CACHE_ENABLED) return false;
            $ttl = $ttl ?? TS_APPOINTMENT_CACHE_TTL;
            return set_transient('ts_appointment_' . $key, $value, $ttl);
        }
    }

    if (!function_exists('ts_appointment_cache_delete')) {
        function ts_appointment_cache_delete($key) {
            return delete_transient('ts_appointment_' . $key);
        }
    }
}
