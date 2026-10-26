<?php
/**
 * Session Management & Role-Based Access Control (RBAC) Helpers
 * Project: Apex Fitness Club (Lab 2 Tasks 4, 5.1 & 6)
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
 * Verify whether the authenticated user has one of the allowed roles
 * 
 * @param array|string $roles
 * @return bool
 */
function hasRole($roles): bool {
    if (!isLoggedIn()) {
        return false;
    }

    $allowedRoles = is_array($roles) ? $roles : [$roles];
    $currentRole = $_SESSION['role'] ?? '';

    return in_array($currentRole, $allowedRoles, true);
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

/**
 * Enforce Role-Based Access Control (RBAC):
 * Requires authenticated session AND one of the specified roles.
 * Emits HTTP 403 Forbidden with an informative page if unauthorized.
 * 
 * @param array|string $roles
 * @return void
 */
function requireRole($roles): void {
    // 1. Must be logged in first
    requireLogin("You must be logged in to perform this administrative action.");

    // 2. Must possess required role
    if (!hasRole($roles)) {
        http_response_code(403);
        $user = getCurrentUser();
        $allowedList = implode(', ', (array)$roles);
        $currentRole = htmlspecialchars($user['role'] ?? 'unknown');

        // Determine correct relative navigation paths
        $currentDir = basename(dirname($_SERVER['SCRIPT_FILENAME'] ?? ''));
        $dashboardPath = ($currentDir === 'pages') ? 'dashboard.php' : '../pages/dashboard.php';
        $homePath      = ($currentDir === 'public') ? 'index.php' : '../public/index.php';
        $cssPath       = ($currentDir === 'public') ? 'css/style.css' : '../public/css/style.css';

        echo '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>403 Forbidden - TIGER FITNESS</title>
  <link rel="icon" type="image/svg+xml" href="' . ($currentDir === 'public' ? 'images/favicon.svg' : '../public/images/favicon.svg') . '">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="' . $cssPath . '">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-12 col-md-8 col-lg-6 text-center">
        <div class="card border-0 shadow rounded-4 p-4 p-md-5 bg-white">
          <div class="text-danger mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="72" height="72" fill="currentColor" class="bi bi-shield-lock-fill" viewBox="0 0 16 16">
              <path fill-rule="evenodd" d="M8 0c-.69 0-1.843.265-2.928.56-1.11.3-2.229.655-2.887.87a1.54 1.54 0 0 0-1.044 1.262c-.596 4.477.787 7.795 2.465 9.99a11.8 11.8 0 0 0 2.517 2.453c.386.273.744.482 1.048.625.28.132.581.24.829.24s.548-.108.829-.24a7 7 0 0 0 1.048-.625 11.8 11.8 0 0 0 2.517-2.453c1.678-2.195 3.061-5.513 2.465-9.99a1.54 1.54 0 0 0-1.044-1.263 63 63 0 0 0-2.887-.87C9.843.266 8.69 0 8 0m0 5a1.5 1.5 0 0 1 1.5 1.5v.5H10a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h.5v-.5A1.5 1.5 0 0 1 8 5m0 1a.5.5 0 0 0-.5.5v.5h1v-.5A.5.5 0 0 0 8 6"/>
            </svg>
          </div>
          <span class="badge bg-danger-subtle text-danger border border-danger px-3 py-1 text-uppercase fw-semibold mb-2">
            HTTP 403 Forbidden
          </span>
          <h2 class="fw-bold mb-2">Access Denied</h2>
          <p class="text-secondary mb-3">
            You do not have administrative clearance to access or modify this resource.
          </p>
          <div class="bg-light rounded-3 p-3 text-start small mb-4 border">
            <div class="mb-1"><strong>Authenticated User:</strong> ' . htmlspecialchars($user['full_name']) . ' (' . htmlspecialchars($user['email']) . ')</div>
            <div class="mb-1"><strong>Your Role:</strong> <span class="badge bg-secondary text-uppercase">' . $currentRole . '</span></div>
            <div><strong>Required Role(s):</strong> <span class="badge bg-danger text-uppercase">' . htmlspecialchars($allowedList) . '</span></div>
          </div>
          <div class="d-flex justify-content-center gap-2">
            <a href="' . $dashboardPath . '" class="btn btn-danger px-4 fw-semibold">Return to Dashboard</a>
            <a href="' . $homePath . '" class="btn btn-outline-secondary px-3">Homepage</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>';
        exit;
    }
}
