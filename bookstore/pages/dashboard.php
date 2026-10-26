<?php
/**
 * Protected Member & Coach Dashboard (Lab 2 Tasks 4.4, 5.1 & 6)
 * Enforces authentication via requireLogin()
 * Role-Based Access Control: Displays staff management tools or member privileges
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

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

// Flash messaging
$flashSuccess = getFlash('success');
$flashError   = getFlash('error');

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

// Role badge styling helper
$roleBadges = [
    'admin'   => 'bg-dark text-white border border-secondary',
    'trainer' => 'bg-primary text-white',
    'member'  => 'bg-danger text-white',
];
$badgeClass = $roleBadges[$user['role']] ?? 'bg-secondary text-white';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Member Dashboard - Apex Fitness Club</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../public/css/style.css">
</head>
<body class="bg-light">

<?php require_once __DIR__ . '/../components/header.php'; ?>

<main class="container py-5">
  <!-- Flash Alerts -->
  <?php if ($flashSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm py-3 mb-4" role="alert">
      <strong>Success!</strong> <?php echo htmlspecialchars($flashSuccess); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <?php if ($flashError): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm py-3 mb-4" role="alert">
      <strong>Notice:</strong> <?php echo htmlspecialchars($flashError); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Welcome Header -->
  <div class="row align-items-center mb-4">
    <div class="col-md-7">
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="badge <?php echo $badgeClass; ?> px-3 py-2 text-uppercase fs-6">
          <?php echo htmlspecialchars(strtoupper($user['role'])); ?> ACCOUNT
        </span>
        <span class="badge bg-success-subtle text-success border border-success px-2 py-1 small">
          Active Session
        </span>
      </div>
      <h1 class="display-6 fw-bold mb-1">Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
      <p class="text-secondary mb-0">Here is your training hub and personalized membership overview.</p>
    </div>
    <div class="col-md-5 text-md-end mt-3 mt-md-0 d-flex justify-content-md-end gap-2 flex-wrap">
      <?php if ($isStaff): ?>
        <a href="class_form.php" class="btn btn-danger fw-semibold shadow-sm">+ Create Class</a>
      <?php endif; ?>
      <a href="classes.php" class="btn btn-outline-dark">Browse Catalog</a>
      <a href="../auth/logout.php" class="btn btn-outline-danger">Log Out</a>
    </div>
  </div>

  <!-- Metric Cards -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
      <div class="card border-0 shadow-sm p-3 rounded-3 bg-white h-100">
        <span class="text-muted small text-uppercase fw-semibold">Available Programs</span>
        <div class="d-flex align-items-baseline justify-content-between mt-2">
          <span class="display-6 fw-bold text-danger"><?php echo (int)$stats['total_classes']; ?></span>
          <span class="badge bg-danger-subtle text-danger">Scheduled</span>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card border-0 shadow-sm p-3 rounded-3 bg-white h-100">
        <span class="text-muted small text-uppercase fw-semibold">Training Categories</span>
        <div class="d-flex align-items-baseline justify-content-between mt-2">
          <span class="display-6 fw-bold text-dark"><?php echo (int)$stats['total_categories']; ?></span>
          <span class="badge bg-secondary-subtle text-dark">Disciplines</span>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card border-0 shadow-sm p-3 rounded-3 bg-white h-100">
        <span class="text-muted small text-uppercase fw-semibold">Registered Athletes</span>
        <div class="d-flex align-items-baseline justify-content-between mt-2">
          <span class="display-6 fw-bold text-dark"><?php echo (int)$stats['total_users']; ?></span>
          <span class="badge bg-dark-subtle text-dark">Active Members</span>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4 mb-4">
    <!-- Left Column: Authenticated Profile & RBAC Info -->
    <div class="col-12 col-lg-5">
      <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
        <div class="card-header bg-white border-bottom py-3">
          <h5 class="fw-bold mb-0">Authenticated Profile</h5>
        </div>
        <div class="card-body p-4">
          <ul class="list-group list-group-flush mb-3">
            <li class="list-group-item px-0 d-flex justify-content-between">
              <span class="text-muted">Account ID:</span>
              <span class="fw-semibold">#<?php echo htmlspecialchars($user['id']); ?></span>
            </li>
            <li class="list-group-item px-0 d-flex justify-content-between">
              <span class="text-muted">Full Name:</span>
              <span class="fw-semibold"><?php echo htmlspecialchars($user['full_name']); ?></span>
            </li>
            <li class="list-group-item px-0 d-flex justify-content-between">
              <span class="text-muted">Email:</span>
              <span class="fw-semibold"><?php echo htmlspecialchars($user['email']); ?></span>
            </li>
            <li class="list-group-item px-0 d-flex justify-content-between">
              <span class="text-muted">Assigned Role:</span>
              <span class="badge <?php echo $badgeClass; ?> text-uppercase"><?php echo htmlspecialchars($user['role']); ?></span>
            </li>
          </ul>

          <!-- RBAC Privilege Notice -->
          <?php if ($isStaff): ?>
            <div class="alert alert-success border-0 bg-success-subtle p-3 rounded-3 mb-3">
              <h6 class="fw-bold text-success mb-1">Staff Clearance Active (Task 6)</h6>
              <p class="small text-secondary mb-0">
                You have administrative authority to <strong>Create, Update, and Delete</strong> fitness classes across the platform.
              </p>
            </div>
          <?php else: ?>
            <div class="alert alert-secondary border-0 bg-light p-3 rounded-3 mb-3 border">
              <h6 class="fw-bold text-dark mb-1">Member Privilege Notice (Task 6)</h6>
              <p class="small text-muted mb-2">
                Your role is <strong>Member</strong> (Read-Only access). You can browse training classes and book sessions, but cannot create or edit records.
              </p>
              <a href="class_form.php" class="btn btn-outline-danger btn-sm w-100 fw-semibold">
                &rarr; Test RBAC Security (Attempt class_form.php access)
              </a>
            </div>
          <?php endif; ?>

          <!-- Technical Evidence box for Lab 2 Task 5.1 -->
          <div class="bg-light p-3 rounded-3 border">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <strong class="small text-secondary">Session ID (Task 5.1 Evidence):</strong>
              <span class="badge bg-success small">Verified</span>
            </div>
            <code class="small text-break d-block mb-1"><?php echo htmlspecialchars($sessionId); ?></code>
            <small class="text-muted d-block">
              Session state actively stored and verified across requests.
            </small>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Column: Training Classes Management / Overview -->
    <div class="col-12 col-lg-7">
      <div class="card border-0 shadow-sm rounded-4 h-100 bg-white">
        <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h5 class="fw-bold mb-0">Training Programs Management</h5>
          <div class="d-flex gap-2">
            <?php if ($isStaff): ?>
              <a href="class_form.php" class="btn btn-sm btn-danger fw-semibold">+ New Class</a>
            <?php endif; ?>
            <a href="classes.php" class="small text-danger fw-semibold text-decoration-none align-self-center">All Classes &rarr;</a>
          </div>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="ps-4">Program Title</th>
                  <th>Coach</th>
                  <th>Price</th>
                  <th class="text-end pe-4">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($classes as $c): ?>
                  <tr>
                    <td class="ps-4 fw-semibold text-dark">
                      <a href="item_details.php?id=<?php echo urlencode($c['id']); ?>" class="text-decoration-none text-dark">
                        <?php echo htmlspecialchars($c['title']); ?>
                      </a>
                      <span class="d-block small text-muted">
                        <?php echo htmlspecialchars($c['category_name']); ?>
                      </span>
                    </td>
                    <td class="text-secondary small">
                      <?php echo htmlspecialchars($c['trainer_name']); ?>
                    </td>
                    <td class="fw-bold text-danger">
                      $<?php echo number_format((float)$c['price'], 2); ?>
                    </td>
                    <td class="text-end pe-4">
                      <div class="d-inline-flex gap-1">
                        <a href="item_details.php?id=<?php echo urlencode($c['id']); ?>" class="btn btn-sm btn-outline-secondary" title="View details">
                          View
                        </a>
                        <?php if ($isStaff): ?>
                          <a href="class_form.php?id=<?php echo urlencode($c['id']); ?>" class="btn btn-sm btn-outline-primary" title="Edit class">
                            Edit
                          </a>
                          <form action="delete_class.php" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this class?');">
                            <input type="hidden" name="class_id" value="<?php echo (int)$c['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete class">
                              &times;
                            </button>
                          </form>
                        <?php endif; ?>
                      </div>
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
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
