<?php
/**
 * TIGER FITNESS Center - Class Item Details Page (Labs 2 & 3)
 * Fetches dynamic entity details via PDO Prepared Statements
 * Integrates Live AJAX Class Capacity Checking & Instant AJAX Booking (Lab 3 Tasks 2 & 3)
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/preferences.php';

$activeTheme = getActiveTheme();
$isAuth = isLoggedIn();
$rawId = $_GET['id'] ?? null;
$classId = filter_var($rawId, FILTER_VALIDATE_INT);
$fitnessClass = null;
$errorMessage = null;
$defaultDate = date('Y-m-d', strtotime('+1 day'));

if ($classId === false || $classId === null) {
    $errorMessage = "Invalid or missing fitness class identifier.";
} else {
    try {
        $stmt = $pdo->prepare("
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
                c.name AS category_name,
                c.description AS category_description
            FROM fitness_classes fc
            INNER JOIN categories c ON fc.category_id = c.id
            WHERE fc.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $classId]);
        $fitnessClass = $stmt->fetch();

        if (!$fitnessClass) {
            $errorMessage = "The requested fitness class (#{$classId}) could not be found in our schedule.";
        } else {
            trackRecentlyViewedClass((int)$fitnessClass['id'], $fitnessClass['title']);
        }
    } catch (PDOException $e) {
        error_log("Failed to query class details: " . $e->getMessage());
        $errorMessage = "An unexpected error occurred while loading class details. Please try again later.";
    }
}

$seo = [
    'title'       => $fitnessClass ? ($fitnessClass['title'] . ' - TIGER FITNESS') : 'Class Details - TIGER FITNESS',
    'description' => $fitnessClass ? mb_strimwidth($fitnessClass['description'], 0, 155, '...') : 'View detailed schedule, coach assignment, and live spot capacity for Tiger Fitness classes.',
    'keywords'    => $fitnessClass ? ($fitnessClass['title'] . ', ' . $fitnessClass['category_name'] . ', ' . $fitnessClass['trainer_name'] . ', Tiger Fitness') : 'Tiger Fitness class details',
];

if ($fitnessClass) {
    $seo['schema'] = [
        '@context' => 'https://schema.org',
        '@type'    => 'Course',
        'name'     => $fitnessClass['title'],
        'description' => $fitnessClass['description'],
        'provider' => [
            '@type' => 'ExerciseGym',
            'name'  => 'TIGER FITNESS Center',
        ],
        'offers'   => [
            '@type'         => 'Offer',
            'price'         => number_format((float)$fitnessClass['price'], 2, '.', ''),
            'priceCurrency' => 'USD',
        ],
    ];
}
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
  <?php if ($errorMessage): ?>
    <div class="row justify-content-center my-5">
      <div class="col-md-8 col-lg-6 text-center">
        <div class="glass-panel p-5">
          <div class="text-warning mb-3">
            <span class="live-beacon" style="width: 16px; height: 16px; background-color: #F59E0B;"></span>
          </div>
          <h1 class="h4 fw-bold font-headline text-uppercase mb-2">Class Not Available</h1>
          <p class="text-secondary mb-4"><?php echo e($errorMessage); ?></p>
          <a href="classes.php" class="btn btn-apex-crimson px-4 fw-semibold">
            &larr; Return to Classes Directory
          </a>
        </div>
      </div>
    </div>
  <?php else: ?>
    <!-- Breadcrumb navigation -->
    <nav aria-label="breadcrumb" class="mb-4">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../public/index.php" class="text-decoration-none">Home</a></li>
        <li class="breadcrumb-item"><a href="classes.php" class="text-decoration-none">Training Classes</a></li>
        <li class="breadcrumb-item active text-white" aria-current="page"><?php echo e($fitnessClass['title']); ?></li>
      </ol>
    </nav>

    <div class="row g-5 align-items-start">
      <!-- Media column -->
      <div class="col-12 col-lg-6">
        <div class="class-card-img-wrap rounded-4 shadow-lg overflow-hidden border border-secondary border-opacity-25" style="height: 460px;">
          <img
            src="../public/<?php echo e($fitnessClass['image_url']); ?>"
            class="w-100 h-100 object-fit-cover"
            alt="<?php echo e($fitnessClass['title']); ?> - <?php echo e($fitnessClass['category_name']); ?> session">
          <div class="class-card-overlay"></div>
          <span class="class-discipline-badge">
            <?php echo e($fitnessClass['category_name']); ?>
          </span>
        </div>
      </div>

      <!-- Details column -->
      <div class="col-12 col-lg-6">
        <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
          <span class="hero-badge">Course Specification</span>
          <span class="badge bg-secondary-subtle text-secondary border small">Cookie Tracked (7d)</span>
          <span class="badge bg-success-subtle text-success border border-success small">Max Capacity: <?php echo (int)$fitnessClass['max_capacity']; ?> athletes</span>
        </div>

        <h1 class="display-6 fw-bold font-headline text-uppercase mb-2"><?php echo e($fitnessClass['title']); ?></h1>

        <div class="d-flex align-items-baseline gap-2 mb-4">
          <span class="display-6 fw-bold text-tiger font-headline">$<?php echo e(number_format((float)$fitnessClass['price'], 2)); ?></span>
          <span class="text-secondary">/ drop-in session pass</span>
        </div>

        <!-- Coach & Schedule Info Box -->
        <div class="glass-panel p-4 mb-4">
          <div class="row g-3">
            <div class="col-sm-6">
              <strong class="d-block text-secondary small text-uppercase fw-bold font-headline">Assigned Lead Coach</strong>
              <span class="fs-6 fw-semibold text-white"><?php echo e($fitnessClass['trainer_name']); ?></span>
            </div>
            <div class="col-sm-6">
              <strong class="d-block text-secondary small text-uppercase fw-bold font-headline">Training Schedule</strong>
              <span class="fs-6 fw-semibold text-white"><?php echo e($fitnessClass['schedule_info']); ?></span>
            </div>
          </div>
        </div>

        <!-- Description -->
        <div class="mb-4">
          <h2 class="h5 fw-bold font-headline text-uppercase mb-2">Program Overview &amp; Biomechanics Syllabus</h2>
          <p class="text-secondary leading-relaxed mb-3" style="line-height: 1.7;">
            <?php echo nl2br(e($fitnessClass['description'])); ?>
          </p>
          <p class="small text-secondary fst-italic">
            Discipline Focus: <?php echo e($fitnessClass['category_description']); ?>
          </p>
        </div>

        <!-- LAB 3 TASK 2: Live AJAX Capacity & Instant Booking Widget -->
        <section aria-label="Live Class Capacity Check" class="glass-panel p-4 mb-4" id="live-capacity-widget">
          <input type="hidden" id="capacity_class_id" value="<?php echo (int)$fitnessClass['id']; ?>">
          <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <h2 class="h6 fw-bold font-headline text-uppercase mb-0">Live Schedule Capacity (AJAX)</h2>
            <button type="button" id="refresh-capacity-btn" class="btn btn-sm btn-outline-apex">
              &#8635; Check Live Spots
            </button>
          </div>

          <div class="row g-2 align-items-end mb-3">
            <div class="col-sm-5">
              <label for="capacity_date" class="form-label small text-secondary fw-semibold">Select Date</label>
              <input
                type="date"
                class="form-control form-control-sm"
                id="capacity_date"
                min="<?php echo e(date('Y-m-d')); ?>"
                max="<?php echo e(date('Y-m-d', strtotime('+60 days'))); ?>"
                value="<?php echo e($defaultDate); ?>">
            </div>
            <div class="col-sm-7">
              <div class="d-flex flex-wrap gap-2" id="capacity-date-pills"></div>
            </div>
          </div>

          <div class="p-3 rounded-3 border border-secondary border-opacity-25 mb-3" id="live-capacity-output">
            <div class="small text-secondary">Checking real-time availability...</div>
          </div>

          <div id="ajax-booking-feedback" class="mb-3"></div>

          <div class="d-flex flex-wrap gap-2">
            <?php if ($isAuth): ?>
              <button
                type="button"
                id="ajax-quick-book-btn"
                class="btn btn-apex-crimson btn-lg px-4 fw-semibold shadow-sm flex-grow-1">
                Book Session via AJAX
              </button>
            <?php else: ?>
              <a href="../auth/login.php" class="btn btn-apex-crimson btn-lg px-4 fw-semibold shadow-sm flex-grow-1">
                Log In to Book Session
              </a>
            <?php endif; ?>
            <a href="bookings.php?class_id=<?php echo (int)$fitnessClass['id']; ?>&amp;date=<?php echo e($defaultDate); ?>" class="btn btn-outline-apex btn-lg px-3">
              Full Booking Console
            </a>
          </div>
        </section>

        <div class="d-flex flex-wrap gap-2">
          <a href="classes.php" class="btn btn-outline-apex px-4">
            &larr; All Classes
          </a>
        </div>

        <?php if (hasRole(['admin', 'trainer'])): ?>
          <div class="glass-panel p-3 mt-4 border-start border-danger border-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
              <div>
                <strong class="text-white d-block font-headline text-uppercase small">Staff Clearance Active</strong>
                <span class="small text-secondary">Authorized to modify or permanently delete this class.</span>
              </div>
              <div class="d-flex gap-2">
                <a href="class_form.php?id=<?php echo urlencode((string)$fitnessClass['id']); ?>" class="btn btn-outline-apex btn-sm px-3 fw-semibold">
                  Edit Class
                </a>
                <form action="delete_class.php" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this class?');">
                  <?php echo csrfField(); ?>
                  <input type="hidden" name="class_id" value="<?php echo (int)$fitnessClass['id']; ?>">
                  <input type="hidden" name="id" value="<?php echo (int)$fitnessClass['id']; ?>">
                  <button type="submit" class="btn btn-danger btn-sm px-3 fw-semibold">
                    Delete Class
                  </button>
                </form>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../public/js/script.js"></script>
</body>
</html>
