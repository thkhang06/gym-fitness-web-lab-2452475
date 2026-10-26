<?php
/**
 * Apex Fitness & Performance Center - Homepage (Lab 2 Tasks 2 & 5.2)
 * High-Octane UI Overhaul Synthesized with Google Stitch MCP
 * Dynamic MySQL Integration via PDO Prepared Statements
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/preferences.php';

$activeTheme = getActiveTheme();
$recentClass = getRecentlyViewedClass();
$favCategory = getFavoriteCategory();

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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
</head>
<body class="<?php echo ($activeTheme === 'dark') ? 'theme-dark' : ''; ?>" data-bs-theme="<?php echo $activeTheme; ?>">

<?php require_once __DIR__ . '/../components/header.php'; ?>

<!-- High-Octane Hero Section -->
<section class="hero-section text-white">
  <div class="container text-center py-4">
    <div class="hero-badge mb-3 shadow-sm">
      <span class="live-beacon"></span>
      <span>Apex High-Performance Gym</span>
    </div>
    
    <h1 class="display-4 fw-bold mb-3 text-uppercase font-headline" style="letter-spacing: 0.05em; max-width: 980px; margin: 0 auto;">
      Forge Your Ultimate Strength & Endurance
    </h1>
    
    <p class="lead opacity-75 mb-4 mx-auto" style="max-width: 720px; font-size: 1.15rem;">
      World-class barbell biomechanics, high-intensity functional conditioning, and dynamic mobility recovery protocols tailored for dedicated athletes.
    </p>

    <div class="d-flex justify-content-center gap-3 mb-5 flex-wrap">
      <a href="#items" class="btn btn-apex-crimson btn-lg px-4 py-2 fw-semibold">
        Explore Classes &darr;
      </a>
      <a href="#trainers" class="btn btn-outline-apex btn-lg px-4 py-2 fw-semibold">
        Meet Coaches
      </a>
    </div>

    <!-- Performance Metrics Bar (Stitch Telemetry Widget) -->
    <div class="row g-3 justify-content-center max-w-4xl mx-auto pt-2" style="max-width: 900px;">
      <div class="col-12 col-md-4">
        <div class="stats-card text-center h-100">
          <div class="stats-value text-danger mb-1">1,200+</div>
          <span class="small opacity-75 font-headline text-uppercase">Active Athletes</span>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="stats-card text-center h-100">
          <div class="stats-value text-warning mb-1">15+</div>
          <span class="small opacity-75 font-headline text-uppercase">Elite Master Coaches</span>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="stats-card text-center h-100">
          <div class="stats-value text-success mb-1">24+</div>
          <span class="small opacity-75 font-headline text-uppercase">Weekly Programs</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Item Catalog (Dynamic MySQL Database Integration) -->
<section class="py-5" id="items">
  <div class="container">
    <!-- Personalized 'Welcome Back' Cookie Banner (Task 5.2) -->
    <?php if ($recentClass || $favCategory): ?>
      <div class="glass-panel p-3 mb-5 border-start border-4 border-danger shadow-sm d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
          <div class="p-2 rounded-circle bg-danger bg-opacity-10 text-danger d-flex align-items-center justify-content-center">
            <span class="live-beacon"></span>
          </div>
          <div>
            <div class="d-flex align-items-center gap-2">
              <span class="badge bg-danger rounded-pill px-2 py-0.5 small">Welcome Back</span>
              <strong class="font-headline text-uppercase small">Personalized Preference Active</strong>
            </div>
            <div class="small text-secondary mt-0.5">
              <?php if ($recentClass): ?>
                Recently viewed session: <strong class="text-white"><?php echo htmlspecialchars($recentClass['title']); ?></strong>
              <?php elseif ($favCategory): ?>
                Your preferred training discipline: <strong class="text-white"><?php echo htmlspecialchars($favCategory['name']); ?></strong>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <div>
          <?php if ($recentClass): ?>
            <a href="../pages/item_details.php?id=<?php echo urlencode($recentClass['id']); ?>" class="btn btn-sm btn-apex-crimson px-3 py-1.5">
              Resume Class Details &rarr;
            </a>
          <?php elseif ($favCategory): ?>
            <a href="../pages/classes.php?category_id=<?php echo urlencode($favCategory['id']); ?>" class="btn btn-sm btn-outline-apex px-3 py-1.5">
              Filter By Discipline &rarr;
            </a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="text-center mb-5">
      <span class="hero-badge mb-2">Our Offerings</span>
      <h2 class="display-5 fw-bold font-headline mt-1 text-uppercase">Featured Training Programs</h2>
      <p class="text-secondary mx-auto" style="max-width: 600px;">
        High-intensity coaching sessions engineered for neuromuscular adaptation, raw strength, and conditioning.
      </p>
    </div>

    <?php if (empty($classes)): ?>
      <div class="glass-panel text-center py-5 shadow-sm">
        <h5 class="fw-bold">No fitness classes currently available</h5>
        <p class="mb-0 text-secondary">Please check back soon or verify that the database seed data has been imported.</p>
      </div>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($classes as $class): ?>
          <div class="col-12 col-md-6 col-lg-4">
            <div class="class-card h-100">
              <div class="class-card-img-wrap">
                <img
                  src="<?php echo htmlspecialchars($class['image_url']); ?>"
                  alt="<?php echo htmlspecialchars($class['title']); ?>">
                <div class="class-card-overlay"></div>
                <span class="class-discipline-badge">
                  <?php echo htmlspecialchars($class['category_name']); ?>
                </span>
              </div>
              <div class="card-body d-flex flex-column p-4">
                <h5 class="card-title fw-bold font-headline mb-2" style="font-size: 1.25rem;">
                  <?php echo htmlspecialchars($class['title']); ?>
                </h5>
                <p class="card-text text-secondary small flex-grow-1 mb-3" style="line-height: 1.6;">
                  <?php echo htmlspecialchars($class['description']); ?>
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

                <div class="d-flex align-items-center justify-content-between pt-3 border-top border-secondary border-opacity-25">
                  <div>
                    <span class="small text-secondary d-block font-headline text-uppercase" style="font-size: 0.7rem;">Session Pass</span>
                    <span class="class-price-tag">$<?php echo number_format((float)$class['price'], 2); ?></span>
                  </div>
                  <a href="../pages/item_details.php?id=<?php echo urlencode($class['id']); ?>"
                     class="btn btn-sm btn-outline-apex px-3 py-2 fw-semibold">
                    View Details &rarr;
                  </a>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="text-center mt-5">
        <a href="../pages/classes.php" class="btn btn-outline-apex btn-lg px-5 py-3 font-headline fw-semibold text-uppercase shadow-sm">
          Browse Complete Class Directory & Filter &rarr;
        </a>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Additional Gym Highlights Section -->
<section class="py-5 border-top border-secondary border-opacity-25" id="trainers" style="background: rgba(17, 24, 39, 0.4);">
  <div class="container text-center py-3">
    <div class="row g-4 justify-content-center">
      <div class="col-12 col-md-4">
        <div class="glass-panel p-4 h-100">
          <div class="display-6 font-headline fw-bold text-danger mb-2">5,000+ sq ft</div>
          <h6 class="font-headline text-uppercase fw-bold text-white mb-1">Elite Facility</h6>
          <p class="text-secondary small mb-0">Full competition powerlifting platforms, turf sprint lanes & Eleiko calibrated plates.</p>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="glass-panel p-4 h-100">
          <div class="display-6 font-headline fw-bold text-warning mb-2">100% Certified</div>
          <h6 class="font-headline text-uppercase fw-bold text-white mb-1">Expert Mentorship</h6>
          <p class="text-secondary small mb-0">CSCS, USAW, and Precision Nutrition accredited coaches guiding every movement.</p>
        </div>
      </div>
      <div class="col-12 col-md-4">
        <div class="glass-panel p-4 h-100">
          <div class="display-6 font-headline fw-bold text-success mb-2">Zero Lock-In</div>
          <h6 class="font-headline text-uppercase fw-bold text-white mb-1">Pure Flexibility</h6>
          <p class="text-secondary small mb-0">Individual drop-in passes and open training packages without restrictive contracts.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
