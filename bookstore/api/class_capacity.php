<?php
/**
 * AJAX Endpoint: Live Class Capacity & Multi-Day Availability Checker (Lab 3 Task 2)
 * Checks real-time spots remaining in MySQL without full page reload.
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/booking_service.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed. Use GET.']);
    exit;
}

$classId = filter_var($_GET['class_id'] ?? null, FILTER_VALIDATE_INT);
$dateStr = trim($_GET['date'] ?? date('Y-m-d', strtotime('+1 day')));

if (!$classId || $classId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing or invalid class_id parameter.']);
    exit;
}

if (!isValidBookingDate($dateStr)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid date format. Expected YYYY-MM-DD.']);
    exit;
}

try {
    $currentUser = getCurrentUser();
    $userId = $currentUser ? (int)$currentUser['id'] : null;

    $snapshot = getClassCapacitySnapshot($pdo, $classId, $dateStr, $userId);
    if (!$snapshot) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Fitness class not found.']);
        exit;
    }

    // Build 4-day upcoming availability forecast for interactive date selector UI
    $upcoming = [];
    for ($offset = 0; $offset < 4; $offset++) {
        $d = date('Y-m-d', strtotime("+{$offset} days"));
        $daySnap = getClassCapacitySnapshot($pdo, $classId, $d, $userId);
        if ($daySnap) {
            $upcoming[] = [
                'date'            => $d,
                'label'           => ($offset === 0 ? 'Today' : ($offset === 1 ? 'Tomorrow' : date('D, M j', strtotime($d)))),
                'remaining_spots' => $daySnap['remaining_spots'],
                'max_capacity'    => $daySnap['max_capacity'],
                'is_full'         => $daySnap['is_full'],
            ];
        }
    }

    $membership = $userId ? getUserActiveMembership($pdo, $userId) : null;

    echo json_encode([
        'success'      => true,
        'is_logged_in' => isLoggedIn(),
        'snapshot'     => $snapshot,
        'upcoming'     => $upcoming,
        'membership'   => $membership,
    ]);
} catch (PDOException $e) {
    error_log('AJAX class_capacity error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to retrieve live capacity status.']);
}
