<?php
/**
 * Interface frontend
 */

if (!defined('ABSPATH')) {
    exit;
}

class TS_Appointment_Frontend {
    
    public static function register_shortcodes() {
        add_shortcode('ts_appointment_form', array(__CLASS__, 'render_booking_form'));
        add_shortcode('ts_appointment_calendar', array(__CLASS__, 'render_calendar'));
    }

    public static function render_booking_form() {
        ob_start();
        include TS_APPOINTMENT_DIR . 'templates/frontend-booking-form.php';
        return ob_get_clean();
    }

    public static function render_calendar() {
        ob_start();
        include TS_APPOINTMENT_DIR . 'templates/frontend-calendar.php';
        return ob_get_clean();
    }
}

add_action('init', array('TS_Appointment_Frontend', 'register_shortcodes'));
