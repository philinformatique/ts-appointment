<?php
/**
 * Script de migration manuelle
 * À exécuter une seule fois pour migrer les données vers client_data JSON
 */

// Charger WordPress
require_once('../../../wp-load.php');

// Vérifier les permissions
if (!current_user_can('manage_options')) {
    die('Accès refusé');
}

echo '<h1>Migration TS Appointment</h1>';
echo '<p>Début de la migration...</p>';

// Déclencher la migration
require_once('includes/class-database.php');
TS_Appointment_Database::create_tables();

echo '<p><strong>Migration terminée!</strong></p>';
echo '<p>Les colonnes hardcodées ont été migrées vers client_data JSON.</p>';
echo '<p><a href=\"/wp-admin/admin.php?page=ts-appointment-list\">Retour au tableau de bord</a></p>';
