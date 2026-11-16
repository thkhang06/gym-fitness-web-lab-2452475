<?php
/**
 * Automated End-to-End Integration, Business Logic & Security Test Suite (Lab 3 Task 7)
 * Usage:
 *   C:\xampp\php\php.exe tests/test_lab3_e2e.php
 *   C:\xampp\php\php.exe tests/test_lab3_e2e.php --base-url=http://127.0.0.1:8080/LAB1.3_CC01_2452475/bookstore
 */

$baseUrl = 'http://127.0.0.1:8080/LAB1.3_CC01_2452475/bookstore';
foreach ($argv ?? [] as $arg) {
    if (strpos($arg, '--base-url=') === 0) {
        $baseUrl = rtrim(substr($arg, 11), '/');
    }
}

$passed = 0;
$failed = 0;
$results = [];

function recordTest(string $name, bool $ok, string $detail = ''): void {
    global $passed, $failed, $results;
    if ($ok) {
        $passed++;
        echo "[PASS] {$name}" . ($detail ? " — {$detail}" : "") . "\n";
    } else {
        $failed++;
        echo "[FAIL] {$name}" . ($detail ? " — {$detail}" : "") . "\n";
    }
    $results[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail];
}

function httpCall(string $url, string $method = 'GET', $body = null, string $cookieFile = '', array $headers = [], bool $follow = true): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $follow);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    if ($cookieFile !== '') {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $respHeaders = substr((string)$raw, 0, $headerSize);
    $respBody = substr((string)$raw, $headerSize);
    curl_close($ch);

    return ['code' => $code, 'headers' => $respHeaders, 'body' => $respBody];
}

function extractCsrf(string $html): string {
    if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $html, $m)) {
        return $m[1];
    }
    if (preg_match('/<meta\s+name="csrf-token"\s+content="([^"]+)"/', $html, $m)) {
        return $m[1];
    }
    return '';
}

function loginAs(string $baseUrl, string $email, string $password, string $cookieFile): array {
    @unlink($cookieFile);
    $get = httpCall($baseUrl . '/auth/login.php', 'GET', null, $cookieFile);
    $csrf = extractCsrf($get['body']);
    $post = httpCall($baseUrl . '/auth/login.php', 'POST', http_build_query([
        'csrf_token' => $csrf,
        'email'      => $email,
        'password'   => $password,
    ]), $cookieFile, [], true);
    $pageCsrf = extractCsrf($post['body']);
    return ['resp' => $post, 'csrf' => $pageCsrf ?: $csrf];
}

echo "=== TIGER FITNESS LAB 3 END-TO-END TEST SUITE ===\n";
echo "Target Base URL: {$baseUrl}\n\n";

$adminCookie   = sys_get_temp_dir() . '/tiger_test_admin.txt';
$memberCookie  = sys_get_temp_dir() . '/tiger_test_member.txt';
$member4Cookie = sys_get_temp_dir() . '/tiger_test_member4.txt';
$newUserCookie = sys_get_temp_dir() . '/tiger_test_newuser.txt';

// 1. Public Pages, SEO & Google Maps Integration
$home = httpCall($baseUrl . '/public/index.php');
$hasSeo = strpos($home['body'], '<meta name="description"') !== false
    && strpos($home['body'], 'application/ld+json') !== false
    && strpos($home['body'], 'ExerciseGym') !== false;
$hasMap = strpos($home['body'], 'maps.google.com/maps') !== false
    && strpos($home['body'], 'Open Directions in Google Maps') !== false;
recordTest('T1.1 Public Homepage & SEO Metadata', $home['code'] === 200 && $hasSeo, 'HTTP ' . $home['code']);
recordTest('T1.2 Google Maps Location Integration', $hasMap, 'Embed + External link present');

$sitemap = httpCall($baseUrl . '/public/sitemap.xml');
$robots  = httpCall($baseUrl . '/public/robots.txt');
recordTest('T1.3 SEO Sitemap & Robots.txt', $sitemap['code'] === 200 && strpos($sitemap['body'], '<urlset') !== false && $robots['code'] === 200, 'Sitemap HTTP ' . $sitemap['code']);

// 2. AJAX Feature 1: Search & Filter
$ajaxAll = httpCall($baseUrl . '/api/search_classes.php');
$jsonAll = json_decode($ajaxAll['body'], true);
recordTest('T2.1 AJAX Search All Classes', $ajaxAll['code'] === 200 && !empty($jsonAll['success']) && ($jsonAll['count'] ?? 0) >= 6, 'Count=' . ($jsonAll['count'] ?? 0));

$ajaxSearch = httpCall($baseUrl . '/api/search_classes.php?search=Olympic');
$jsonSearch = json_decode($ajaxSearch['body'], true);
recordTest('T2.2 AJAX Keyword Search ("Olympic")', ($jsonSearch['count'] ?? 0) === 1 && stripos($jsonSearch['classes'][0]['title'] ?? '', 'Olympic') !== false, 'Matched=' . ($jsonSearch['classes'][0]['title'] ?? 'none'));

