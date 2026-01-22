<?php
/**
 * Helpers utilities for TS Appointment plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('ts_appointment_log')) {
    function ts_appointment_log($message, $level = 'info') {
        // Minimal safe logger when plugin config isn't loaded
        $const_debug = defined('TS_APPOINTMENT_DEBUG') && TS_APPOINTMENT_DEBUG;
        $opt_debug = false;
        if (function_exists('get_option')) {
            $opt_debug = (bool) get_option('ts_appointment_debug_enabled', false);
        }
        if (!$const_debug && !$opt_debug) {
            return;
        }

        $log_file = (defined('TS_APPOINTMENT_DIR') ? TS_APPOINTMENT_DIR : plugin_dir_path(__FILE__)) . 'debug.log';
        $timestamp = defined('WP_TIME') ? WP_TIME : current_time('mysql');
        $entry = sprintf("[%s] [%s] %s\n", $timestamp, $level, $message);
        // Attempt to write; suppress errors
        @file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);
    }
}

if (!function_exists('ts_appointment_format_duration')) {
    /**
     * Format duration given in minutes to a human readable string.
     * Examples: 90 -> "90 min", 60 -> "1 h", 120 -> "2 h", 1440 -> "1 j"
     */
    function ts_appointment_format_duration($minutes) {
        $m = intval($minutes);
        if ($m <= 0) return '0 min';
        if ($m % 1440 === 0) {
            $d = intval($m / 1440);
            return $d . ' ' . _n('jour', 'jours', $d, 'ts-appointment');
        }
        if ($m % 60 === 0 && $m >= 60) {
            $h = intval($m / 60);
            return $h . ' ' . _n('h', 'h', $h, 'ts-appointment');
        }
        return $m . ' ' . _n('min', 'min', $m, 'ts-appointment');
    }
}
