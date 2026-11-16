<?php
/**
 * AJAX Endpoint: Live Search & Multi-Criteria Filter for Fitness Classes (Lab 3 Task 1)
 * Supports filtering by keyword (title/description/trainer), category, trainer, availability, date, and sort order.
 * Returns structured JSON using PDO Prepared Statements.
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/preferences.php';
require_once __DIR__ . '/../config/booking_service.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed. Use GET.']);
    exit;
}

$search       = trim($_GET['search'] ?? '');
$categoryId   = filter_var($_GET['category_id'] ?? null, FILTER_VALIDATE_INT);
$trainer      = trim($_GET['trainer'] ?? '');
$availability = trim($_GET['availability'] ?? 'all');
$sort         = trim($_GET['sort'] ?? 'newest');
$bookingDate  = trim($_GET['date'] ?? date('Y-m-d', strtotime('+1 day')));

if (!isValidBookingDate($bookingDate)) {
    $bookingDate = date('Y-m-d', strtotime('+1 day'));
}

// Track favorite category cookie if user filters by a valid category (Task 5.2 continuity)
if ($categoryId) {
    try {
        $catCheck = $pdo->prepare("SELECT id, name FROM categories WHERE id = :id LIMIT 1");
        $catCheck->execute(['id' => $categoryId]);
        $catRow = $catCheck->fetch();
        if ($catRow) {
            trackFavoriteCategory((int)$catRow['id'], $catRow['name']);
        }
    } catch (PDOException $e) {
        error_log('Favorite category cookie check failed: ' . $e->getMessage());
    }
}

$sql = "
    SELECT
        fc.id,
        fc.category_id,
        fc.title,
        fc.description,
        fc.trainer_name,
        fc.schedule_info,
        fc.price,
        fc.max_capacity,
        fc.image_url,
        fc.created_at,
        c.name AS category_name,
        COALESCE(bk.booked_count, 0) AS booked_count,
        GREATEST(0, fc.max_capacity - COALESCE(bk.booked_count, 0)) AS remaining_spots
    FROM fitness_classes fc
    INNER JOIN categories c ON fc.category_id = c.id
    LEFT JOIN (
        SELECT class_id, COUNT(*) AS booked_count
        FROM class_bookings
        WHERE booking_date = :booking_date
          AND status = 'confirmed'
        GROUP BY class_id
    ) bk ON fc.id = bk.class_id
    WHERE 1=1
";

$params = ['booking_date' => $bookingDate];

if ($search !== '') {
    $sql .= " AND (fc.title LIKE :searchTitle OR fc.description LIKE :searchDesc OR fc.trainer_name LIKE :searchTrainer OR c.name LIKE :searchCat)";
    $like = "%{$search}%";
    $params['searchTitle']   = $like;
    $params['searchDesc']    = $like;
    $params['searchTrainer'] = $like;
    $params['searchCat']     = $like;
}

if ($categoryId !== false && $categoryId !== null && $categoryId > 0) {
    $sql .= " AND fc.category_id = :catId";
    $params['catId'] = $categoryId;
}

if ($trainer !== '') {
    $sql .= " AND fc.trainer_name = :trainerName";
    $params['trainerName'] = $trainer;
}

if ($availability === 'available') {
    $sql .= " AND (fc.max_capacity - COALESCE(bk.booked_count, 0)) > 0";
} elseif ($availability === 'full') {
    $sql .= " AND (fc.max_capacity - COALESCE(bk.booked_count, 0)) <= 0";
}

switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY fc.price ASC, fc.id ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY fc.price DESC, fc.id ASC";
        break;
    case 'spots_desc':
        $sql .= " ORDER BY remaining_spots DESC, fc.id ASC";
        break;
    case 'newest':
    default:
        $sql .= " ORDER BY fc.id DESC";
        break;
}

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $classes = [];
    foreach ($rows as $row) {
        $maxCap = max(1, (int)$row['max_capacity']);
        $booked = (int)$row['booked_count'];
        $remaining = max(0, $maxCap - $booked);
        $classes[] = [
            'id'                => (int)$row['id'],
            'category_id'       => (int)$row['category_id'],
            'category_name'     => $row['category_name'],
            'title'             => $row['title'],
            'description'       => $row['description'],
            'short_description' => mb_strimwidth($row['description'], 0, 140, '...'),
            'trainer_name'      => $row['trainer_name'],
            'schedule_info'     => $row['schedule_info'],
            'price'             => (float)$row['price'],
            'price_formatted'   => number_format((float)$row['price'], 2),
            'image_url'         => $row['image_url'] ?: 'images/item1.jpg',
            'max_capacity'      => $maxCap,
            'booked_count'      => $booked,
            'remaining_spots'   => $remaining,
            'is_full'           => ($remaining <= 0),
        ];
    }

    echo json_encode([
        'success'      => true,
        'date'         => $bookingDate,
        'count'        => count($classes),
        'is_staff'     => hasRole(['admin', 'trainer']),
        'is_logged_in' => isLoggedIn(),
        'classes'      => $classes,
    ]);
} catch (PDOException $e) {
    error_log('AJAX search_classes error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to query fitness classes. Please try again.'
    ]);
}
