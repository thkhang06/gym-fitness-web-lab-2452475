<?php
/**
 * User Logout Script (Lab 2 Task 4.3)
 * Completely invalidates session, removes session cookie, and redirects to login
 */
require_once __DIR__ . '/../config/session.php';

// Unset all session variables
$_SESSION = [];

// Clear the session cookie from the client browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session storage on server
session_destroy();

// Disable browser caching for the redirect
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to login page with logged_out parameter
header("Location: login.php?logged_out=1");
exit;
