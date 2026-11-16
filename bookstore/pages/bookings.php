<?php
/**
 * Membership & Class Booking Workflow Console (Lab 3 Tasks 2, 3, 4 & 5)
 * Mandatory T1 Business Flow: Membership -> Fitness Class -> Schedule -> Booking
 * Supports both Progressive Form POST (with CSRF + DB Transaction) and Live AJAX Check/Booking.
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/preferences.php';
require_once __DIR__ . '/../config/booking_service.php';

$activeTheme = getActiveTheme();
$currentUser = getCurrentUser();
$isAuth      = isLoggedIn();
$isStaff     = hasRole(['admin', 'trainer']);

$flashSuccess = getFlash('success');
$flashError   = getFlash('error');

$selectedClassId = filter_var($_REQUEST['class_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
$selectedDate    = trim($_REQUEST['date'] ?? $_POST['booking_date'] ?? date('Y-m-d', strtotime('+1 day')));
if (!isValidBookingDate($selectedDate)) {
    $selectedDate = date('Y-m-d', strtotime('+1 day'));
}

// Handle standard POST fallback for non-JS / E2E form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireLogin('Please log in to activate a membership or book a fitness class.');
    requireCsrfToken(false);

    $userId = (int)$currentUser['id'];
    $formAction = trim($_POST['form_action'] ?? '');

    if ($formAction === 'activate_membership') {
        $planId = filter_var($_POST['plan_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
        $res = activateUserMembership($pdo, $userId, $planId);
        setFlash($res['ok'] ? 'success' : 'error', $res['message']);
        header('Location: bookings.php?class_id=' . urlencode((string)$selectedClassId) . '&date=' . urlencode($selectedDate));
        exit;
    }

    if ($formAction === 'book_class') {
        $postClassId = filter_var($_POST['class_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
        $postDate    = trim($_POST['booking_date'] ?? $selectedDate);
        $postNotes   = (string)($_POST['notes'] ?? '');
        $res = createClassBooking($pdo, $userId, $postClassId, $postDate, $postNotes);
        setFlash($res['ok'] ? 'success' : 'error', $res['message']);
        header('Location: bookings.php?class_id=' . urlencode((string)$postClassId) . '&date=' . urlencode($postDate));
        exit;
    }

    if ($formAction === 'cancel_booking') {
        $bookingId = filter_var($_POST['booking_id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
        $res = updateBookingStatus($pdo, $bookingId, 'cancelled', $currentUser);
        setFlash($res['ok'] ? 'success' : 'error', $res['message']);
        header('Location: bookings.php');
        exit;
    }
}

// Fetch plans & fitness classes
try {
    $plans = $pdo->query("SELECT * FROM membership_plans ORDER BY price ASC")->fetchAll();
    $classes = $pdo->query("
        SELECT fc.id, fc.title, fc.trainer_name, fc.schedule_info, fc.price, fc.max_capacity, c.name AS category_name
        FROM fitness_classes fc
        INNER JOIN categories c ON fc.category_id = c.id
        ORDER BY fc.id ASC
    ")->fetchAll();
} catch (PDOException $e) {
    error_log('Bookings page load error: ' . $e->getMessage());
    $plans = [];
    $classes = [];
}

if ($selectedClassId <= 0 && !empty($classes)) {
    // Default to Class 3 if present (or Class 1)
    $selectedClassId = (int)$classes[0]['id'];
}

$activeMembership = $isAuth ? getUserActiveMembership($pdo, (int)$currentUser['id']) : null;
$myBookings = [];
$staffBookings = [];

if ($isAuth) {
    try {
        $myStmt = $pdo->prepare("
            SELECT cb.id, cb.booking_date, cb.status, cb.notes, cb.created_at,
                   fc.id AS class_id, fc.title AS class_title, fc.trainer_name, fc.schedule_info
            FROM class_bookings cb
            INNER JOIN fitness_classes fc ON cb.class_id = fc.id
            WHERE cb.user_id = :user_id
            ORDER BY cb.booking_date DESC, cb.id DESC
            LIMIT 25
        ");
        $myStmt->execute(['user_id' => (int)$currentUser['id']]);
        $myBookings = $myStmt->fetchAll();

        if ($isStaff) {
            $staffStmt = $pdo->query("
                SELECT cb.id, cb.booking_date, cb.status, cb.notes, cb.created_at,
                       u.full_name AS member_name, u.email AS member_email,
                       fc.id AS class_id, fc.title AS class_title, fc.max_capacity
                FROM class_bookings cb
                INNER JOIN users u ON cb.user_id = u.id
                INNER JOIN fitness_classes fc ON cb.class_id = fc.id
                ORDER BY cb.booking_date DESC, cb.id DESC
                LIMIT 30
            ");
            $staffBookings = $staffStmt->fetchAll();
        }
    } catch (PDOException $e) {
        error_log('Bookings list error: ' . $e->getMessage());
    }
}

$seo = [
    'title'       => 'Membership Plans & Class Booking - TIGER FITNESS',
    'description' => 'Activate your Tiger Fitness membership tier, check live class capacity via AJAX, and reserve your upcoming strength or conditioning training session.',
    'keywords'    => 'Tiger Fitness membership, gym class booking, class schedule capacity, strength class reservation',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php require __DIR__ . '/../components/seo_meta.php'; ?>
  <link rel="icon" type="image/svg+xml" href="../public/images/favicon.svg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../public/css/style.css">
</head>
<body class="<?php echo ($activeTheme === 'dark') ? 'theme-dark' : ''; ?>" data-bs-theme="<?php echo e($activeTheme); ?>">

<?php require_once __DIR__ . '/../components/header.php'; ?>

<main class="container py-5" id="bookings-page-marker">
  <!-- Header -->
  <div class="row align-items-center mb-4 g-3">
    <div class="col-md-8">
      <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
        <span class="hero-badge">Topic T1 Workflow</span>
        <span class="badge bg-success-subtle text-success border border-success">Membership &rarr; Class &rarr; Schedule &rarr; Booking</span>
      </div>
      <h1 class="display-6 fw-bold font-headline text-uppercase mb-1">Membership &amp; Session Booking</h1>
      <p class="text-secondary mb-0">Verify your active membership tier, inspect live class capacity via AJAX, and reserve your spot.</p>
    </div>
    <div class="col-md-4 text-md-end">
      <a href="classes.php" class="btn btn-outline-apex fw-semibold">&larr; Browse Class Catalog</a>
    </div>
  </div>

  <!-- Alerts -->
  <?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm py-3 mb-4 rounded-3 border-0 bg-success bg-opacity-10 text-success border-start border-4 border-success" role="alert">
      <strong>Success!</strong> <?php echo e($flashSuccess); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm py-3 mb-4 rounded-3 border-0 bg-danger bg-opacity-10 text-danger border-start border-4 border-danger" role="alert">
      <strong>Action Blocked:</strong> <?php echo e($flashError); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div id="bookings-ajax-toast"></div>

  <?php if (!$isAuth): ?>
    <div class="glass-panel p-4 mb-4 border-start border-4 border-warning d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div>
        <h2 class="h5 fw-bold font-headline text-uppercase text-white mb-1">Log In Required to Book Sessions</h2>
        <p class="text-secondary small mb-0">You can browse our Membership Plans and check live class spot counts below. Log in or register to confirm a reservation.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="../auth/login.php" class="btn btn-apex-crimson fw-semibold">Log In</a>
        <a href="../auth/register.php" class="btn btn-outline-apex">Register Account</a>
      </div>
    </div>
  <?php endif; ?>

  <!-- STEP 1: MEMBERSHIP TIERS -->
  <section aria-labelledby="membership-step-heading" class="mb-5">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <h2 id="membership-step-heading" class="h4 fw-bold font-headline text-uppercase mb-0">
          Step 1: Membership Tier Verification
        </h2>
        <p class="text-secondary small mb-0">An active membership determines your concurrent class booking limit.</p>
      </div>
      <?php if ($activeMembership): ?>
        <div class="glass-panel px-3 py-2 border-start border-4 border-success">
          <span class="small text-secondary">Active Plan:</span>
          <strong class="text-success ms-1"><?php echo e($activeMembership['plan_name']); ?></strong>
          <span class="badge bg-success-subtle text-success border border-success ms-2">
            Quota: <?php echo (int)$activeMembership['active_bookings_count']; ?> / <?php echo (int)$activeMembership['max_active_bookings']; ?> active
          </span>
          <span class="small text-secondary ms-2">Expires: <?php echo e($activeMembership['end_date']); ?></span>
        </div>
      <?php elseif ($isAuth): ?>
        <span class="badge bg-warning text-dark px-3 py-2">No Active Membership — Choose a tier below</span>
      <?php endif; ?>
    </div>

    <div class="row g-3">
      <?php foreach ($plans as $plan): ?>
        <?php $isCurrentPlan = ($activeMembership && (int)$activeMembership['plan_id'] === (int)$plan['id']); ?>
        <div class="col-12 col-md-4">
          <div class="bento-card d-flex flex-column justify-content-between <?php echo $isCurrentPlan ? 'border-success border-2' : ''; ?>">
            <div>
              <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="hero-badge"><?php echo e($plan['tier_code']); ?></span>
                <?php if ($isCurrentPlan): ?>
                  <span class="badge bg-success">Current Active Plan</span>
                <?php endif; ?>
              </div>
              <h3 class="h5 fw-bold font-headline text-white mb-1"><?php echo e($plan['name']); ?></h3>
              <div class="mb-2">
                <span class="class-price-tag">$<?php echo e(number_format((float)$plan['price'], 2)); ?></span>
                <span class="small text-secondary">/ <?php echo (int)$plan['duration_days']; ?> days</span>
              </div>
              <p class="small text-secondary mb-3"><?php echo e($plan['description']); ?></p>
              <div class="small fw-semibold text-white mb-3">
                Max Concurrent Bookings: <span class="text-tiger"><?php echo (int)$plan['max_active_bookings']; ?> classes</span>
              </div>
            </div>

            <?php if ($isAuth): ?>
              <form action="bookings.php" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="form_action" value="activate_membership">
                <input type="hidden" name="plan_id" value="<?php echo (int)$plan['id']; ?>">
                <input type="hidden" name="class_id" value="<?php echo (int)$selectedClassId; ?>">
                <input type="hidden" name="date" value="<?php echo e($selectedDate); ?>">
                <button type="submit" class="btn btn-sm w-100 fw-semibold <?php echo $isCurrentPlan ? 'btn-outline-apex' : 'btn-apex-crimson'; ?>" <?php echo $isCurrentPlan ? 'disabled' : ''; ?>>
                  <?php echo $isCurrentPlan ? 'Active Membership' : 'Activate / Switch to ' . e($plan['tier_code']); ?>
                </button>
              </form>
            <?php else: ?>
              <a href="../auth/login.php" class="btn btn-sm btn-outline-apex w-100">Log In to Subscribe</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- STEPS 2, 3, 4: CLASS, SCHEDULE & BOOKING -->
  <section aria-labelledby="booking-step-heading" class="mb-5">
    <div class="row g-4">
      <div class="col-12 col-lg-7">
        <div class="glass-panel p-4 h-100" id="live-capacity-widget">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <div>
              <h2 id="booking-step-heading" class="h4 fw-bold font-headline text-uppercase mb-0">
                Steps 2–4: Class, Schedule &amp; Capacity Booking
              </h2>
              <p class="text-secondary small mb-0">Live AJAX verification prevents overbooking beyond class capacity.</p>
            </div>
            <button type="button" id="refresh-capacity-btn" class="btn btn-sm btn-outline-apex">
              &#8635; Refresh Spots
            </button>
          </div>

          <form action="bookings.php" method="POST" id="booking-workflow-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="form_action" value="book_class">

            <div class="row g-3 mb-3">
              <div class="col-md-7">
                <label for="capacity_class_id" class="form-label fw-semibold small text-secondary">Step 2: Select Fitness Class</label>
                <select class="form-select" id="capacity_class_id" name="class_id" required>
                  <?php foreach ($classes as $cls): ?>
                    <option value="<?php echo (int)$cls['id']; ?>" <?php echo ($selectedClassId === (int)$cls['id']) ? 'selected' : ''; ?>>
                      <?php echo e($cls['title']); ?> (Max: <?php echo (int)$cls['max_capacity']; ?> &bull; <?php echo e($cls['schedule_info']); ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-5">
                <label for="capacity_date" class="form-label fw-semibold small text-secondary">Step 3: Training Date</label>
                <input
                  type="date"
                  class="form-control"
                  id="capacity_date"
                  name="booking_date"
                  min="<?php echo e(date('Y-m-d')); ?>"
                  max="<?php echo e(date('Y-m-d', strtotime('+60 days'))); ?>"
                  value="<?php echo e($selectedDate); ?>"
                  required>
              </div>
            </div>

            <!-- Interactive 4-day Quick Availability Pills -->
            <div class="mb-3">
              <span class="small fw-semibold text-secondary d-block mb-2">Quick Schedule Forecast (Click a day to inspect via AJAX):</span>
              <div class="d-flex flex-wrap gap-2" id="capacity-date-pills"></div>
            </div>

            <!-- Live AJAX Capacity Meter -->
            <div class="glass-panel p-3 mb-3" id="live-capacity-output">
              <div class="small text-secondary">Loading live capacity...</div>
            </div>

            <div class="mb-3">
              <label for="ajax_booking_notes" class="form-label fw-semibold small text-secondary">Training Focus / Notes (Optional)</label>
              <input
                type="text"
                class="form-control"
                id="ajax_booking_notes"
                name="notes"
                maxlength="255"
                placeholder="e.g. Working on squat mobility or beginner introduction">
            </div>

            <div id="ajax-booking-feedback" class="mb-3"></div>

            <?php if ($isAuth): ?>
              <div class="d-flex flex-wrap gap-2">
                <button type="button" id="ajax-quick-book-btn" class="btn btn-apex-crimson btn-lg fw-semibold flex-grow-1">
                  Confirm Booking via AJAX
                </button>
                <button type="submit" class="btn btn-outline-apex" title="Standard form POST fallback">
                  Submit (Standard POST)
                </button>
              </div>
            <?php else: ?>
              <a href="../auth/login.php" class="btn btn-apex-crimson btn-lg w-100 fw-semibold">Log In to Confirm Booking</a>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <!-- Right Column: Business Rules Explanation & Gym Location Link -->
      <div class="col-12 col-lg-5">
        <div class="bento-card d-flex flex-column justify-content-between">
          <div>
            <h3 class="h5 fw-bold font-headline text-uppercase mb-3">Server-Side Business Rules Enforced</h3>
            <ul class="list-group list-group-flush small mb-4">
              <li class="list-group-item px-0">
                <strong class="text-tiger">1. Active Membership Gate:</strong>
                <span class="text-secondary d-block">Only authenticated athletes with an active, unexpired membership plan can book classes.</span>
              </li>
              <li class="list-group-item px-0">
                <strong class="text-tiger">2. Plan Concurrency Quota:</strong>
                <span class="text-secondary d-block">Starter allows 2 active bookings, Tiger Pro allows 5, and Apex VIP allows 15 concurrent bookings.</span>
              </li>
              <li class="list-group-item px-0">
                <strong class="text-tiger">3. Strict Class Capacity Lock:</strong>
                <span class="text-secondary d-block">Uses MySQL row-level locking (<code>FOR UPDATE</code>) inside a transaction to prevent exceeding <code>max_capacity</code>. Try <em>Olympic Weightlifting</em> tomorrow (seeded at 4/5 spots) to test reaching capacity!</span>
              </li>
              <li class="list-group-item px-0">
                <strong class="text-tiger">4. Duplicate Prevention:</strong>
                <span class="text-secondary d-block">Prevents the same member from booking the same class twice on the same date.</span>
              </li>
            </ul>
          </div>

          <div class="glass-panel p-3">
            <h4 class="h6 fw-bold font-headline text-uppercase mb-1">Training Facility Address</h4>
            <p class="small text-secondary mb-2">268 Ly Thuong Kiet, District 10, Ho Chi Minh City (05:00 AM – 11:00 PM)</p>
            <a href="../public/index.php#location" class="btn btn-sm btn-outline-apex w-100">View Interactive Google Map &rarr;</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- MY BOOKINGS HISTORY -->
  <?php if ($isAuth): ?>
    <section aria-labelledby="my-bookings-heading" class="mb-5">
      <div class="glass-panel p-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <h2 id="my-bookings-heading" class="h4 fw-bold font-headline text-uppercase mb-0">My Scheduled Bookings</h2>
          <span class="badge bg-secondary-subtle text-white border"><?php echo count($myBookings); ?> records</span>
        </div>

        <?php if (empty($myBookings)): ?>
          <p class="text-secondary mb-0">You have not booked any fitness classes yet. Use the workflow above to reserve a spot.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle small mb-0">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Fitness Class</th>
                  <th>Coach &amp; Schedule</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Notes</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($myBookings as $bk): ?>
                  <?php
                    $badgeCls = 'bg-success-subtle text-success border-success';
                    if ($bk['status'] === 'cancelled') $badgeCls = 'bg-danger-subtle text-danger border-danger';
                    elseif ($bk['status'] === 'completed') $badgeCls = 'bg-secondary-subtle text-secondary border-secondary';
                  ?>
                  <tr>
                    <td class="fw-bold">#<?php echo (int)$bk['id']; ?></td>
                    <td>
                      <a href="item_details.php?id=<?php echo (int)$bk['class_id']; ?>" class="fw-semibold text-decoration-none text-tiger">
                        <?php echo e($bk['class_title']); ?>
                      </a>
                    </td>
                    <td class="text-secondary">
                      <div><?php echo e($bk['trainer_name']); ?></div>
                      <div class="opacity-75"><?php echo e($bk['schedule_info']); ?></div>
                    </td>
                    <td class="fw-semibold text-white"><?php echo e($bk['booking_date']); ?></td>
                    <td>
                      <span class="badge border text-uppercase <?php echo $badgeCls; ?>"><?php echo e($bk['status']); ?></span>
                    </td>
                    <td class="text-secondary"><?php echo e($bk['notes'] ?: '—'); ?></td>
                    <td class="text-end">
                      <?php if ($bk['status'] === 'confirmed'): ?>
                        <button type="button" class="btn btn-sm btn-outline-danger ajax-cancel-booking-btn" data-booking-id="<?php echo (int)$bk['id']; ?>">
                          Cancel
                        </button>
                      <?php else: ?>
                        <span class="text-secondary">—</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- STAFF MANAGEMENT CONSOLE (RBAC: Admin & Trainer) -->
  <?php if ($isStaff): ?>
    <section aria-labelledby="staff-bookings-heading">
      <div class="glass-panel p-4 border-start border-4 border-warning">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <div>
            <span class="vip-role-badge vip-role-admin mb-1">Staff Management (RBAC)</span>
            <h2 id="staff-bookings-heading" class="h4 fw-bold font-headline text-uppercase mb-0">All Member Reservations</h2>
          </div>
          <span class="small text-secondary">Changes save live via AJAX</span>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle small mb-0">
            <thead>
              <tr>
                <th>Booking</th>
                <th>Athlete</th>
                <th>Class</th>
                <th>Date</th>
                <th>Current Status</th>
                <th class="text-end">Update Status (AJAX)</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($staffBookings as $sb): ?>
                <tr>
                  <td class="fw-bold">#<?php echo (int)$sb['id']; ?></td>
                  <td>
                    <div class="fw-semibold text-white"><?php echo e($sb['member_name']); ?></div>
                    <div class="text-secondary"><?php echo e($sb['member_email']); ?></div>
                  </td>
                  <td><?php echo e($sb['class_title']); ?></td>
                  <td><?php echo e($sb['booking_date']); ?></td>
                  <td><span class="badge bg-secondary text-uppercase"><?php echo e($sb['status']); ?></span></td>
                  <td class="text-end" style="min-width: 150px;">
                    <select class="form-select form-select-sm ajax-staff-status-select" data-booking-id="<?php echo (int)$sb['id']; ?>" aria-label="Status for booking <?php echo (int)$sb['id']; ?>">
                      <option value="confirmed" <?php echo $sb['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                      <option value="completed" <?php echo $sb['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                      <option value="cancelled" <?php echo $sb['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../public/js/script.js"></script>
</body>
</html>
