<?php
/**
 * Fitness Classes Management & Catalog (Lab 2 Tasks 3 & 6)
 * Read: Publicly viewable catalog with search & category filtering
 * Delete / Create / Update actions: Restricted to admin and trainer roles
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/preferences.php';

$isStaff = hasRole(['admin', 'trainer']);
$currentUser = getCurrentUser();

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
    $sql .= " AND (fc.title LIKE :search OR fc.description LIKE :search OR fc.trainer_name LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

if (!empty($categoryId)) {
    $sql .= " AND fc.category_id = :category_id";
    $params['category_id'] = $categoryId;
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
  <title>Training Classes Catalog & Management - Apex Fitness</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../public/css/style.css">
</head>
<body class="bg-light">

<?php require_once __DIR__ . '/../components/header.php'; ?>

<main class="container py-5">
  <!-- Page Header -->
  <div class="row align-items-center mb-4">
    <div class="col-md-8">
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="badge bg-danger text-uppercase px-3 py-1">Class Directory</span>
        <?php if ($isStaff): ?>
          <span class="badge bg-dark text-white px-2 py-1 small">
            Staff Access: <?php echo htmlspecialchars(strtoupper($currentUser['role'])); ?>
          </span>
        <?php endif; ?>
      </div>
      <h1 class="display-6 fw-bold mb-1">Training Classes Catalog</h1>
      <p class="text-secondary mb-0">Browse through our structured strength, cardio, and athletic conditioning sessions.</p>
    </div>
    <div class="col-md-4 text-md-end mt-3 mt-md-0">
      <?php if ($isStaff): ?>
        <a href="class_form.php" class="btn btn-danger btn-lg fw-semibold shadow-sm">
          + Add New Class
        </a>
      <?php else: ?>
        <a href="../public/index.php" class="btn btn-outline-dark">
          &larr; Back to Home
        </a>
      <?php endif; ?>
    </div>
  </div>

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

  <!-- Search & Filter Controls -->
  <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
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
        <label for="category_id" class="form-label fw-semibold small text-secondary">Discipline Category</label>
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
        <button type="submit" class="btn btn-dark w-100 fw-semibold">
          Filter
        </button>
        <?php if (!empty($search) || !empty($categoryId)): ?>
          <a href="classes.php" class="btn btn-outline-secondary">
            Reset
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Class Count Summary -->
  <div class="d-flex justify-content-between align-items-center mb-3 text-secondary small">
    <span>Showing <strong><?php echo count($classes); ?></strong> programs</span>
    <?php if ($isStaff): ?>
      <span class="text-danger fw-semibold">Admin/Trainer CRUD permissions active</span>
    <?php else: ?>
      <span>Guest / Member view mode</span>
    <?php endif; ?>
  </div>

  <!-- Classes Listing Cards & Management Controls -->
  <?php if (empty($classes)): ?>
    <div class="alert alert-info shadow-sm p-4 text-center my-4">
      <h5 class="fw-bold mb-1">No Training Classes Found</h5>
      <p class="text-muted mb-3">No classes matched your filter or search query.</p>
      <a href="classes.php" class="btn btn-outline-dark btn-sm">Clear Filters</a>
    </div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($classes as $class): ?>
        <div class="col-12 col-md-6 col-lg-4">
          <div class="card h-100 shadow-sm border-0 class-card d-flex flex-column">
            <!-- Media & Category Badge -->
            <div class="position-relative">
              <img
                src="../public/<?php echo htmlspecialchars($class['image_url']); ?>"
                class="card-img-top"
                alt="<?php echo htmlspecialchars($class['title']); ?>">
              <span class="badge bg-danger position-absolute top-0 end-0 m-3 px-3 py-2 shadow-sm">
                <?php echo htmlspecialchars($class['category_name']); ?>
              </span>
            </div>

            <!-- Card Body -->
            <div class="card-body d-flex flex-column p-4">
              <h5 class="card-title fw-bold text-dark mb-2">
                <?php echo htmlspecialchars($class['title']); ?>
              </h5>
              <p class="card-text text-muted small flex-grow-1">
                <?php echo htmlspecialchars(mb_strimwidth($class['description'], 0, 140, '...')); ?>
              </p>

              <div class="class-meta my-3 pt-3 border-top">
                <div class="d-flex align-items-center text-secondary small mb-1">
                  <span class="fw-semibold text-dark me-2">Lead Coach:</span>
                  <span><?php echo htmlspecialchars($class['trainer_name']); ?></span>
                </div>
                <div class="d-flex align-items-center text-secondary small">
                  <span class="fw-semibold text-dark me-2">Schedule:</span>
                  <span><?php echo htmlspecialchars($class['schedule_info']); ?></span>
                </div>
              </div>

              <!-- Price & Public Action -->
              <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                <div>
                  <span class="text-muted small d-block">Session Pass</span>
                  <span class="fs-5 fw-bold text-danger">$<?php echo number_format((float)$class['price'], 2); ?></span>
                </div>
                <a href="item_details.php?id=<?php echo urlencode($class['id']); ?>" class="btn btn-outline-dark btn-sm px-3 fw-semibold">
                  View Details &rarr;
                </a>
              </div>

              <!-- Staff Management Action Bar (CRUD: Update & Delete) -->
              <?php if ($isStaff): ?>
                <div class="d-flex gap-2 pt-3 mt-3 border-top bg-light -mx-4 -mb-4 p-3 rounded-bottom">
                  <a href="class_form.php?id=<?php echo urlencode($class['id']); ?>" class="btn btn-sm btn-primary flex-grow-1 fw-semibold">
                    Edit
                  </a>
                  <form action="delete_class.php" method="POST" class="d-inline flex-grow-1" onsubmit="return confirm('Are you sure you want to permanently delete this fitness class: <?php echo htmlspecialchars(addslashes($class['title'])); ?>?');">
                    <input type="hidden" name="class_id" value="<?php echo (int)$class['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger w-100 fw-semibold">
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
