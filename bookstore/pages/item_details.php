<?php
/**
 * Apex Fitness & Performance Center - Class Item Details Page (Lab 2 Tasks 2 & 5.2)
 * Fetches dynamic entity details from MySQL using PDO Prepared Statements
 * Stitch Design System Enhancement (Project ID: 6697286312568266889)
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/preferences.php';

$activeTheme = getActiveTheme();
$rawId = $_GET['id'] ?? null;
$classId = filter_var($rawId, FILTER_VALIDATE_INT);
$fitnessClass = null;
$errorMessage = null;

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
            // Task 5.2: Track recently viewed class in persistent client cookie
            trackRecentlyViewedClass((int)$fitnessClass['id'], $fitnessClass['title']);
        }
    } catch (PDOException $e) {
        error_log("Failed to query class details: " . $e->getMessage());
        $errorMessage = "An unexpected error occurred while loading class details. Please try again later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $fitnessClass ? htmlspecialchars($fitnessClass['title']) . ' - TIGER FITNESS' : 'Class Details - TIGER FITNESS'; ?></title>
  <link rel="icon" type="image/svg+xml" href="../public/images/favicon.svg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../public/css/style.css">
</head>
<body class="<?php echo ($activeTheme === 'dark') ? 'theme-dark' : ''; ?>" data-bs-theme="<?php echo $activeTheme; ?>">

<?php require_once __DIR__ . '/../components/header.php'; ?>

<main class="container py-5">
  <?php if ($errorMessage): ?>
    <div class="row justify-content-center my-5">
      <div class="col-md-8 col-lg-6 text-center">
        <div class="glass-panel p-5">
          <div class="text-warning mb-3">
            <span class="live-beacon" style="width: 16px; height: 16px; background-color: #F59E0B;"></span>
          </div>
          <h4 class="fw-bold font-headline text-uppercase mb-2">Class Not Available</h4>
          <p class="text-secondary mb-4"><?php echo htmlspecialchars($errorMessage); ?></p>
          <div>
            <a href="classes.php" class="btn btn-apex-crimson px-4 fw-semibold">
              &larr; Return to Classes Directory
            </a>
          </div>
        </div>
      </div>
    </div>
  <?php else: ?>
    <!-- Breadcrumb navigation -->
    <nav aria-label="breadcrumb" class="mb-4">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="../public/index.php" class="text-decoration-none">Home</a></li>
        <li class="breadcrumb-item"><a href="classes.php" class="text-decoration-none">Training Classes</a></li>
        <li class="breadcrumb-item active text-white" aria-current="page"><?php echo htmlspecialchars($fitnessClass['title']); ?></li>
      </ol>
    </nav>

    <div class="row g-5 align-items-center">
      <!-- Media column -->
      <div class="col-12 col-lg-6">
        <div class="class-card-img-wrap rounded-4 shadow-lg overflow-hidden border border-secondary border-opacity-25" style="height: 480px;">
          <img
            src="../public/<?php echo htmlspecialchars($fitnessClass['image_url']); ?>"
            class="w-100 h-100 object-fit-cover"
            alt="<?php echo htmlspecialchars($fitnessClass['title']); ?>">
          <div class="class-card-overlay"></div>
          <span class="class-discipline-badge">
            <?php echo htmlspecialchars($fitnessClass['category_name']); ?>
          </span>
        </div>
      </div>

      <!-- Details column -->
      <div class="col-12 col-lg-6">
        <div class="d-flex align-items-center gap-2 mb-3">
          <span class="hero-badge">Course Specification</span>
          <span class="badge bg-secondary-subtle text-secondary border small">Cookie Tracked (7d)</span>
        </div>

        <h1 class="display-6 fw-bold font-headline text-uppercase mb-2"><?php echo htmlspecialchars($fitnessClass['title']); ?></h1>
        
        <div class="d-flex align-items-baseline gap-2 mb-4">
          <span class="display-6 fw-bold text-danger font-headline">$<?php echo number_format((float)$fitnessClass['price'], 2); ?></span>
          <span class="text-secondary">/ drop-in session pass</span>
        </div>

        <!-- Coach & Schedule Info Box (Glass Panel) -->
        <div class="glass-panel p-4 mb-4">
          <div class="row g-3">
            <div class="col-sm-6">
              <strong class="d-block text-secondary small text-uppercase fw-bold font-headline">Assigned Lead Coach</strong>
              <span class="fs-6 fw-semibold text-white"><?php echo htmlspecialchars($fitnessClass['trainer_name']); ?></span>
            </div>
            <div class="col-sm-6">
              <strong class="d-block text-secondary small text-uppercase fw-bold font-headline">Training Schedule</strong>
              <span class="fs-6 fw-semibold text-white"><?php echo htmlspecialchars($fitnessClass['schedule_info']); ?></span>
            </div>
          </div>
        </div>

        <!-- Description -->
        <div class="mb-4">
          <h5 class="fw-bold font-headline text-uppercase mb-2">Program Overview & Biomechanics Syllabus</h5>
          <p class="text-secondary leading-relaxed mb-3" style="line-height: 1.7;">
            <?php echo nl2br(htmlspecialchars($fitnessClass['description'])); ?>
          </p>
          <p class="small text-secondary fst-italic">
            Discipline Focus: <?php echo htmlspecialchars($fitnessClass['category_description']); ?>
          </p>
        </div>

        <!-- Action CTA -->
        <div class="d-flex flex-wrap gap-3 pt-2">
          <button
            type="button"
            class="btn btn-apex-crimson btn-lg px-4 py-2.5 fw-semibold shadow-sm"
            onclick="showMessage()">
            Book This Session
          </button>
          <a href="classes.php" class="btn btn-outline-apex btn-lg px-4 py-2.5">
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
                <a href="class_form.php?id=<?php echo urlencode($fitnessClass['id']); ?>" class="btn btn-outline-apex btn-sm px-3 fw-semibold">
                  Edit Class
                </a>
                <form action="delete_class.php" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this class?');">
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
<script>
function showMessage() {
  alert("Booking reservation requested for <?php echo addslashes($fitnessClass['title'] ?? 'this session'); ?>! Our front desk staff will confirm your slot via email.");
}
</script>
</body>
</html>
