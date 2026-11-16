<?php
/**
 * AJAX Endpoint: T1 Workflow Mutations (Book Class, Cancel/Update Booking, Activate Membership)
 * Protected by Authentication, Role-Based Access Control (RBAC), CSRF Validation & DB Transactions.
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/booking_service.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed. Use POST.']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'code'    => 'UNAUTHENTICATED',
        'message' => 'Please log in to manage memberships or book fitness classes.'
    ]);
    exit;
}

// Parse JSON body if Content-Type is application/json, otherwise fall back to $_POST
$rawBody = file_get_contents('php://input');
$payload = $_POST;
if (empty($payload) && is_string($rawBody) && trim($rawBody) !== '') {
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}

// Enforce CSRF Token validation (accepts header X-CSRF-Token or body csrf_token)
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($payload['csrf_token'] ?? null);
if (!verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'code'    => 'INVALID_CSRF',
        'message' => 'Security token verification failed. Please reload the page.'
    ]);
    exit;
}

$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];
$action = trim((string)($payload['action'] ?? ''));

switch ($action) {
    case 'activate_membership':
        $planId = filter_var($payload['plan_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$planId || $planId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'code' => 'INVALID_PLAN', 'message' => 'Invalid membership plan selected.']);
            exit;
        }
        $res = activateUserMembership($pdo, $userId, $planId);
        if (!$res['ok']) {
            http_response_code(400);
        }
        echo json_encode([
            'success'    => $res['ok'],
            'code'       => $res['code'] ?? 'OK',
            'message'    => $res['message'],
            'membership' => $res['membership'] ?? null,
        ]);
        exit;

    case 'book_class':
        $classId     = filter_var($payload['class_id'] ?? null, FILTER_VALIDATE_INT);
        $bookingDate = trim((string)($payload['booking_date'] ?? ''));
        $notes       = (string)($payload['notes'] ?? '');

        if (!$classId || $classId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'code' => 'INVALID_CLASS', 'message' => 'Please select a valid fitness class.']);
            exit;
        }

        $res = createClassBooking($pdo, $userId, $classId, $bookingDate, $notes);
        if (!$res['ok']) {
            $status = in_array($res['code'] ?? '', ['CAPACITY_FULL', 'DUPLICATE_BOOKING', 'MEMBERSHIP_QUOTA_REACHED', 'NO_ACTIVE_MEMBERSHIP'], true) ? 409 : 400;
            http_response_code($status);
        }
        echo json_encode([
            'success'    => $res['ok'],
            'code'       => $res['code'] ?? 'OK',
            'message'    => $res['message'],
            'booking_id' => $res['booking_id'] ?? null,
            'snapshot'   => $res['capacity'] ?? null,
            'membership' => $res['membership'] ?? null,
        ]);
        exit;

    case 'cancel_booking':
        $bookingId = filter_var($payload['booking_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$bookingId || $bookingId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'code' => 'INVALID_BOOKING', 'message' => 'Invalid booking ID.']);
            exit;
        }
        $res = updateBookingStatus($pdo, $bookingId, 'cancelled', $currentUser);
        if (!$res['ok']) {
            http_response_code(($res['code'] ?? '') === 'FORBIDDEN' ? 403 : 400);
        }
        echo json_encode([
            'success'    => $res['ok'],
            'code'       => $res['code'] ?? 'OK',
            'message'    => $res['message'],
            'snapshot'   => $res['capacity'] ?? null,
            'membership' => getUserActiveMembership($pdo, $userId),
        ]);
        exit;

    case 'update_status':
        if (!hasRole(['admin', 'trainer'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'code' => 'FORBIDDEN', 'message' => 'Staff clearance required to update booking status.']);
            exit;
        }
        $bookingId = filter_var($payload['booking_id'] ?? null, FILTER_VALIDATE_INT);
        $newStatus = trim((string)($payload['status'] ?? ''));
        if (!$bookingId || $bookingId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'code' => 'INVALID_BOOKING', 'message' => 'Invalid booking ID.']);
            exit;
        }
        $res = updateBookingStatus($pdo, $bookingId, $newStatus, $currentUser);
        if (!$res['ok']) {
            http_response_code(400);
        }
        echo json_encode([
            'success'  => $res['ok'],
            'code'     => $res['code'] ?? 'OK',
            'message'  => $res['message'],
            'snapshot' => $res['capacity'] ?? null,
        ]);
        exit;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'code' => 'UNKNOWN_ACTION', 'message' => 'Unsupported booking action.']);
        exit;
}