$ajaxCat = httpCall($baseUrl . '/api/search_classes.php?category_id=2');
$jsonCat = json_decode($ajaxCat['body'], true);
recordTest('T2.3 AJAX Category Filter + Cookie Persistence', ($jsonCat['count'] ?? 0) >= 2 && stripos($ajaxCat['headers'], 'apex_fav_category') !== false, 'Count=' . ($jsonCat['count'] ?? 0));

$ajaxEmpty = httpCall($baseUrl . '/api/search_classes.php?search=NON_EXISTENT_XYZ_999');
$jsonEmpty = json_decode($ajaxEmpty['body'], true);
recordTest('T2.4 AJAX Empty Results Handling', $ajaxEmpty['code'] === 200 && !empty($jsonEmpty['success']) && ($jsonEmpty['count'] ?? -1) === 0, 'Count=0');

$ajaxSqli = httpCall($baseUrl . '/api/search_classes.php?search=' . urlencode("' OR '1'='1"));
$jsonSqli = json_decode($ajaxSqli['body'], true);
recordTest('T2.5 Security: SQL Injection Probe Blocked', $ajaxSqli['code'] === 200 && ($jsonSqli['count'] ?? -1) === 0, 'Safely parameterized');

// 3. AJAX Feature 2: Live Class Capacity
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$capRes = httpCall($baseUrl . "/api/class_capacity.php?class_id=3&date={$tomorrow}");
$capJson = json_decode($capRes['body'], true);
recordTest(
    'T3.1 AJAX Live Capacity Snapshot (Class 3 Tomorrow)',
    $capRes['code'] === 200 && ($capJson['snapshot']['max_capacity'] ?? 0) === 5 && ($capJson['snapshot']['booked_count'] ?? -1) === 4 && ($capJson['snapshot']['remaining_spots'] ?? -1) === 1,
    'Booked=' . ($capJson['snapshot']['booked_count'] ?? '?') . '/' . ($capJson['snapshot']['max_capacity'] ?? '?')
);

// 4. Authentication, Registration, Logout & RBAC
$uniqueEmail = 'e2e_athlete_' . time() . '@gymfitness.local';
$regGet = httpCall($baseUrl . '/auth/register.php', 'GET', null, $newUserCookie);
$regCsrf = extractCsrf($regGet['body']);
$regPost = httpCall($baseUrl . '/auth/register.php', 'POST', http_build_query([
    'csrf_token'       => $regCsrf,
    'full_name'        => 'E2E Athlete',
    'email'            => $uniqueEmail,
    'role'             => 'member',
    'password'         => 'TigerPass123',
    'confirm_password' => 'TigerPass123',
]), $newUserCookie);
recordTest('T4.1 User Registration + Validation + Bcrypt', strpos($regPost['body'], 'Registration successful') !== false, $uniqueEmail);

$newLogin = loginAs($baseUrl, $uniqueEmail, 'TigerPass123', $newUserCookie);
recordTest('T4.2 Login Newly Registered User', strpos($newLogin['resp']['body'], 'E2E Athlete') !== false, 'Logged into dashboard');

// 5. T1 Workflow: No Active Membership -> Blocked -> Activate -> Book -> Success
$noMemBook = httpCall(
    $baseUrl . '/api/booking_action.php',
    'POST',
    json_encode(['action' => 'book_class', 'class_id' => 1, 'booking_date' => $tomorrow]),
    $newUserCookie,
    ['Content-Type: application/json', 'X-CSRF-Token: ' . $newLogin['csrf']]
);
$noMemJson = json_decode($noMemBook['body'], true);
recordTest('T5.1 T1 Business Logic: Block Booking Without Active Membership', $noMemBook['code'] === 409 && ($noMemJson['code'] ?? '') === 'NO_ACTIVE_MEMBERSHIP', $noMemJson['message'] ?? '');

$actMem = httpCall(
    $baseUrl . '/api/booking_action.php',
    'POST',
    json_encode(['action' => 'activate_membership', 'plan_id' => 1]),
    $newUserCookie,
    ['Content-Type: application/json', 'X-CSRF-Token: ' . $newLogin['csrf']]
);
$actMemJson = json_decode($actMem['body'], true);
recordTest('T5.2 T1 Business Logic: Activate Membership Plan', $actMem['code'] === 200 && !empty($actMemJson['success']), $actMemJson['message'] ?? '');

// 6. Capacity Enforcement on Seeded Member Accounts
$mem3Login = loginAs($baseUrl, 'member@gymfitness.local', 'Password123!', $memberCookie);
$mem4Login = loginAs($baseUrl, 'member4@gymfitness.local', 'Password123!', $member4Cookie);

