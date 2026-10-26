<?php
/**
 * Protected Member & Coach Dashboard (Lab 2 Tasks 4.4, 5.1, 5.2 & 6)
 * Bento Grid Telemetry Layout Synthesized via Google Stitch MCP
 * Live Session & Client Cookie Inspector with RBAC Management
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/preferences.php';

// Security: Prevent browser caching of protected content
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Enforce authentication gate
requireLogin("Access Denied: You must be logged in to view the Member Dashboard.");

// Retrieve current authenticated user details from session
$user = getCurrentUser();
$sessionId = session_id();
$isStaff = hasRole(['admin', 'trainer']);
$activeTheme = getActiveTheme();

// Flash messaging
$flashSuccess = getFlash('success');
$flashError   = getFlash('error');

// Cookie inspector data for Task 5.2 evaluation
$cookiesInspector = getCookiesInspectorData();
$recentClass      = getRecentlyViewedClass();
$favCategory      = getFavoriteCategory();

// Fetch summary metrics & training classes from database for display
try {
    $stmtClasses = $pdo->query("
        SELECT fc.id, fc.title, fc.trainer_name, fc.schedule_info, fc.price, c.name AS category_name
        FROM fitness_classes fc
        INNER JOIN categories c ON fc.category_id = c.id
        ORDER BY fc.id DESC
    ");
    $classes = $stmtClasses->fetchAll();

    $stmtStats = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM fitness_classes) AS total_classes,
            (SELECT COUNT(*) FROM categories) AS total_categories,
            (SELECT COUNT(*) FROM users) AS total_users
    ");
    $stats = $stmtStats->fetch();
} catch (PDOException $e) {
    error_log("Dashboard query error: " . $e->getMessage());
    $classes = [];
    $stats = ['total_classes' => 0, 'total_categories' => 0, 'total_users' => 0];
}

// Role badge styling helper using Stitch VIP tokens
$roleClass = 'vip-role-member';
if ($user['role'] === 'admin') {
    $roleClass = 'vip-role-admin';
} elseif ($user['role'] === 'trainer') {
    $roleClass = 'vip-role-trainer';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Athlete Dashboard - TIGER FITNESS</title>
  <link rel="icon" type="image/svg+xml" href="../public/images/favicon.svg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../public/css/style.css">
</head>
<body class="<?php echo ($activeTheme === 'dark') ? 'theme-dark' : ''; ?>" data-bs-theme="<?php echo $activeTheme; ?>">

<?php require_once __DIR__ . '/../components/header.php'; ?>

<main class="container py-5">
  <!-- Flash Alerts -->
  <?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm py-3 mb-4 rounded-3 border-0 bg-success bg-opacity-10 text-success border-start border-4 border-success" role="alert">
      <strong>Success!</strong> <?php echo htmlspecialchars($flashSuccess); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm py-3 mb-4 rounded-3 border-0 bg-danger bg-opacity-10 text-danger border-start border-4 border-danger" role="alert">
      <strong>Notice:</strong> <?php echo htmlspecialchars($flashError); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Welcome Header (Stitch Bento Title Bar) -->
  <div class="glass-panel p-4 mb-4">
    <div class="row align-items-center g-3">
      <div class="col-md-7">
        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
          <span class="vip-role-badge <?php echo $roleClass; ?> px-3 py-1 text-uppercase fs-6">
            <?php echo htmlspecialchars(strtoupper($user['role'])); ?> ACCOUNT
          </span>
          <span class="badge bg-success-subtle text-success border border-success px-2 py-1 small d-inline-flex align-items-center gap-1">
            <span class="live-beacon"></span> Active Session Verified
          </span>
        </div>
        <h1 class="display-6 fw-bold font-headline mb-1 text-uppercase">Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
        <p class="text-secondary mb-0">Here is your training hub, personalized membership overview, and system controls.</p>
      </div>
      <div class="col-md-5 text-md-end d-flex justify-content-md-end gap-2 flex-wrap">
        <?php if ($isStaff): ?>
          <a href="class_form.php" class="btn btn-apex-crimson fw-semibold shadow-sm">+ Create Class</a>
        <?php endif; ?>
        <a href="classes.php" class="btn btn-outline-apex">Browse Catalog</a>
        <a href="../auth/logout.php" class="btn btn-outline-apex text-danger border-danger">Log Out</a>
      </div>
    </div>
  </div>

  <!-- Bento Grid Metric Telemetry Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
      <div class="bento-card">
        <span class="text-secondary small text-uppercase fw-bold font-headline">Available Programs</span>
        <div class="d-flex align-items-baseline justify-content-between mt-2">
          <span class="display-5 fw-bold text-danger font-headline"><?php echo (int)$stats['total_classes']; ?></span>
          <span class="badge bg-danger-subtle text-danger border border-danger">Scheduled</span>
        </div>
        <div class="small text-secondary mt-2">Active training slots open for booking.</div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="bento-card">
        <span class="text-secondary small text-uppercase fw-bold font-headline">Training Disciplines</span>
        <div class="d-flex align-items-baseline justify-content-between mt-2">
          <span class="display-5 fw-bold text-warning font-headline"><?php echo (int)$stats['total_categories']; ?></span>
          <span class="badge bg-warning-subtle text-warning border border-warning">Disciplines</span>
        </div>
        <div class="small text-secondary mt-2">Strength, HIIT, Mobility & Combat.</div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="bento-card">
        <span class="text-secondary small text-uppercase fw-bold font-headline">Active Members</span>
        <div class="d-flex align-items-baseline justify-content-between mt-2">
          <span class="display-5 fw-bold text-success font-headline"><?php echo (int)$stats['total_users']; ?></span>
          <span class="badge bg-success-subtle text-success border border-success">Registered</span>
        </div>
        <div class="small text-secondary mt-2">Verified athletes on the platform.</div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <!-- Left Column: Authenticated Profile & RBAC Info -->
    <div class="col-12 col-lg-5">
      <div class="bento-card">
        <div class="bento-card-header">
          <h5 class="fw-bold font-headline text-uppercase mb-0">Authenticated Profile</h5>
          <span class="live-beacon"></span>
        </div>
        <div class="p-1">
          <ul class="list-group list-group-flush mb-3">
            <li class="list-group-item px-0 d-flex justify-content-between">
              <span class="text-secondary">Account ID:</span>
              <span class="fw-semibold text-white">#<?php echo htmlspecialchars($user['id']); ?></span>
            </li>
            <li class="list-group-item px-0 d-flex justify-content-between">
              <span class="text-secondary">Full Name:</span>
              <span class="fw-semibold text-white"><?php echo htmlspecialchars($user['full_name']); ?></span>
            </li>
            <li class="list-group-item px-0 d-flex justify-content-between">
              <span class="text-secondary">Email:</span>
              <span class="fw-semibold text-white"><?php echo htmlspecialchars($user['email']); ?></span>
            </li>
            <li class="list-group-item px-0 d-flex justify-content-between">
              <span class="text-secondary">Assigned Role:</span>
              <span class="vip-role-badge <?php echo $roleClass; ?> text-uppercase"><?php echo htmlspecialchars($user['role']); ?></span>
            </li>
          </ul>

          <!-- RBAC Privilege Notice -->
          <?php if ($isStaff): ?>
            <div class="alert alert-success border-0 bg-success bg-opacity-10 p-3 rounded-3 mb-3 border-start border-4 border-success">
              <h6 class="fw-bold text-success font-headline text-uppercase mb-1">Staff Clearance Active (Task 6)</h6>
              <p class="small text-secondary mb-0">
                You have administrative authority to <strong>Create, Update, and Delete</strong> fitness classes across the platform.
              </p>
            </div>
          <?php else: ?>
            <div class="glass-panel p-3 rounded-3 mb-3 border-start border-4 border-secondary">
              <h6 class="fw-bold text-white font-headline text-uppercase mb-1">Member Privilege Notice (Task 6)</h6>
              <p class="small text-secondary mb-2">
                Your role is <strong>Member</strong> (Read-Only access). You can browse training classes and book sessions, but cannot create or edit records.
              </p>
              <a href="class_form.php" class="btn btn-outline-apex btn-sm w-100 fw-semibold">
                &rarr; Test RBAC Security (Attempt class_form.php access)
              </a>
            </div>
          <?php endif; ?>

          <!-- Technical Evidence box for Lab 2 Task 5.1 -->
          <div class="glass-panel p-3 rounded-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <strong class="small text-secondary font-headline text-uppercase">Session ID (Task 5.1 Evidence):</strong>
              <span class="badge bg-success small">Verified</span>
            </div>
            <code class="small text-break d-block mb-1 text-warning"><?php echo htmlspecialchars($sessionId); ?></code>
            <small class="text-secondary d-block">
              Session state actively stored on server and verified on every HTTP request.
            </small>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: Training Classes Management / Overview -->
    <div class="col-12 col-lg-7">
      <div class="bento-card">
        <div class="bento-card-header">
          <h5 class="fw-bold font-headline text-uppercase mb-0">Training Programs Management</h5>
          <div class="d-flex gap-2">
            <?php if ($isStaff): ?>
              <a href="class_form.php" class="btn btn-sm btn-apex-crimson fw-semibold">+ New Class</a>
            <?php endif; ?>
            <a href="classes.php" class="small text-danger fw-semibold text-decoration-none align-self-center">All Classes &rarr;</a>
          </div>
        </div>
        <div class="p-1">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 small">
              <thead>
                <tr>
                  <th>Class Title</th>
                  <th>Discipline</th>
                  <th>Coach</th>
                  <th>Price</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (array_slice($classes, 0, 5) as $cls): ?>
                  <tr>
                    <td class="fw-semibold text-white">
                      <?php echo htmlspecialchars($cls['title']); ?>
                    </td>
                    <td>
                      <span class="badge bg-secondary-subtle text-white border small">
                        <?php echo htmlspecialchars($cls['category_name']); ?>
                      </span>
                    </td>
                    <td class="text-secondary"><?php echo htmlspecialchars($cls['trainer_name']); ?></td>
                    <td class="fw-bold text-danger font-headline">$<?php echo number_format((float)$cls['price'], 2); ?></td>
                    <td class="text-end">
                      <a href="item_details.php?id=<?php echo urlencode($cls['id']); ?>" class="btn btn-sm btn-outline-apex px-2 py-0.5">
                        View
                      </a>
                      <?php if ($isStaff): ?>
                        <a href="class_form.php?id=<?php echo urlencode($cls['id']); ?>" class="btn btn-sm btn-outline-apex px-2 py-0.5 text-warning border-warning">
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
  </div>

  <!-- Task 5 Technical Evaluation: PHP Sessions & Client Cookies Inspector -->
  <div class="glass-panel overflow-hidden mt-4">
    <div class="p-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom border-secondary border-opacity-25" style="background: rgba(9, 13, 22, 0.6);">
      <div class="d-flex align-items-center gap-2">
        <span class="hero-badge">Task 5 Deliverable</span>
        <strong class="fs-6 font-headline text-uppercase text-white">Client-Side Cookies & Session Inspector</strong>
      </div>
      <div>
        <a href="clear_cookies.php?return=dashboard.php" class="btn btn-sm btn-outline-apex text-danger border-danger fw-semibold" onclick="return confirm('Reset all tracking cookies and preferences?');">
          Reset Preferences (Clear Cookies)
        </a>
      </div>
    </div>
    
    <div class="p-4">
      <div class="row g-3 mb-4">
        <!-- Quick Preference Badges -->
        <div class="col-12 col-md-4">
          <div class="p-3 glass-panel h-100">
            <strong class="d-block small text-secondary font-headline text-uppercase">Active UI Theme (Cookie 1)</strong>
            <div class="d-flex align-items-center justify-content-between mt-2">
              <span class="fw-bold text-white text-uppercase font-headline"><?php echo htmlspecialchars($activeTheme); ?> Mode</span>
              <a href="set_theme.php?theme=<?php echo ($activeTheme === 'dark') ? 'light' : 'dark'; ?>&return=dashboard.php" class="btn btn-sm btn-outline-apex">
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
                <span class="fw-bold text-danger text-truncate me-2"><?php echo htmlspecialchars($recentClass['title']); ?></span>
                <a href="item_details.php?id=<?php echo urlencode($recentClass['id']); ?>" class="btn btn-sm btn-outline-apex">
                  Revisit
                </a>
              </div>
              <small class="text-secondary d-block mt-2">Cookie <code>apex_recent_class</code> (7-day lifespan).</small>
            <?php else: ?>
              <span class="text-secondary small d-block mt-2">No class visited recently yet.</span>
              <small class="text-secondary d-block mt-1">Visit any class in catalog to activate.</small>
            <?php endif; ?>
          </div>
        </div>

        <div class="col-12 col-md-4">
          <div class="p-3 glass-panel h-100">
            <strong class="d-block small text-secondary font-headline text-uppercase">Favorite Discipline (Cookie 3)</strong>
            <?php if ($favCategory): ?>
              <div class="d-flex align-items-center justify-content-between mt-2">
                <span class="fw-bold text-white"><?php echo htmlspecialchars($favCategory['name']); ?></span>
                <a href="classes.php?category_id=<?php echo urlencode($favCategory['id']); ?>" class="btn btn-sm btn-outline-apex">
                  Filter
                </a>
              </div>
              <small class="text-secondary d-block mt-2">Cookie <code>apex_fav_category</code> (7-day lifespan).</small>
            <?php else: ?>
              <span class="text-secondary small d-block mt-2">No category filtered yet.</span>
              <small class="text-secondary d-block mt-1">Filter classes by category to activate.</small>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Live Inspection Table for Evaluator -->
      <h6 class="fw-bold font-headline text-uppercase text-white mb-2">Technical Cookies & Session Inspector Table</h6>
      <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle small mb-0">
          <thead>
            <tr>
              <th>Cookie Name</th>
              <th>Current Stored Value</th>
              <th>System Purpose</th>
              <th>Lifespan & Expiration</th>
              <th>Security Level</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($cookiesInspector as $c): ?>
              <tr>
                <td class="fw-semibold text-danger"><code><?php echo htmlspecialchars($c['name']); ?></code></td>
                <td><code class="text-white"><?php echo htmlspecialchars($c['value']); ?></code></td>
                <td class="text-secondary"><?php echo htmlspecialchars($c['purpose']); ?></td>
                <td><span class="badge bg-secondary-subtle text-white border"><?php echo htmlspecialchars($c['lifespan']); ?></span></td>
                <td><span class="badge bg-success-subtle text-success border border-success"><?php echo htmlspecialchars($c['security']); ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="mt-3 text-secondary small">
        <strong>Task 5.2 Security Guarantee:</strong> All persistent cookies above store strictly non-sensitive user preferences. Under no circumstances are passwords, secret keys, or authentication tokens stored in client cookies.
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
