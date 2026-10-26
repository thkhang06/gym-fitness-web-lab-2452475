<?php
/**
 * Database Connection Configuration
 * Project: Gym & Fitness Center Website (Lab 2 - Task 1 & 2)
 * Technology: PHP PDO with MySQL / MariaDB
 */

$host = 'localhost';
$dbname = 'gym_fitness_db';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Record internal error message into system log
    error_log("Database Connection Error: " . $e->getMessage());

    // Present user-friendly error response without exposing database credentials
    http_response_code(500);
    echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Notice - Gym & Fitness Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="card shadow-sm border-0 text-center p-4" style="max-width: 520px;">
        <div class="card-body">
            <div class="text-danger mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" fill="currentColor" class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
                    <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.15.15 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.2.2 0 0 1-.054.06.1.1 0 0 1-.066.017H1.146a.1.1 0 0 1-.066-.017.2.2 0 0 1-.054-.06c-.033-.059-.034-.123.002-.183L7.884 2.073a.15.15 0 0 1 .054-.057zm1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.71c.889 0 1.438-.99.98-1.767z"/>
                    <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                </svg>
            </div>
            <h4 class="card-title text-danger mb-2">Service Temporarily Unavailable</h4>
            <p class="text-secondary mb-4">We are currently experiencing technical difficulties connecting to our database services. Please try again in a few moments.</p>
            <a href="javascript:location.reload();" class="btn btn-outline-danger btn-sm">Reload Page</a>
        </div>
    </div>
</body>
</html>';
    exit;
}
