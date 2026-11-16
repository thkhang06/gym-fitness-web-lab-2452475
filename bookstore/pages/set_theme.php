<?php
/**
 * Switch Interface Theme Endpoint (Lab 2 Task 5.2 & Lab 3 Security Hardening)
 * Sets the 'apex_theme' cookie for 30 days and redirects safely
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/preferences.php';

$theme = trim($_GET['theme'] ?? 'light');
$return = trim($_GET['return'] ?? '');

setThemePreference($theme);

if (!isSafeLocalRedirect($return)) {
    $return = '../public/index.php';
}

header("Location: {$return}");
exit;