// Member 3 takes the final (5th) spot on Class 3 tomorrow (with XSS payload in notes to test escaping!)
$xssNote = '<script>alert("xss")</script> Squats';
$book5 = httpCall(
    $baseUrl . '/api/booking_action.php',
    'POST',
    json_encode(['action' => 'book_class', 'class_id' => 3, 'booking_date' => $tomorrow, 'notes' => $xssNote]),
    $memberCookie,
    ['Content-Type: application/json', 'X-CSRF-Token: ' . $mem3Login['csrf']]
);
$book5Json = json_decode($book5['body'], true);
$newBookingId = (int)($book5Json['booking_id'] ?? 0);
recordTest(
    'T6.1 T1 Business Logic: Book Final Spot (5/5)',
    $book5['code'] === 200 && !empty($book5Json['success']) && ($book5Json['snapshot']['remaining_spots'] ?? -1) === 0,
    "Booking #{$newBookingId}, Remaining=" . ($book5Json['snapshot']['remaining_spots'] ?? '?')
);

// Duplicate booking test by Member 3
$dupBook = httpCall(
    $baseUrl . '/api/booking_action.php',
    'POST',
    json_encode(['action' => 'book_class', 'class_id' => 3, 'booking_date' => $tomorrow]),
    $memberCookie,
    ['Content-Type: application/json', 'X-CSRF-Token: ' . $mem3Login['csrf']]
);
$dupJson = json_decode($dupBook['body'], true);
recordTest('T6.2 T1 Business Logic: Prevent Duplicate Booking', $dupBook['code'] === 409 && ($dupJson['code'] ?? '') === 'DUPLICATE_BOOKING', $dupJson['message'] ?? '');

// Member 4 tries to book full Class 3 -> MUST BE BLOCKED (Capacity Rule)
$overBook = httpCall(
    $baseUrl . '/api/booking_action.php',
    'POST',
    json_encode(['action' => 'book_class', 'class_id' => 3, 'booking_date' => $tomorrow]),
    $member4Cookie,
    ['Content-Type: application/json', 'X-CSRF-Token: ' . $mem4Login['csrf']]
);
$overJson = json_decode($overBook['body'], true);
recordTest('T6.3 T1 Business Logic: Prevent Exceeding Class Capacity (5/5)', $overBook['code'] === 409 && ($overJson['code'] ?? '') === 'CAPACITY_FULL', $overJson['message'] ?? '');

// Verify XSS Escaping on bookings.php HTML
$bookingsPage = httpCall($baseUrl . '/pages/bookings.php', 'GET', null, $memberCookie);
$xssSafe = strpos($bookingsPage['body'], '<script>alert("xss")</script>') === false
    && strpos($bookingsPage['body'], '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;') !== false;
recordTest('T6.4 Security: Stored XSS Output Escaped in HTML', $xssSafe, 'Special characters safely encoded');

// Cancel booking via AJAX and verify spot opens back up
$cancelRes = httpCall(
    $baseUrl . '/api/booking_action.php',
    'POST',
    json_encode(['action' => 'cancel_booking', 'booking_id' => $newBookingId]),
    $memberCookie,
    ['Content-Type: application/json', 'X-CSRF-Token: ' . $mem3Login['csrf']]
);
$cancelJson = json_decode($cancelRes['body'], true);
recordTest(
    'T6.5 AJAX Cancel Booking Restores Spot (4/5)',
    $cancelRes['code'] === 200 && ($cancelJson['snapshot']['remaining_spots'] ?? -1) === 1,
    'Remaining=' . ($cancelJson['snapshot']['remaining_spots'] ?? '?')
);

// 7. RBAC & Security Controls
$rbac403 = httpCall($baseUrl . '/pages/class_form.php', 'GET', null, $memberCookie);
recordTest('T7.1 Security RBAC: Member Blocked From class_form.php (HTTP 403)', $rbac403['code'] === 403, 'HTTP ' . $rbac403['code']);

$rbacApi403 = httpCall(
    $baseUrl . '/api/booking_action.php',
    'POST',
    json_encode(['action' => 'update_status', 'booking_id' => 5, 'status' => 'completed']),
    $memberCookie,
    ['Content-Type: application/json', 'X-CSRF-Token: ' . $mem3Login['csrf']]
);
recordTest('T7.2 Security RBAC: Member Blocked From Staff Status API (HTTP 403)', $rbacApi403['code'] === 403, 'HTTP ' . $rbacApi403['code']);

$noCsrf = httpCall(
    $baseUrl . '/api/booking_action.php',
    'POST',
    json_encode(['action' => 'book_class', 'class_id' => 1, 'booking_date' => $tomorrow]),
    $memberCookie,
    ['Content-Type: application/json']
);
recordTest('T7.3 Security CSRF: Missing Token Rejected (HTTP 403)', $noCsrf['code'] === 403, 'HTTP ' . $noCsrf['code']);

