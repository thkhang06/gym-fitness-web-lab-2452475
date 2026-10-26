<?php
/**
 * Fitness Classes Management & Catalog (Lab 2 Tasks 3, 5.2 & 6)
 * Read: Publicly viewable catalog with search & category filtering
 * Delete / Create / Update actions: Restricted to admin and trainer roles
 * Stitch Design System Enhancement (Project ID: 6697286312568266889)
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/preferences.php';

$isStaff = hasRole(['admin', 'trainer']);
$currentUser = getCurrentUser();
$activeTheme = getActiveTheme();

// Flash messaging
$flashSuccess = getFlash('success');
$flashError   = getFlash('error');

// Filter & Search parameters
$search     = trim($_GET['search'] ?? '');
$categoryId = filter_var($_GET['category_id'] ?? null, FILTER_VALIDATE_INT);

// Retrieve all categories for the filter dropdown
try {
    $categoriesStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $allCategories = $categoriesStmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to load categories: " . $e->getMessage());
    $allCategories = [];
}

// Task 5.2: Track favorite category preference in client cookie when filtering
if ($categoryId) {
    foreach ($allCategories as $cat) {
        if ((int)$cat['id'] === $categoryId) {
            trackFavoriteCategory($categoryId, $cat['name']);
            break;
        }
    }
}

// Build dynamic filtered query
$sql = "
    SELECT 
        fc.id,
        fc.title,
        fc.description,
        fc.trainer_name,
        fc.schedule_info,
        fc.price,
        fc.image_url,
        fc.created_at,
        c.id AS category_id,
        c.name AS category_name
    FROM fitness_classes fc
    INNER JOIN categories c ON fc.category_id = c.id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (fc.title LIKE :searchTitle OR fc.description LIKE :searchDesc OR fc.trainer_name LIKE :searchTrainer)";
    $params['searchTitle']   = "%{$search}%";
    $params['searchDesc']    = "%{$search}%";
    $params['searchTrainer'] = "%{$search}%";
}

if (!empty($categoryId)) {
    $sql .= " AND fc.category_id = :catId";
    $params['catId'] = $categoryId;
}

$sql .= " ORDER BY fc.id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $classes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to query classes list: " . $e->getMessage());
    $classes = [];
    $flashError = "Failed to load classes from the database. Please try again.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Training Classes Catalog & Management - TIGER FITNESS</title>
  <link rel="icon" type="image/svg+xml" href="../public/images/favicon.svg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../public/css/style.css">
</head>
<body class="<?php echo ($activeTheme === 'dark') ? 'theme-dark' : ''; ?>" data-bs-theme="<?php echo $activeTheme; ?>">

<?php require_once __DIR__ . '/../components/header.php'; ?>

<main class="container py-5">
  <!-- Page Header -->
  <div class="row align-items-center mb-4 g-3">
    <div class="col-md-8">
      <div class="d-flex align-items-center gap-2 mb-2">
        <span class="hero-badge">Class Directory</span>
        <?php if ($isStaff): ?>
          <span class="vip-role-badge vip-role-admin">
            Staff Access: <?php echo htmlspecialchars(strtoupper($currentUser['role'])); ?>
          </span>
        <?php endif; ?>
      </div>
      <h1 class="display-6 fw-bold font-headline mb-1 text-uppercase">Training Classes Catalog</h1>
      <p class="text-secondary mb-0">Browse through our structured strength, cardio, and athletic conditioning sessions.</p>
    </div>
    <div class="col-md-4 text-md-end">
      <?php if ($isStaff): ?>
        <a href="class_form.php" class="btn btn-apex-crimson btn-lg px-4 fw-semibold shadow-sm">
          + Add New Class
        </a>
      <?php else: ?>
        <a href="../public/index.php" class="btn btn-outline-apex">
          &larr; Back to Home
        </a>
      <?php endif; ?>
    </div>
  </div>

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

  <!-- Search & Filter Controls (Glass Panel) -->
  <div class="glass-panel p-4 mb-4">
    <form action="classes.php" method="GET" class="row g-3 align-items-end">
      <div class="col-12 col-md-5">
        <label for="search" class="form-label fw-semibold small text-secondary">Search Keyword</label>
        <input
          type="text"
          class="form-control"
          id="search"
          name="search"
          placeholder="Search by class title, coach, or description..."
          value="<?php echo htmlspecialchars($search); ?>">
      </div>

      <div class="col-12 col-md-4">
        <label for="category_id" class="form-label fw-semibold small text-secondary">Discipline Category (Saves Cookie)</label>
        <select class="form-select" id="category_id" name="category_id">
          <option value="">All Categories</option>
          <?php foreach ($allCategories as $cat): ?>
            <option
              value="<?php echo (int)$cat['id']; ?>"
              <?php echo ($categoryId === (int)$cat['id']) ? 'selected' : ''; ?>>
              <?php echo htmlspecialchars($cat['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12 col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-apex-crimson w-100 fw-semibold">
          Filter
        </button>
        <?php if (!empty($search) || !empty($categoryId)): ?>
          <a href="classes.php" class="btn btn-outline-apex">
            Reset
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Class Count Summary -->
  <div class="d-flex justify-content-between align-items-center mb-3 text-secondary small">
    <span>Showing <strong class="text-white"><?php echo count($classes); ?></strong> programs</span>
    <?php if ($isStaff): ?>
      <span class="text-danger fw-semibold">Staff CRUD active</span>
    <?php else: ?>
      <span>Guest / Member view mode</span>
    <?php endif; ?>
  </div>

  <!-- Classes Listing Cards & Management Controls -->
  <?php if (empty($classes)): ?>
    <div class="glass-panel p-5 text-center my-4">
      <h5 class="fw-bold font-headline text-uppercase mb-1">No Training Classes Found</h5>
      <p class="text-secondary mb-3">No classes matched your filter or search query.</p>
      <a href="classes.php" class="btn btn-outline-apex btn-sm">Clear Filters</a>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($classes as $class): ?>
        <div class="col-12 col-md-6 col-lg-4">
          <div class="class-card h-100">
            <!-- Media & Category Badge -->
            <div class="class-card-img-wrap">
              <img
                src="../public/<?php echo htmlspecialchars($class['image_url']); ?>"
                alt="<?php echo htmlspecialchars($class['title']); ?>">
              <div class="class-card-overlay"></div>
              <span class="class-discipline-badge">
                <?php echo htmlspecialchars($class['category_name']); ?>
              </span>
            </div>

            <!-- Card Body -->
            <div class="card-body d-flex flex-column p-4">
              <h5 class="card-title fw-bold font-headline mb-2" style="font-size: 1.25rem;">
                <?php echo htmlspecialchars($class['title']); ?>
              </h5>
              <p class="card-text text-secondary small flex-grow-1 mb-3" style="line-height: 1.6;">
                <?php echo htmlspecialchars(mb_strimwidth($class['description'], 0, 140, '...')); ?>
              </p>

              <div class="pt-3 border-top border-secondary border-opacity-25 mb-3">
                <div class="d-flex align-items-center justify-content-between small text-secondary mb-1.5">
                  <span class="fw-semibold">Lead Coach:</span>
                  <span class="text-white fw-medium"><?php echo htmlspecialchars($class['trainer_name']); ?></span>
                </div>
                <div class="d-flex align-items-center justify-content-between small text-secondary">
                  <span class="fw-semibold">Schedule:</span>
                  <span class="opacity-90"><?php echo htmlspecialchars($class['schedule_info']); ?></span>
                </div>
              </div>

              <!-- Price & Public Action -->
              <div class="d-flex align-items-center justify-content-between pt-3 border-top border-secondary border-opacity-25">
                <div>
                  <span class="small text-secondary d-block font-headline text-uppercase" style="font-size: 0.7rem;">Session Pass</span>
                  <span class="class-price-tag">$<?php echo number_format((float)$class['price'], 2); ?></span>
                </div>
                <a href="item_details.php?id=<?php echo urlencode($class['id']); ?>" class="btn btn-outline-apex btn-sm px-3 fw-semibold">
                  View Details &rarr;
                </a>
              </div>

              <!-- Staff Management Action Bar (CRUD: Update & Delete) -->
              <?php if ($isStaff): ?>
                <div class="d-flex gap-2 pt-3 mt-3 border-top border-secondary border-opacity-25">
                  <a href="class_form.php?id=<?php echo urlencode($class['id']); ?>" class="btn btn-sm btn-outline-apex flex-grow-1 fw-semibold">
                    Edit
                  </a>
                  <form action="delete_class.php" method="POST" class="d-inline flex-grow-1" onsubmit="return confirm('Are you sure you want to permanently delete this fitness class: <?php echo htmlspecialchars(addslashes($class['title'])); ?>?');">
                    <input type="hidden" name="class_id" value="<?php echo (int)$class['id']; ?>">
                    <input type="hidden" name="id" value="<?php echo (int)$class['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-danger w-100 fw-semibold">
                      Delete
                    </button>
                  </form>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
