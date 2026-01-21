#!/usr/bin/env php
<?php
/**
 * Tests unitaires basiques pour TS Appointment
 * Utilisation: php tests.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "========================================\n";
echo "TS Appointment - Tests Unitaires\n";
echo "========================================\n\n";

$tests_passed = 0;
$tests_failed = 0;

// Fonction utilitaire pour les tests
function assert_true($condition, $message) {
    global $tests_passed, $tests_failed;
    if ($condition) {
        echo "✅ {$message}\n";
        $tests_passed++;
    } else {
        echo "❌ {$message}\n";
        $tests_failed++;
    }
}

function assert_equals($actual, $expected, $message) {
    global $tests_passed, $tests_failed;
    if ($actual === $expected) {
        echo "✅ {$message}\n";
        $tests_passed++;
    } else {
        echo "❌ {$message} (Attendu: {$expected}, Reçu: {$actual})\n";
        $tests_failed++;
    }
}

// ==================== Tests ====================

echo "[Configuration]\n";
$config_file = __DIR__ . '/config.php';
assert_true(file_exists($config_file), "Fichier config.php existe");

echo "\n[Structure des répertoires]\n";
$required_dirs = array('includes', 'assets', 'templates', 'assets/css', 'assets/js');
foreach ($required_dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    assert_true(is_dir($path), "Répertoire {$dir} existe");
}

echo "\n[Fichiers principaux]\n";
$required_files = array(
    'ts-appointment.php',
    'README.md',
    'QUICKSTART.md',
    'includes/class-database.php',
    'includes/class-admin.php',
    'assets/css/frontend.css',
    'assets/js/frontend.js',
);
foreach ($required_files as $file) {
    $path = __DIR__ . '/' . $file;
    assert_true(file_exists($path), "Fichier {$file} existe");
}

echo "\n[Syntaxe PHP]\n";
$php_files = array(
    'ts-appointment.php',
    'config.php',
    'includes/class-database.php',
    'includes/class-google-calendar.php',
    'includes/class-appointment.php',
    'includes/class-admin.php',
    'includes/class-frontend.php',
    'includes/class-rest-api.php',
    'includes/class-email.php',
);

foreach ($php_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (!file_exists($path)) continue;
    
    $output = array();
    $return = 0;
    exec("php -l {$path} 2>&1", $output, $return);
    assert_equals($return, 0, "Syntaxe valide pour {$file}");
}

echo "\n[Permissions]\n";
$writable_dirs = array('includes', 'assets', 'templates');
foreach ($writable_dirs as $dir) {
    $path = __DIR__ . '/' . $dir;
    assert_true(is_writable($path), "Répertoire {$dir} accessible en écriture");
}

echo "\n[Contenu des fichiers]\n";
$main_file = file_get_contents(__DIR__ . '/ts-appointment.php');
assert_true(strpos($main_file, 'class TS_Appointment') !== false, "Classe TS_Appointment trouvée");
assert_true(strpos($main_file, 'register_activation_hook') !== false, "Hook d'activation trouvé");
assert_true(strpos($main_file, 'register_deactivation_hook') !== false, "Hook de désactivation trouvé");

echo "\n[Structure du code]\n";
$database_file = file_get_contents(__DIR__ . '/includes/class-database.php');
assert_true(strpos($database_file, 'class TS_Appointment_Database') !== false, "Classe Database trouvée");
assert_true(strpos($database_file, 'create_tables') !== false, "Méthode create_tables trouvée");

$google_file = file_get_contents(__DIR__ . '/includes/class-google-calendar.php');
assert_true(strpos($google_file, 'class TS_Appointment_Google_Calendar') !== false, "Classe Google Calendar trouvée");

echo "\n[Documentation]\n";
assert_true(file_exists(__DIR__ . '/README.md'), "README.md existe");
assert_true(strlen(file_get_contents(__DIR__ . '/README.md')) > 1000, "README.md contient du contenu");
assert_true(file_exists(__DIR__ . '/QUICKSTART.md'), "QUICKSTART.md existe");

echo "\n[Configuration]\n";
$composer_file = json_decode(file_get_contents(__DIR__ . '/composer.json'), true);
assert_true(!empty($composer_file), "composer.json est valide");
assert_true(isset($composer_file['name']), "composer.json a un nom");

// ==================== Résumé ====================
echo "\n========================================\n";
echo "Résumé des tests:\n";
echo "  ✅ Passé: {$tests_passed}\n";
echo "  ❌ Échoué: {$tests_failed}\n";
echo "========================================\n";

if ($tests_failed === 0) {
    echo "\n✅ Tous les tests sont passés !\n";
    exit(0);
} else {
    echo "\n❌ Certains tests ont échoué.\n";
    exit(1);
}
