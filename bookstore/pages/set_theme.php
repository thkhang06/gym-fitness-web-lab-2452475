<?php
/**
 * Switch Interface Theme Endpoint (Lab 2 Task 5.2)
 * Sets the 'apex_theme' cookie for 30 days and redirects back to the referring page
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/preferences.php';

$theme = trim($_GET['theme'] ?? 'light');
$return = trim($_GET['return'] ?? '');

setThemePreference($theme);

// Safely sanitize return target to prevent Open Redirect
if (empty($return) || strpos($return, 'http') === 0 || strpos($return, '//') === 0) {
    $return = '../public/index.php';
}

header("Location: {$return}");
exit;
