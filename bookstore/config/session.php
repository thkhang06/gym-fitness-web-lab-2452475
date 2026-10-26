<?php
/**
 * Session Management & Authentication Helpers
 * Project: Apex Fitness Club (Lab 2 Tasks 4 & 5.1)
 */

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if the visitor is an authenticated user
 * 
 * @return bool
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Retrieve current authenticated user data from session
 * 
 * @return array|null
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id'        => $_SESSION['user_id'] ?? null,
        'user_id'   => $_SESSION['user_id'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? 'Member',
        'email'     => $_SESSION['email'] ?? '',
        'role'      => $_SESSION['role'] ?? 'member',
    ];
}

/**
 * Set a one-time flash message in session
 * 
 * @param string $key 'success', 'error', 'warning', 'info'
 * @param string $message
 * @return void
 */
function setFlash(string $key, string $message): void {
    $_SESSION['flash'][$key] = $message;
}

/**
 * Retrieve and consume a flash message
 * 
 * @param string $key
 * @return string|null
 */
function getFlash(string $key): ?string {
    if (isset($_SESSION['flash'][$key])) {
        $message = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $message;
    }
    return null;
}

/**
 * Enforce authentication: redirects unauthenticated requests to login page
 * 
 * @param string $message
 * @return void
 */
function requireLogin(string $message = 'Please log in to access your member dashboard.'): void {
    if (!isLoggedIn()) {
        setFlash('error', $message);
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';

        // Calculate relative path to auth/login.php based on calling script directory
        $currentDir = basename(dirname($_SERVER['SCRIPT_FILENAME'] ?? ''));
        if ($currentDir === 'pages' || $currentDir === 'public') {
            $loginPath = '../auth/login.php';
        } elseif ($currentDir === 'auth') {
            $loginPath = 'login.php';
        } else {
            $loginPath = 'auth/login.php';
        }

        header("Location: {$loginPath}");
        exit;
    }
}
