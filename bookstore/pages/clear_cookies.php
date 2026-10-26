<?php
/**
 * Clear All Cookies & Preferences Endpoint (Lab 2 Task 5.2)
 * Demonstrates unsetting cookies with past timestamps
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/preferences.php';

clearAllPreferences();
setFlash('success', 'All client preferences and tracking cookies have been cleared successfully.');

$return = trim($_GET['return'] ?? 'dashboard.php');
if (empty($return) || strpos($return, 'http') === 0) {
    $return = 'dashboard.php';
}

header("Location: {$return}");
exit;
