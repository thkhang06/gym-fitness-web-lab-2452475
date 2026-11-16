<?php
/**
 * Fitness Classes Management & Live AJAX Catalog (Lab 2 Tasks 3, 5.2, 6 & Lab 3 Task 1)
 * Read & Live Filter: Publicly viewable with AJAX search/filtering without page reload
 * Create / Update / Delete actions: Restricted to admin and trainer roles
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/preferences.php';
require_once __DIR__ . '/../config/booking_service.php';

$isStaff = hasRole(['admin', 'trainer']);
$currentUser = getCurrentUser();
$activeTheme = getActiveTheme();

$flashSuccess = getFlash('success');
$flashError   = getFlash('error');

$search       = trim($_GET['search'] ?? '');
$categoryId   = filter_var($_GET['category_id'] ?? null, FILTER_VALIDATE_INT);
$trainer      = trim($_GET['trainer'] ?? '');
$availability = trim($_GET['availability'] ?? 'all');
$sort         = trim($_GET['sort'] ?? 'newest');
$filterDate   = trim($_GET['date'] ?? date('Y-m-d', strtotime('+1 day')));
if (!isValidBookingDate($filterDate)) {
    $filterDate = date('Y-m-d', strtotime('+1 day'));
}

// Retrieve categories and distinct trainers for filter controls
try {
    $allCategories = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
    $allTrainers   = $pdo->query("SELECT DISTINCT trainer_name FROM fitness_classes ORDER BY trainer_name ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    error_log("Failed to load filter metadata: " . $e->getMessage());
    $allCategories = [];
    $allTrainers   = [];
}

if ($categoryId) {
    foreach ($allCategories as $cat) {
        if ((int)$cat['id'] === $categoryId) {
            trackFavoriteCategory($categoryId, $cat['name']);
            break;
        }
    }
}

$sql = "
    SELECT
        fc.id,
        fc.title,
        fc.description,
        fc.trainer_name,
        fc.schedule_info,
        fc.price,
        fc.max_capacity,
        fc.image_url,
        fc.created_at,
        c.id AS category_id,
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
$params = ['booking_date' => $filterDate];

if ($search !== '') {
    $sql .= " AND (fc.title LIKE :searchTitle OR fc.description LIKE :searchDesc OR fc.trainer_name LIKE :searchTrainer OR c.name LIKE :searchCat)";
    $like = "%{$search}%";
    $params['searchTitle']   = $like;
    $params['searchDesc']    = $like;
    $params['searchTrainer'] = $like;
    $params['searchCat']     = $like;
}

if (!empty($categoryId)) {
    $sql .= " AND fc.category_id = :catId";
    $params['catId'] = $categoryId;
}

if ($trainer !== '') {
    $sql .= " AND fc.trainer_name = :trainerName";
    $params['trainerName'] = $trainer;
}

if ($availability === 'available') {
    $sql .= " AND (fc.max_capacity - COALESCE(bk.booked_count, 0)) > 0";
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

$seo = [
    'title'       => 'Training Classes Directory & Live AJAX Search - TIGER FITNESS',
    'description' => 'Search and filter Tiger Fitness training classes in real time by discipline, coach, and open spot availability without reloading.',
    'keywords'    => 'Tiger Fitness classes, barbell mastery, HIIT cardio, Olympic weightlifting, boxing conditioning, AJAX filter',
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
  <!-- Page Header -->
  <div class="row align-items-center mb-4 g-3">
    <div class="col-md-8">
      <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
        <span class="hero-badge">Live AJAX Directory</span>
        <?php if ($isStaff): ?>
          <span class="vip-role-badge vip-role-admin">
            Staff Access: <?php echo e(strtoupper($currentUser['role'])); ?>
          </span>
        <?php endif; ?>
      </div>
      <h1 class="display-6 fw-bold font-headline mb-1 text-uppercase">Training Classes Catalog</h1>
      <p class="text-secondary mb-0">Filter instantly by keyword, discipline, coach, or remaining spots without reloading the page.</p>
    </div>
    <div class="col-md-4 text-md-end d-flex justify-content-md-end gap-2 flex-wrap">
      <?php if ($isStaff): ?>
        <a href="class_form.php" class="btn btn-apex-crimson px-4 fw-semibold shadow-sm">
          + Add New Class
        </a>
      <?php endif; ?>
      <a href="bookings.php" class="btn btn-outline-apex fw-semibold">
        Book a Session &rarr;
      </a>
    </div>
  </div>

  <!-- Flash Alerts -->
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

  <!-- Search & Filter Controls (AJAX Powered) -->
  <section aria-label="Live AJAX Search and Filters" class="glass-panel p-4 mb-4">
    <form action="classes.php" method="GET" id="ajax-class-filter-form" class="row g-3 align-items-end">
      <div class="col-12 col-md-4">
        <label for="search" class="form-label fw-semibold small text-secondary">Search Keyword (Live AJAX)</label>
        <input
          type="text"
          class="form-control"
          id="search"
          name="search"
          placeholder="Class title, coach, or discipline..."
          value="<?php echo e($search); ?>">
      </div>

      <div class="col-12 col-sm-6 col-md-2">
        <label for="category_id" class="form-label fw-semibold small text-secondary">Category</label>
        <select class="form-select" id="category_id" name="category_id">
          <option value="">All Categories</option>
          <?php foreach ($allCategories as $cat): ?>
            <option
              value="<?php echo (int)$cat['id']; ?>"
              <?php echo ($categoryId === (int)$cat['id']) ? 'selected' : ''; ?>>
              <?php echo e($cat['name']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12 col-sm-6 col-md-2">
        <label for="trainer" class="form-label fw-semibold small text-secondary">Coach</label>
        <select class="form-select" id="trainer" name="trainer">
          <option value="">All Coaches</option>
          <?php foreach ($allTrainers as $trName): ?>
            <option value="<?php echo e($trName); ?>" <?php echo ($trainer === $trName) ? 'selected' : ''; ?>>
              <?php echo e($trName); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-12 col-sm-6 col-md-2">
        <label for="availability" class="form-label fw-semibold small text-secondary">Availability</label>
        <select class="form-select" id="availability" name="availability">
          <option value="all" <?php echo ($availability === 'all') ? 'selected' : ''; ?>>All Classes</option>
          <option value="available" <?php echo ($availability === 'available') ? 'selected' : ''; ?>>Open Spots Only</option>
          <option value="full" <?php echo ($availability === 'full') ? 'selected' : ''; ?>>Full Classes</option>
        </select>
      </div>

      <div class="col-12 col-sm-6 col-md-2">
        <label for="filter_date" class="form-label fw-semibold small text-secondary">Check Date</label>
        <input type="date" class="form-control" id="filter_date" name="date" value="<?php echo e($filterDate); ?>">
      </div>

      <div class="col-12 col-md-4">
        <label for="sort" class="form-label fw-semibold small text-secondary">Sort Order</label>
        <select class="form-select" id="sort" name="sort">
          <option value="newest" <?php echo ($sort === 'newest') ? 'selected' : ''; ?>>Newest Programs</option>
          <option value="price_asc" <?php echo ($sort === 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
          <option value="price_desc" <?php echo ($sort === 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
          <option value="spots_desc" <?php echo ($sort === 'spots_desc') ? 'selected' : ''; ?>>Most Spots Available</option>
        </select>
      </div>

      <div class="col-12 col-md-8 d-flex gap-2 justify-content-md-end align-items-center">
        <div id="ajax-search-status" class="small text-tiger d-none d-flex align-items-center me-2"></div>
        <button type="submit" class="btn btn-apex-crimson px-4 fw-semibold">
          Apply Filter
        </button>
        <a href="classes.php" id="ajax-reset-filters" class="btn btn-outline-apex">
          Reset
        </a>
      </div>
    </form>
  </section>

  <!-- Class Count Summary -->
  <div class="d-flex justify-content-between align-items-center mb-3 text-secondary small">
    <span>Showing <strong class="text-white" id="classes-visible-count"><?php echo count($classes); ?></strong> programs</span>
    <?php if ($isStaff): ?>
      <span class="text-tiger fw-semibold">Staff CRUD active</span>
    <?php else: ?>
      <span>Live MySQL + AJAX Filtering Active</span>
    <?php endif; ?>
  </div>

  <!-- Classes Listing Cards -->
  <div class="row g-4" id="classes-grid-container">
    <?php if (empty($classes)): ?>
      <div class="col-12">
        <div class="glass-panel p-5 text-center my-4">
          <h2 class="h5 fw-bold font-headline text-uppercase mb-1">No Training Classes Found</h2>
          <p class="text-secondary mb-3">No classes matched your filter or search query.</p>
          <a href="classes.php" class="btn btn-outline-apex btn-sm">Clear Filters</a>
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($classes as $class): ?>
        <?php
          $maxCap = max(1, (int)$class['max_capacity']);
          $booked = (int)$class['booked_count'];
          $remaining = max(0, $maxCap - $booked);
        ?>
        <div class="col-12 col-md-6 col-lg-4">
          <article class="class-card h-100">
            <div class="class-card-img-wrap">
              <img
                src="../public/<?php echo e($class['image_url']); ?>"
                alt="<?php echo e($class['title']); ?> training class">
              <div class="class-card-overlay"></div>
              <span class="class-discipline-badge">
                <?php echo e($class['category_name']); ?>
              </span>
            </div>

            <div class="card-body d-flex flex-column p-4">
              <h3 class="h5 card-title fw-bold font-headline mb-2">
                <?php echo e($class['title']); ?>
              </h3>
              <p class="card-text text-secondary small flex-grow-1 mb-3" style="line-height: 1.6;">
                <?php echo e(mb_strimwidth($class['description'], 0, 140, '...')); ?>
              </p>

              <div class="pt-3 border-top border-secondary border-opacity-25 mb-3">
                <div class="d-flex align-items-center justify-content-between small text-secondary mb-1">
                  <span class="fw-semibold">Lead Coach:</span>
                  <span class="text-white fw-medium"><?php echo e($class['trainer_name']); ?></span>
                </div>
                <div class="d-flex align-items-center justify-content-between small text-secondary mb-1">
                  <span class="fw-semibold">Schedule:</span>
                  <span class="opacity-90 text-end"><?php echo e($class['schedule_info']); ?></span>
                </div>
                <div class="d-flex align-items-center justify-content-between small text-secondary">
                  <span class="fw-semibold">Spots (<?php echo e($filterDate); ?>):</span>
                  <?php if ($remaining <= 0): ?>
                    <span class="badge bg-danger-subtle text-danger border border-danger">FULL (<?php echo $booked; ?>/<?php echo $maxCap; ?>)</span>
                  <?php else: ?>
                    <span class="badge bg-success-subtle text-success border border-success"><?php echo $remaining; ?> / <?php echo $maxCap; ?> spots left</span>
                  <?php endif; ?>
                </div>
              </div>

              <div class="d-flex align-items-center justify-content-between pt-3 border-top border-secondary border-opacity-25 gap-2">
                <div>
                  <span class="small text-secondary d-block font-headline text-uppercase" style="font-size: 0.7rem;">Session Pass</span>
                  <span class="class-price-tag">$<?php echo e(number_format((float)$class['price'], 2)); ?></span>
                </div>
                <div class="d-flex gap-2">
                  <a href="item_details.php?id=<?php echo urlencode((string)$class['id']); ?>" class="btn btn-outline-apex btn-sm px-3 fw-semibold">
                    Details
                  </a>
                  <a href="bookings.php?class_id=<?php echo urlencode((string)$class['id']); ?>&amp;date=<?php echo urlencode($filterDate); ?>" class="btn btn-apex-crimson btn-sm px-3 fw-semibold">
                    Book
                  </a>
                </div>
              </div>

              <?php if ($isStaff): ?>
                <div class="d-flex gap-2 pt-3 mt-3 border-top border-secondary border-opacity-25">
                  <a href="class_form.php?id=<?php echo urlencode((string)$class['id']); ?>" class="btn btn-sm btn-outline-apex flex-grow-1 fw-semibold">
                    Edit
                  </a>
                  <form action="delete_class.php" method="POST" class="d-inline flex-grow-1" onsubmit="return confirm('Are you sure you want to permanently delete this fitness class?');">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="class_id" value="<?php echo (int)$class['id']; ?>">
                    <input type="hidden" name="id" value="<?php echo (int)$class['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-danger w-100 fw-semibold">
                      Delete
                    </button>
                  </form>
                </div>
              <?php endif; ?>
            </div>
          </article>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../public/js/script.js"></script>
</body>
</html>
