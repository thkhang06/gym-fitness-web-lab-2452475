<?php
/**
 * Apex Fitness & Performance Center - Class Item Details Page (Lab 2 Task 2)
 * Fetches dynamic entity details from MySQL using PDO Prepared Statements
 */
require_once __DIR__ . '/../config/database.php';

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
  <title><?php echo $fitnessClass ? htmlspecialchars($fitnessClass['title']) . ' - Apex Fitness' : 'Class Details - Apex Fitness'; ?></title>
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet">
  <link rel="stylesheet" href="../public/css/style.css">
</head>
<body>

<?php
require_once __DIR__ . '/../components/header.php';
?>

<main class="container py-5">
  <?php if ($errorMessage): ?>
    <div class="row justify-content-center my-5">
      <div class="col-md-8 col-lg-6 text-center">
        <div class="card border-0 shadow-sm p-4">
          <div class="text-warning mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16">
              <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
              <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
            </svg>
          </div>
          <h4 class="fw-bold mb-2">Class Not Available</h4>
          <p class="text-muted mb-4"><?php echo htmlspecialchars($errorMessage); ?></p>
          <div>
            <a href="../public/index.php#items" class="btn btn-danger px-4 fw-semibold">
              &larr; Return to Training Schedule
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
        <li class="breadcrumb-item"><a href="../public/index.php#items" class="text-decoration-none">Training Classes</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($fitnessClass['title']); ?></li>
      </ol>
    </nav>

    <div class="row g-5 align-items-center">
      <!-- Media column -->
      <div class="col-12 col-lg-6">
        <div class="class-image-wrapper shadow rounded overflow-hidden">
          <img
            src="../public/<?php echo htmlspecialchars($fitnessClass['image_url']); ?>"
            class="img-fluid w-100 object-fit-cover"
            style="min-height: 420px; max-height: 520px;"
            alt="<?php echo htmlspecialchars($fitnessClass['title']); ?>">
        </div>
      </div>

      <!-- Details column -->
      <div class="col-12 col-lg-6">
        <div class="mb-3">
          <span class="badge bg-danger px-3 py-2 fs-6 shadow-sm">
            <?php echo htmlspecialchars($fitnessClass['category_name']); ?>
          </span>
        </div>

        <h1 class="display-6 fw-bold mb-3"><?php echo htmlspecialchars($fitnessClass['title']); ?></h1>
        
        <div class="d-flex align-items-baseline gap-2 mb-4">
          <span class="display-6 fw-bold text-danger">$<?php echo number_format((float)$fitnessClass['price'], 2); ?></span>
          <span class="text-muted">/ drop-in session pass</span>
        </div>

        <!-- Coach & Schedule Info Box -->
        <div class="card bg-light border-0 p-4 mb-4 rounded-3">
          <div class="row g-3">
            <div class="col-sm-6">
              <strong class="d-block text-secondary small text-uppercase fw-bold">Assigned Head Coach</strong>
              <span class="fs-6 fw-semibold text-dark"><?php echo htmlspecialchars($fitnessClass['trainer_name']); ?></span>
            </div>
            <div class="col-sm-6">
              <strong class="d-block text-secondary small text-uppercase fw-bold">Training Schedule</strong>
              <span class="fs-6 fw-semibold text-dark"><?php echo htmlspecialchars($fitnessClass['schedule_info']); ?></span>
            </div>
          </div>
        </div>

        <!-- Description -->
        <div class="mb-4">
          <h5 class="fw-bold mb-2">Program Overview & Syllabus</h5>
          <p class="text-secondary leading-relaxed mb-3">
            <?php echo nl2br(htmlspecialchars($fitnessClass['description'])); ?>
          </p>
          <p class="small text-muted fst-italic">
            Category Focus: <?php echo htmlspecialchars($fitnessClass['category_description']); ?>
          </p>
        </div>

        <!-- Action CTA -->
        <div class="d-flex flex-wrap gap-3 pt-2">
          <button
            type="button"
            class="btn btn-danger btn-lg px-4 fw-semibold shadow-sm"
            onclick="showMessage()">
            Book This Session
          </button>
          <a href="../public/index.php#items" class="btn btn-outline-dark btn-lg px-4">
            &larr; All Classes
          </a>
        </div>
      </div>
    </div>
  <?php endif; ?>
</main>

<?php
require_once __DIR__ . '/../components/footer.php';
?>

<script
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js">
</script>
<script src="../public/js/script.js"></script>
</body>
</html>
