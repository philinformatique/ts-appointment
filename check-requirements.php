#!/usr/bin/env php
<?php
/**
 * Script de vérification des prérequis
 * Utilisation: php check-requirements.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "========================================\n";
echo "TS Appointment - Vérification Prérequis\n";
echo "========================================\n\n";

$passed = 0;
$failed = 0;

// Vérifier PHP
echo "[PHP]\n";
$php_version = phpversion();
$php_required = '7.2';
if (version_compare($php_version, $php_required, '>=')) {
    echo "✅ PHP {$php_version} (requis: {$php_required}+)\n";
    $passed++;
} else {
    echo "❌ PHP {$php_version} (requis: {$php_required}+)\n";
    $failed++;
}

// Extensions PHP
echo "\n[Extensions PHP]\n";
$required_extensions = array('json', 'curl', 'mysqli', 'mbstring', 'filter');
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ {$ext}\n";
        $passed++;
    } else {
        echo "❌ {$ext} - Manquante\n";
        $failed++;
    }
}

// Permissions de fichier
echo "\n[Permissions]\n";
$dirs = array('.', 'includes', 'assets', 'templates');
foreach ($dirs as $dir) {
    if (is_writable($dir)) {
        echo "✅ Répertoire './{$dir}' accessible en écriture\n";
        $passed++;
    } else {
        echo "❌ Répertoire './{$dir}' - Pas d'accès en écriture\n";
        $failed++;
    }
}

// Fichiers
echo "\n[Fichiers]\n";
$required_files = array(
    'ts-appointment.php',
    'includes/class-database.php',
    'includes/class-google-calendar.php',
    'includes/class-appointment.php',
    'assets/css/frontend.css',
    'assets/js/frontend.js'
);

foreach ($required_files as $file) {
    if (file_exists($file)) {
        echo "✅ {$file}\n";
        $passed++;
    } else {
        echo "❌ {$file} - Manquant\n";
        $failed++;
    }
}

// Vérification de syntaxe PHP
echo "\n[Syntaxe PHP]\n";
$php_files = array(
    'ts-appointment.php',
    'includes/class-database.php',
    'includes/class-google-calendar.php',
    'includes/class-appointment.php',
    'includes/class-admin.php',
    'includes/class-frontend.php',
    'includes/class-rest-api.php',
    'includes/class-email.php',
);

foreach ($php_files as $file) {
    if (!file_exists($file)) {
        continue;
    }
    
    $output = array();
    $return = 0;
    exec("php -l {$file} 2>&1", $output, $return);
    
    if ($return === 0) {
        echo "✅ {$file}\n";
        $passed++;
    } else {
        echo "❌ {$file}\n";
        foreach ($output as $line) {
            echo "   {$line}\n";
        }
        $failed++;
    }
}

// Résumé
echo "\n========================================\n";
echo "Résumé:\n";
echo "  ✅ Passé: {$passed}\n";
echo "  ❌ Échoué: {$failed}\n";

if ($failed === 0) {
    echo "\n✅ Tous les prérequis sont satisfaits !\n";
    exit(0);
} else {
    echo "\n❌ Certains prérequis ne sont pas satisfaits.\n";
    exit(1);
}
