<?php
/**
 * Apex Fitness & Performance Center - Homepage (Lab 2 Task 2)
 * Demonstrates dynamic MySQL integration via PDO prepared querying
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

try {
    $stmt = $pdo->query("
        SELECT 
            fc.id,
            fc.title,
            fc.description,
            fc.trainer_name,
            fc.schedule_info,
            fc.price,
            fc.image_url,
            c.name AS category_name
        FROM fitness_classes fc
        INNER JOIN categories c ON fc.category_id = c.id
        ORDER BY fc.id ASC
    ");
    $classes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch fitness classes: " . $e->getMessage());
    $classes = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Apex Fitness & Performance Center - Transform Your Potential</title>
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
    rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php
require_once __DIR__ . '/../components/header.php';
?>

<!-- Hero Section -->
<section class="hero-section text-white py-5">
  <div class="container text-center py-4">
    <span class="badge bg-danger text-uppercase px-3 py-2 mb-3 tracking-wider">Apex High-Performance Gym</span>
    <h1 class="display-4 fw-bold mb-3">Forge Your Ultimate Strength & Endurance</h1>
    <p class="lead text-light opacity-75 mb-4">
      World-class barbell coaching, high-intensity functional conditioning, and dynamic mobility programs tailored for dedicated athletes and fitness enthusiasts.
    </p>
    <div class="d-flex justify-content-center gap-3">
      <a href="#items" class="btn btn-danger btn-lg px-4 fw-semibold shadow-sm">
        Explore Classes
      </a>
      <a href="#trainers" class="btn btn-outline-light btn-lg px-4 fw-semibold">
        Meet Coaches
      </a>
    </div>
  </div>
</section>

<!-- Item Catalog (Dynamic MySQL Database Integration) -->
<section class="py-5" id="items">
  <div class="container">
    <div class="text-center mb-5">
      <span class="text-danger fw-semibold text-uppercase small">Our Offerings</span>
      <h2 class="display-6 fw-bold mt-1">Featured Training Programs</h2>
      <p class="text-muted">Dynamic sessions led by certified strength coaches and elite athletes.</p>
    </div>

    <?php if (empty($classes)): ?>
      <div class="alert alert-info text-center py-4 shadow-sm">
        <h5>No fitness classes currently available</h5>
        <p class="mb-0 text-muted">Please check back soon or verify that the database seed data has been imported.</p>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($classes as $class): ?>
          <div class="col-12 col-md-6 col-lg-4">
            <div class="card h-100 shadow-sm border-0 class-card">
              <div class="position-relative">
                <img
                  src="<?php echo htmlspecialchars($class['image_url']); ?>"
                  class="card-img-top"
                  alt="<?php echo htmlspecialchars($class['title']); ?>">
                <span class="badge bg-danger position-absolute top-0 end-0 m-3 px-3 py-2 shadow-sm">
                  <?php echo htmlspecialchars($class['category_name']); ?>
                </span>
              </div>
              <div class="card-body d-flex flex-column p-4">
                <h5 class="card-title fw-bold text-dark mb-2">
                  <?php echo htmlspecialchars($class['title']); ?>
                </h5>
                <p class="card-text text-muted small flex-grow-1">
                  <?php echo htmlspecialchars($class['description']); ?>
                </p>
                
                <div class="class-meta my-3 pt-3 border-top">
                  <div class="d-flex align-items-center text-secondary small mb-2">
                    <span class="fw-semibold text-dark me-2">Lead Coach:</span>
                    <span><?php echo htmlspecialchars($class['trainer_name']); ?></span>
                  </div>
                  <div class="d-flex align-items-center text-secondary small">
                    <span class="fw-semibold text-dark me-2">Schedule:</span>
                    <span><?php echo htmlspecialchars($class['schedule_info']); ?></span>
                  </div>
                </div>

                <div class="d-flex align-items-center justify-content-between pt-3 border-top">
                  <div>
                    <span class="text-muted small d-block">Session Pass</span>
                    <span class="fs-5 fw-bold text-danger">$<?php echo number_format((float)$class['price'], 2); ?></span>
                  </div>
                  <a href="../pages/item_details.php?id=<?php echo urlencode($class['id']); ?>"
                     class="btn btn-dark btn-sm px-3 py-2 fw-semibold">
                    View Details &rarr;
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Additional Gym Highlights Section -->
<section class="py-5 bg-white border-top" id="trainers">
  <div class="container text-center">
    <div class="row g-4 align-items-center justify-content-center">
      <div class="col-md-4">
        <div class="p-3">
          <div class="fs-2 text-danger fw-bold mb-1">5,000+ sq ft</div>
          <p class="text-muted mb-0">High-End Olympic & Powerlifting Facility</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="p-3">
          <div class="fs-2 text-danger fw-bold mb-1">100% Certified</div>
          <p class="text-muted mb-0">CSCS & USAW Accredited Coaching Staff</p>
        </div>
      </div>
      <div class="col-md-4">
        <div class="p-3">
          <div class="fs-2 text-danger fw-bold mb-1">Flexible Passes</div>
          <p class="text-muted mb-0">No Long-Term Lock-in Contracts Required</p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php
require_once __DIR__ . '/../components/footer.php';
?>

<script
  src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js">
</script>
<script src="js/script.js"></script>
</body>
</html>
