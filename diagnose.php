<?php
/**
 * Script de diagnostic TS Appointment
 */

// Charger WordPress
$wp_load_paths = array(
    __DIR__ . '/../../../wp-load.php',
    __DIR__ . '/../../../../wp-load.php',
    __DIR__ . '/../../../../../wp-load.php',
);

$loaded = false;
foreach ($wp_load_paths as $path) {
    if (file_exists($path)) {
        require_once($path);
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    die('Impossible de charger WordPress. Ajustez les chemins.');
}

// Vérifier les permissions
if (!current_user_can('manage_options')) {
    die('Accès refusé');
}

echo '<h1>Diagnostic TS Appointment</h1>';
echo '<style>body{font-family:Arial,sans-serif;padding:20px;} .ok{color:green;} .error{color:red;} .warning{color:orange;} pre{background:#f5f5f5;padding:10px;border-radius:4px;overflow-x:auto;}</style>';

// 1. Vérifier les tables
global $wpdb;
$appointments_table = $wpdb->prefix . 'ts_appointment_appointments';

echo '<h2>1. Structure de la table appointments</h2>';
$columns = $wpdb->get_results("SHOW COLUMNS FROM $appointments_table");
echo '<table border="1" cellpadding="5" cellspacing="0">';
echo '<tr><th>Colonne</th><th>Type</th><th>Null</th><th>Défaut</th></tr>';
foreach ($columns as $col) {
    $class = '';
    if (in_array($col->Field, array('client_name', 'client_email', 'client_phone', 'client_address', 'notes'))) {
        $class = 'error';
        echo '<tr class="'.$class.'"><td>' . esc_html($col->Field) . ' ⚠️ ANCIEN</td>';
    } else {
        $class = ($col->Field === 'client_data') ? 'ok' : '';
        echo '<tr class="'.$class.'"><td>' . esc_html($col->Field) . '</td>';
    }
    echo '<td>' . esc_html($col->Type) . '</td>';
    echo '<td>' . esc_html($col->Null) . '</td>';
    echo '<td>' . esc_html($col->Default) . '</td></tr>';
}
echo '</table>';

// Vérifier si les anciennes colonnes existent
$has_old_columns = false;
foreach ($columns as $col) {
    if (in_array($col->Field, array('client_name', 'client_email', 'client_phone', 'client_address', 'notes'))) {
        $has_old_columns = true;
        break;
    }
}

if ($has_old_columns) {
    echo '<p class="error">❌ <strong>PROBLÈME:</strong> Des anciennes colonnes hardcodées existent encore!</p>';
    echo '<p><a href="migrate.php" style="background:#007cba;color:white;padding:10px 20px;text-decoration:none;border-radius:4px;display:inline-block;">Exécuter la migration</a></p>';
} else {
    echo '<p class="ok">✅ Structure correcte - pas de colonnes hardcodées</p>';
}

// 2. Vérifier le form_schema
echo '<h2>2. Schéma du formulaire</h2>';
$form_schema = get_option('ts_appointment_form_schema');
if ($form_schema) {
    $schema = json_decode($form_schema, true);
    if (is_array($schema)) {
        echo '<p class="ok">✅ Schéma trouvé (' . count($schema) . ' champs)</p>';
        echo '<table border="1" cellpadding="5" cellspacing="0">';
        echo '<tr><th>Clé</th><th>Label</th><th>Type</th><th>Obligatoire</th></tr>';
        foreach ($schema as $field) {
            echo '<tr>';
            echo '<td>' . esc_html($field['key'] ?? '') . '</td>';
            echo '<td>' . esc_html($field['label'] ?? '') . '</td>';
            echo '<td>' . esc_html($field['type'] ?? '') . '</td>';
            echo '<td>' . (!empty($field['required']) ? 'Oui' : 'Non') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="error">❌ Schéma invalide (JSON mal formé)</p>';
        echo '<pre>' . esc_html($form_schema) . '</pre>';
    }
} else {
    echo '<p class="warning">⚠️ Aucun schéma de formulaire trouvé</p>';
}

// 3. Vérifier la configuration des lieux
echo '<h2>3. Configuration des lieux</h2>';
$locations_config = get_option('ts_appointment_locations_config');
if ($locations_config) {
    $locs = json_decode($locations_config, true);
    if (is_array($locs)) {
        echo '<p class="ok">✅ Configuration trouvée (' . count($locs) . ' lieux)</p>';
        echo '<table border="1" cellpadding="5" cellspacing="0">';
        echo '<tr><th>Clé</th><th>Label</th><th>Prix</th></tr>';
        foreach ($locs as $loc) {
            echo '<tr>';
            echo '<td>' . esc_html($loc['key'] ?? '') . '</td>';
            echo '<td>' . esc_html($loc['label'] ?? '') . '</td>';
            echo '<td>' . esc_html($loc['price'] ?? '') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p class="error">❌ Configuration invalide</p>';
    }
} else {
    echo '<p class="warning">⚠️ Aucune configuration de lieux trouvée</p>';
}

// 4. Test d'insertion
echo '<h2>4. Test d\'insertion (simulation)</h2>';
$test_data = array(
    'service_id' => 1,
    'appointment_type' => 'on_site',
    'appointment_date' => date('Y-m-d', strtotime('+2 days')),
    'appointment_time' => '14:00',
    'client_name' => 'Test User',
    'client_email' => 'test@example.com',
    'client_phone' => '0123456789',
);

require_once(__DIR__ . '/includes/class-appointment.php');
$validation = TS_Appointment_Manager::validate_appointment($test_data);

if ($validation['valid']) {
    echo '<p class="ok">✅ Validation réussie</p>';
} else {
    echo '<p class="error">❌ Validation échouée: ' . esc_html($validation['message']) . '</p>';
}

// 5. Derniers rendez-vous
echo '<h2>5. Derniers rendez-vous</h2>';
$appointments = $wpdb->get_results("SELECT id, appointment_date, appointment_time, client_data FROM $appointments_table ORDER BY id DESC LIMIT 5");
if ($appointments) {
    echo '<table border="1" cellpadding="5" cellspacing="0">';
    echo '<tr><th>ID</th><th>Date</th><th>Heure</th><th>Client Data</th></tr>';
    foreach ($appointments as $apt) {
        echo '<tr>';
        echo '<td>' . intval($apt->id) . '</td>';
        echo '<td>' . esc_html($apt->appointment_date) . '</td>';
        echo '<td>' . esc_html($apt->appointment_time) . '</td>';
        echo '<td><pre>' . esc_html(substr($apt->client_data, 0, 100)) . '...</pre></td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo '<p>Aucun rendez-vous trouvé</p>';
}

echo '<hr>';
echo '<p><a href="/wp-admin/admin.php?page=ts-appointment-list">Retour au tableau de bord</a></p>';
