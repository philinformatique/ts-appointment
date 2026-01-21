<?php
/**
 * Script d'installation - Créer les services et créneaux par défaut
 * 
 * À utiliser une seule fois après l'activation du plugin
 * Allez à la page: https://votresite.com/wp-admin/admin.php?page=ts-appointment&setup=1
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ajouter un hook d'activation pour l'initialisation
add_action('admin_init', function() {
    if (isset($_GET['page']) && $_GET['page'] === 'ts-appointment' && isset($_GET['setup'])) {
        if (current_user_can('manage_options')) {
            ts_appointment_setup_demo_data();
            wp_safe_remote_get(admin_url('admin.php?page=ts-appointment'));
        }
    }
});

/**
 * Créer les données de démonstration
 */
function ts_appointment_setup_demo_data() {
    // Créer des services
    $services = array(
        array(
            'name' => 'Consultation - 30 minutes',
            'description' => 'Consultation professionnelle de 30 minutes',
            'duration' => 30,
            'price' => wp_json_encode(array('on_site' => 25, 'remote' => 20, 'home' => 30)),
            'active' => 1,
        ),
        array(
            'name' => 'Consultation - 1 heure',
            'description' => 'Consultation professionnelle de 1 heure',
            'duration' => 60,
            'price' => wp_json_encode(array('on_site' => 50, 'remote' => 40, 'home' => 60)),
            'active' => 1,
        ),
        array(
            'name' => 'Appel visio',
            'description' => 'Consultation à distance par visioconférence',
            'duration' => 45,
            'price' => wp_json_encode(array('on_site' => 40, 'remote' => 35, 'home' => 45)),
            'active' => 1,
        ),
        array(
            'name' => 'Visite à domicile',
            'description' => 'Visite professionnelle chez le client',
            'duration' => 120,
            'price' => wp_json_encode(array('on_site' => 80, 'remote' => 0, 'home' => 100)),
            'active' => 1,
        ),
    );

    $service_ids = array();
    foreach ($services as $service) {
        $service_ids[] = TS_Appointment_Database::insert_service($service);
    }

    // Créer les créneaux pour chaque service
    // Heures de travail : Lundi à Vendredi (1-5), 9h à 18h
    foreach ($service_ids as $service_id) {
        for ($day = 1; $day <= 5; $day++) {
            global $wpdb;
            $table = $wpdb->prefix . 'ts_appointment_slots';
            
            $wpdb->insert($table, array(
                'service_id' => $service_id,
                'day_of_week' => $day,
                'start_time' => '09:00',
                'end_time' => '12:00',
                'max_appointments' => 1,
                'active' => 1,
            ));

            $wpdb->insert($table, array(
                'service_id' => $service_id,
                'day_of_week' => $day,
                'start_time' => '14:00',
                'end_time' => '18:00',
                'max_appointments' => 1,
                'active' => 1,
            ));
        }
    }

    return true;
}
