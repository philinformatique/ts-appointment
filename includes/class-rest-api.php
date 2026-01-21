<?php
/**
 * API REST
 */

if (!defined('ABSPATH')) {
    exit;
}

class TS_Appointment_REST_API {
    
    public static function register_routes() {
        register_rest_route('ts-appointment/v1', '/appointment/book', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'book_appointment'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('ts-appointment/v1', '/appointment/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_appointment'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('ts-appointment/v1', '/services', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_services'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('ts-appointment/v1', '/available-slots', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'get_available_slots'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('ts-appointment/v1', '/appointment/(?P<id>\d+)/confirm', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'confirm_appointment'),
            'permission_callback' => array(__CLASS__, 'check_admin_permission'),
        ));

        register_rest_route('ts-appointment/v1', '/appointment/(?P<id>\d+)/cancel', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'cancel_appointment'),
            'permission_callback' => array(__CLASS__, 'check_admin_permission'),
        ));
    }

    public static function book_appointment($request) {
        $params = $request->get_json_params();
        $result = TS_Appointment_Manager::book_appointment($params);
        
        return new WP_REST_Response($result, $result['success'] ? 200 : 400);
    }

    public static function get_appointment($request) {
        $id = intval($request['id']);
        $appointment = TS_Appointment_Database::get_appointment($id);
        
        if (!$appointment) {
            return new WP_REST_Response(array('message' => 'Not found'), 404);
        }

        return new WP_REST_Response($appointment);
    }

    public static function get_services() {
        $services = TS_Appointment_Database::get_services();
        return new WP_REST_Response($services);
    }

    public static function get_available_slots($request) {
        // S'assurer que les tables existent avant d'interroger
        TS_Appointment_Database::maybe_create_tables();

        $service_id = intval($request->get_param('service_id'));
        $date = sanitize_text_field($request->get_param('date'));
        
        error_log('TS Appointment: get_available_slots called with service_id=' . $service_id . ', date=' . $date);
        
        if (!$service_id || !$date) {
            error_log('TS Appointment: Missing params - service_id=' . $service_id . ', date=' . $date);
            return new WP_REST_Response(array('message' => __('Paramètres manquants.', 'ts-appointment')), 400);
        }

        $service = TS_Appointment_Database::get_service($service_id);
        if (!$service) {
            error_log('TS Appointment: Service not found - service_id=' . $service_id);
            return new WP_REST_Response(array('message' => __('Service introuvable.', 'ts-appointment')), 404);
        }

        try {
            $slots = TS_Appointment_Database::get_available_slots($service_id, $date);
            error_log('TS Appointment: Slots found - count=' . count($slots));
            return new WP_REST_Response($slots);
        } catch (Exception $e) {
            error_log('TS Appointment: Exception - ' . $e->getMessage());
            return new WP_REST_Response(array('message' => __('Erreur interne: ', 'ts-appointment') . $e->getMessage()), 500);
        }
    }

    public static function confirm_appointment($request) {
        $id = intval($request['id']);
        try {
            $result = TS_Appointment_Manager::confirm_appointment($id);
            return new WP_REST_Response(array('success' => $result));
        } catch (Exception $e) {
            error_log('TS Appointment: confirm_appointment failed - ' . $e->getMessage());
            return new WP_REST_Response(array('success' => false, 'message' => __('Erreur serveur lors de la confirmation.', 'ts-appointment')), 500);
        }
    }

    public static function cancel_appointment($request) {
        $id = intval($request['id']);
        $params = $request->get_json_params();
        $reason = isset($params['reason']) ? sanitize_text_field($params['reason']) : '';
        
        $result = TS_Appointment_Manager::cancel_appointment($id, $reason);
        
        return new WP_REST_Response(array('success' => $result));
    }

    public static function check_admin_permission() {
        return current_user_can('manage_options');
    }
}

add_action('rest_api_init', array('TS_Appointment_REST_API', 'register_routes'));
