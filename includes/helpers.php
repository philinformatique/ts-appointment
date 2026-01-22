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
