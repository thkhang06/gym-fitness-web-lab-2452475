<?php
/**
 * Protected Member & Coach Dashboard (Labs 2 & 3)
 * Bento Grid Telemetry Layout + Membership & Bookings Summary + Cookie & Session Inspector
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/preferences.php';
require_once __DIR__ . '/../config/booking_service.php';

// Security: Prevent browser caching of protected content
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

requireLogin("Access Denied: You must be logged in to view the Member Dashboard.");

$user = getCurrentUser();
$sessionId = session_id();
$isStaff = hasRole(['admin', 'trainer']);
$activeTheme = getActiveTheme();

$flashSuccess = getFlash('success');
$flashError   = getFlash('error');

$cookiesInspector = getCookiesInspectorData();
$recentClass      = getRecentlyViewedClass();
$favCategory      = getFavoriteCategory();
$activeMembership = getUserActiveMembership($pdo, (int)$user['id']);

try {
    $stmtClasses = $pdo->query("
        SELECT fc.id, fc.title, fc.trainer_name, fc.schedule_info, fc.price, fc.max_capacity, c.name AS category_name
        FROM fitness_classes fc
        INNER JOIN categories c ON fc.category_id = c.id
        ORDER BY fc.id DESC
    ");
    $classes = $stmtClasses->fetchAll();

    $stmtStats = $pdo->query("
        SELECT
            (SELECT COUNT(*) FROM fitness_classes) AS total_classes,
            (SELECT COUNT(*) FROM categories) AS total_categories,
            (SELECT COUNT(*) FROM users) AS total_users,
            (SELECT COUNT(*) FROM class_bookings WHERE status = 'confirmed') AS total_bookings
    ");
    $stats = $stmtStats->fetch();

    $myUpcomingStmt = $pdo->prepare("
        SELECT cb.id, cb.booking_date, cb.status, fc.id AS class_id, fc.title AS class_title, fc.schedule_info
        FROM class_bookings cb
        INNER JOIN fitness_classes fc ON cb.class_id = fc.id
        WHERE cb.user_id = :uid
        ORDER BY cb.booking_date DESC, cb.id DESC
        LIMIT 4
    ");
    $myUpcomingStmt->execute(['uid' => (int)$user['id']]);
    $myRecentBookings = $myUpcomingStmt->fetchAll();
} catch (PDOException $e) {
    error_log("Dashboard query error: " . $e->getMessage());
    $classes = [];
    $myRecentBookings = [];
    $stats = ['total_classes' => 0, 'total_categories' => 0, 'total_users' => 0, 'total_bookings' => 0];
}

$roleClass = 'vip-role-member';
if ($user['role'] === 'admin') {
    $roleClass = 'vip-role-admin';
} elseif ($user['role'] === 'trainer') {
    $roleClass = 'vip-role-trainer';
}

$seo = [
    'title'       => 'Athlete Dashboard - TIGER FITNESS',
    'description' => 'Authenticated member dashboard displaying active membership tier, booked sessions, role-based privileges, and session/cookie telemetry.',
    'robots'      => 'noindex, nofollow',
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

<main class="container py-5">
  <?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm py-3 mb-4 rounded-3 border-0 bg-success bg-opacity-10 text-success border-start border-4 border-success" role="alert">
      <strong>Success!</strong> <?php echo e($flashSuccess); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm py-3 mb-4 rounded-3 border-0 bg-danger bg-opacity-10 text-danger border-start border-4 border-danger" role="alert">
      <strong>Notice:</strong> <?php echo e($flashError); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Welcome Header -->
  <div class="glass-panel p-4 mb-4">
    <div class="row align-items-center g-3">
      <div class="col-md-7">
        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
          <span class="vip-role-badge <?php echo e($roleClass); ?> px-3 py-1 text-uppercase fs-6">
            <?php echo e(strtoupper($user['role'])); ?> ACCOUNT
          </span>
          <span class="badge bg-success-subtle text-success border border-success px-2 py-1 small d-inline-flex align-items-center gap-1">
            <span class="live-beacon"></span> Active Session Verified
          </span>
        </div>
        <h1 class="display-6 fw-bold font-headline mb-1 text-uppercase">Welcome, <?php echo e($user['full_name']); ?>!</h1>
        <p class="text-secondary mb-0">Here is your training hub, active membership tier, booked sessions, and security controls.</p>
      </div>
      <div class="col-md-5 text-md-end d-flex justify-content-md-end gap-2 flex-wrap">
        <a href="bookings.php" class="btn btn-apex-crimson fw-semibold shadow-sm">Book Session</a>
        <?php if ($isStaff): ?>
          <a href="class_form.php" class="btn btn-outline-apex fw-semibold">+ Create Class</a>
        <?php endif; ?>
        <a href="classes.php" class="btn btn-outline-apex">Browse Catalog</a>
        <a href="../auth/logout.php" class="btn btn-outline-apex text-danger border-danger">Log Out</a>
      </div>
    </div>
  </div>

  <!-- Bento Grid Telemetry Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="bento-card">
        <span class="text-secondary small text-uppercase fw-bold font-headline">Available Programs</span>
        <div class="d-flex align-items-baseline justify-content-between mt-2">
          <span class="display-5 fw-bold text-tiger font-headline"><?php echo (int)$stats['total_classes']; ?></span>
          <span class="badge bg-danger-subtle text-danger border border-danger">Scheduled</span>
        </div>
        <div class="small text-secondary mt-2">Active training classes open for booking.</div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="bento-card">
        <span class="text-secondary small text-uppercase fw-bold font-headline">Confirmed Bookings</span>
        <div class="d-flex align-items-baseline justify-content-between mt-2">
          <span class="display-5 fw-bold text-warning font-headline"><?php echo (int)$stats['total_bookings']; ?></span>
          <span class="badge bg-warning-subtle text-warning border border-warning">Club-Wide</span>
        </div>
        <div class="small text-secondary mt-2">Active confirmed session reservations.</div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="bento-card">
        <span class="text-secondary small text-uppercase fw-bold font-headline">My Membership</span>
        <div class="d-flex align-items-baseline justify-content-between mt-2">
          <span class="h4 fw-bold text-success font-headline mb-0"><?php echo $activeMembership ? e($activeMembership['tier_code']) : 'NONE'; ?></span>
          <span class="badge bg-success-subtle text-success border border-success">
            <?php echo $activeMembership ? ((int)$activeMembership['active_bookings_count'] . '/' . (int)$activeMembership['max_active_bookings'] . ' quota') : 'Inactive'; ?>
          </span>
        </div>
        <div class="small text-secondary mt-2">
          <a href="bookings.php" class="text-decoration-none text-tiger fw-semibold">Manage membership &rarr;</a>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
      <div class="bento-card">
        <span class="text-secondary small text-uppercase fw-bold font-headline">Registered Athletes</span>
        <div class="d-flex align-items-baseline justify-content-between mt-2">
          <span class="display-5 fw-bold text-info font-headline"><?php echo (int)$stats['total_users']; ?></span>
          <span class="badge bg-info-subtle text-info border border-info">Members</span>
        </div>
        <div class="small text-secondary mt-2">Verified athletes across <?php echo (int)$stats['total_categories']; ?> disciplines.</div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <!-- Left Column: Authenticated Profile & RBAC Info -->
    <div class="col-12 col-lg-5">
      <div class="bento-card">
        <div class="bento-card-header">
          <h2 class="h5 fw-bold font-headline text-uppercase mb-0">Authenticated Profile</h2>
          <span class="live-beacon"></span>
        </div>
        <div class="p-1">
          <ul class="list-group list-group-flush mb-3">
            <li class="list-group-item px-0 d-flex justify-content-between">
              <span class="text-secondary">Account ID:</span>
              <span class="fw-semibold text-white">#<?php echo e((string)$user['id']); ?></span>
            </li>
            <li class="list-group-item px-0 d-flex justify-content-between">
              <span class="text-secondary">Full Name:</span>
              <span class="fw-semibold text-white"><?php echo e($user['full_name']); ?></span>
            </li>
            <li class="list-group-item px-0 d-flex justify-content-between">
              <span class="text-secondary">Email:</span>
              <span class="fw-semibold text-white"><?php echo e($user['email']); ?></span>
            </li>
            <li class="list-group-item px-0 d-flex justify-content-between">
              <span class="text-secondary">Assigned Role:</span>
              <span class="vip-role-badge <?php echo e($roleClass); ?> text-uppercase"><?php echo e($user['role']); ?></span>
            </li>
          </ul>

          <?php if ($isStaff): ?>
            <div class="alert alert-success border-0 bg-success bg-opacity-10 p-3 rounded-3 mb-3 border-start border-4 border-success">
              <h3 class="h6 fw-bold text-success font-headline text-uppercase mb-1">Staff Clearance Active (RBAC)</h3>
              <p class="small text-secondary mb-0">
                You have administrative authority to <strong>Create, Update, Delete</strong> classes and manage member bookings.
              </p>
            </div>
          <?php else: ?>
            <div class="glass-panel p-3 rounded-3 mb-3 border-start border-4 border-secondary">
              <h3 class="h6 fw-bold text-white font-headline text-uppercase mb-1">Member Privilege Notice (RBAC)</h3>
              <p class="small text-secondary mb-2">
                Your role is <strong>Member</strong>. You can activate memberships, check capacity, and book/cancel sessions, while administrative CRUD remains blocked (HTTP 403).
              </p>
              <a href="class_form.php" class="btn btn-outline-apex btn-sm w-100 fw-semibold">
                &rarr; Test RBAC Security (Attempt class_form.php)
              </a>
            </div>
          <?php endif; ?>

          <!-- Technical Evidence box -->
          <div class="glass-panel p-3 rounded-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <strong class="small text-secondary font-headline text-uppercase">Session ID &amp; Security:</strong>
              <span class="badge bg-success small">HttpOnly + CSRF</span>
            </div>
            <code class="small text-break d-block mb-1 text-warning"><?php echo e($sessionId); ?></code>
            <small class="text-secondary d-block">
              Strict session mode active with SameSite=Lax cookies and per-session CSRF tokens.
            </small>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: My Recent Bookings & Programs -->
    <div class="col-12 col-lg-7 d-flex flex-column gap-4">
      <div class="bento-card h-auto">
        <div class="bento-card-header">
          <h2 class="h5 fw-bold font-headline text-uppercase mb-0">My Recent Bookings</h2>
          <a href="bookings.php" class="small text-tiger fw-semibold text-decoration-none">Open Booking Console &rarr;</a>
        </div>
        <?php if (empty($myRecentBookings)): ?>
          <p class="small text-secondary mb-0">No reservations yet. <a href="bookings.php" class="text-tiger">Book your first training session</a>.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm align-middle small mb-0">
              <thead>
                <tr>
                  <th>Class</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th class="text-end">Details</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($myRecentBookings as $mb): ?>
                  <tr>
                    <td class="fw-semibold text-white"><?php echo e($mb['class_title']); ?></td>
                    <td class="text-secondary"><?php echo e($mb['booking_date']); ?></td>
                    <td><span class="badge bg-secondary-subtle text-white border text-uppercase"><?php echo e($mb['status']); ?></span></td>
                    <td class="text-end">
                      <a href="item_details.php?id=<?php echo (int)$mb['class_id']; ?>" class="btn btn-sm btn-outline-apex py-0 px-2">View</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <div class="bento-card">
        <div class="bento-card-header">
          <h2 class="h5 fw-bold font-headline text-uppercase mb-0">Training Programs Overview</h2>
          <div class="d-flex gap-2">
            <?php if ($isStaff): ?>
              <a href="class_form.php" class="btn btn-sm btn-apex-crimson fw-semibold">+ New Class</a>
            <?php endif; ?>
            <a href="classes.php" class="small text-tiger fw-semibold text-decoration-none align-self-center">All Classes &rarr;</a>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0 small">
            <thead>
              <tr>
                <th>Class Title</th>
                <th>Discipline</th>
                <th>Capacity</th>
                <th>Price</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (array_slice($classes, 0, 5) as $cls): ?>
                <tr>
                  <td class="fw-semibold text-white"><?php echo e($cls['title']); ?></td>
                  <td>
                    <span class="badge bg-secondary-subtle text-white border small">
                      <?php echo e($cls['category_name']); ?>
                    </span>
                  </td>
                  <td class="text-secondary"><?php echo (int)$cls['max_capacity']; ?> spots</td>
                  <td class="fw-bold text-tiger font-headline">$<?php echo e(number_format((float)$cls['price'], 2)); ?></td>
                  <td class="text-end">
                    <a href="item_details.php?id=<?php echo urlencode((string)$cls['id']); ?>" class="btn btn-sm btn-outline-apex px-2 py-0.5">
                      View
                    </a>
                    <?php if ($isStaff): ?>
                      <a href="class_form.php?id=<?php echo urlencode((string)$cls['id']); ?>" class="btn btn-sm btn-outline-apex px-2 py-0.5 text-warning border-warning">
                        Edit
                      </a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Client Cookies & Session Inspector -->
  <div class="glass-panel overflow-hidden mt-4">
    <div class="p-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom border-secondary border-opacity-25" style="background: rgba(9, 13, 22, 0.25);">
      <div class="d-flex align-items-center gap-2">
        <span class="hero-badge">Session &amp; Cookie Deliverable</span>
        <strong class="fs-6 font-headline text-uppercase text-white">Client-Side Cookies &amp; Session Inspector</strong>
      </div>
      <a href="clear_cookies.php?return=dashboard.php" class="btn btn-sm btn-outline-apex text-danger border-danger fw-semibold" onclick="return confirm('Reset all tracking cookies and preferences?');">
        Reset Preferences (Clear Cookies)
      </a>
    </div>

    <div class="p-4">
      <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
          <div class="p-3 glass-panel h-100">
            <strong class="d-block small text-secondary font-headline text-uppercase">Active UI Theme (Cookie 1)</strong>
            <div class="d-flex align-items-center justify-content-between mt-2">
              <span class="fw-bold text-white text-uppercase font-headline"><?php echo e($activeTheme); ?> Mode</span>
              <a href="set_theme.php?theme=<?php echo ($activeTheme === 'dark') ? 'light' : 'dark'; ?>&amp;return=dashboard.php" class="btn btn-sm btn-outline-apex">
                Toggle Theme
              </a>
            </div>
            <small class="text-secondary d-block mt-2">Cookie <code>apex_theme</code> (30-day lifespan).</small>
          </div>
        </div>

        <div class="col-12 col-md-4">
          <div class="p-3 glass-panel h-100">
            <strong class="d-block small text-secondary font-headline text-uppercase">Recently Viewed Class (Cookie 2)</strong>
            <?php if ($recentClass): ?>
              <div class="d-flex align-items-center justify-content-between mt-2">
                <span class="fw-bold text-tiger text-truncate me-2"><?php echo e($recentClass['title']); ?></span>
                <a href="item_details.php?id=<?php echo urlencode((string)$recentClass['id']); ?>" class="btn btn-sm btn-outline-apex">
                  Revisit
                </a>
              </div>
              <small class="text-secondary d-block mt-2">Cookie <code>apex_recent_class</code> (7-day lifespan).</small>
            <?php else: ?>
              <span class="text-secondary small d-block mt-2">No class visited recently yet.</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="col-12 col-md-4">
          <div class="p-3 glass-panel h-100">
            <strong class="d-block small text-secondary font-headline text-uppercase">Favorite Discipline (Cookie 3)</strong>
            <?php if ($favCategory): ?>
              <div class="d-flex align-items-center justify-content-between mt-2">
                <span class="fw-bold text-white"><?php echo e($favCategory['name']); ?></span>
                <a href="classes.php?category_id=<?php echo urlencode((string)$favCategory['id']); ?>" class="btn btn-sm btn-outline-apex">
                  Filter
                </a>
              </div>
              <small class="text-secondary d-block mt-2">Cookie <code>apex_fav_category</code> (7-day lifespan).</small>
            <?php else: ?>
              <span class="text-secondary small d-block mt-2">No category filtered yet.</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <h3 class="h6 fw-bold font-headline text-uppercase text-white mb-2">Technical Cookies &amp; Session Inspector Table</h3>
      <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle small mb-0">
          <thead>
            <tr>
              <th>Cookie Name</th>
              <th>Current Stored Value</th>
              <th>System Purpose</th>
              <th>Lifespan &amp; Expiration</th>
              <th>Security Level</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($cookiesInspector as $c): ?>
              <tr>
                <td class="fw-semibold text-tiger"><code><?php echo e($c['name']); ?></code></td>
                <td><code class="text-white"><?php echo e($c['value']); ?></code></td>
                <td class="text-secondary"><?php echo e($c['purpose']); ?></td>
                <td><span class="badge bg-secondary-subtle text-white border"><?php echo e($c['lifespan']); ?></span></td>
                <td><span class="badge bg-success-subtle text-success border border-success"><?php echo e($c['security']); ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