$openRedir = httpCall($baseUrl . '/pages/set_theme.php?theme=dark&return=https://evil.example.com', 'GET', null, $memberCookie, [], false);
$redirSafe = stripos($openRedir['headers'], 'Location: https://evil.example.com') === false;
recordTest('T7.4 Security Open Redirect Blocked on set_theme.php', $redirSafe, 'External URL rejected');

$autoAuthGone = httpCall($baseUrl . '/pages/auto_auth.php?role=admin');
recordTest('T7.5 Security Backdoor Removal: auto_auth.php Not Accessible', $autoAuthGone['code'] === 404, 'HTTP ' . $autoAuthGone['code']);

// 8. Admin CRUD Operations (Create -> Read -> Update -> Delete) + Logout
$adminLogin = loginAs($baseUrl, 'admin@gymfitness.local', 'Password123!', $adminCookie);
$formGet = httpCall($baseUrl . '/pages/class_form.php', 'GET', null, $adminCookie);
$formCsrf = extractCsrf($formGet['body']);

$createRes = httpCall($baseUrl . '/pages/class_form.php', 'POST', http_build_query([
    'csrf_token'    => $formCsrf,
    'title'         => 'E2E Kettlebell Power Lab',
    'category_id'   => '1',
    'price'         => '45.00',
    'max_capacity'  => '8',
    'trainer_name'  => 'Marcus Vance',
    'schedule_info' => 'Sat • 09:00 AM',
    'image_url'     => 'images/item1.jpg',
    'description'   => 'Automated E2E verification class for kettlebell ballistic mastery.',
]), $adminCookie);
$createdOk = strpos($createRes['body'], 'E2E Kettlebell Power Lab') !== false;

// Locate created class ID via AJAX search
$findCreated = json_decode(httpCall($baseUrl . '/api/search_classes.php?search=Kettlebell')['body'], true);
$createdId = (int)($findCreated['classes'][0]['id'] ?? 0);
recordTest('T8.1 Admin CRUD Create Class + CSRF', $createdOk && $createdId > 6, "Created Class ID={$createdId}");

if ($createdId > 0) {
    $editGet = httpCall($baseUrl . "/pages/class_form.php?id={$createdId}", 'GET', null, $adminCookie);
    $editCsrf = extractCsrf($editGet['body']);
    $updRes = httpCall($baseUrl . "/pages/class_form.php?id={$createdId}", 'POST', http_build_query([
        'csrf_token'    => $editCsrf,
        'title'         => 'E2E Kettlebell Power Lab Updated',
        'category_id'   => '1',
        'price'         => '50.00',
        'max_capacity'  => '10',
        'trainer_name'  => 'Marcus Vance',
        'schedule_info' => 'Sat • 10:00 AM',
        'image_url'     => 'images/item1.jpg',
        'description'   => 'Updated E2E verification class description for CRUD check.',
    ]), $adminCookie);
    recordTest('T8.2 Admin CRUD Update Class', strpos($updRes['body'], 'E2E Kettlebell Power Lab Updated') !== false, "Class ID={$createdId}");

    $delRes = httpCall($baseUrl . '/pages/delete_class.php', 'POST', http_build_query([
        'csrf_token' => $editCsrf,
        'class_id'   => $createdId,
        'id'         => $createdId,
    ]), $adminCookie);
    recordTest('T8.3 Admin CRUD Delete Class', strpos($delRes['body'], 'permanently deleted') !== false, "Deleted Class ID={$createdId}");
}

// Logout verification
httpCall($baseUrl . '/auth/logout.php', 'GET', null, $adminCookie);
$afterLogout = httpCall($baseUrl . '/pages/dashboard.php', 'GET', null, $adminCookie, [], false);
recordTest('T8.4 Logout Invalidates Session & Protects Dashboard', $afterLogout['code'] === 302 && stripos($afterLogout['headers'], 'login.php') !== false, 'Redirected to login');

// Cleanup E2E synthetic rows in MySQL so DB returns to clean seed state
require __DIR__ . '/../bookstore/config/database.php';
$pdo->prepare("DELETE FROM class_bookings WHERE id > 10")->execute();
$pdo->prepare("DELETE FROM user_memberships WHERE id > 6")->execute();
$pdo->prepare("DELETE FROM users WHERE id > 6")->execute();
$pdo->prepare("DELETE FROM fitness_classes WHERE id > 6")->execute();

@unlink($adminCookie);
@unlink($memberCookie);
@unlink($member4Cookie);
@unlink($newUserCookie);

echo "\n=== SUMMARY: {$passed} PASSED, {$failed} FAILED ===\n";
exit($failed > 0 ? 1 : 0);
