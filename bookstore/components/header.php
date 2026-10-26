<?php
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/preferences.php';

$isAuth = isLoggedIn();
$currentUser = getCurrentUser();
$isStaff = hasRole(['admin', 'trainer']);
$activeTheme = getActiveTheme();

// Role badge CSS mapping based on Stitch VIP tokens
$roleClass = 'vip-role-member';
if ($isAuth && isset($currentUser['role'])) {
    if ($currentUser['role'] === 'admin') {
        $roleClass = 'vip-role-admin';
    } elseif ($currentUser['role'] === 'trainer') {
        $roleClass = 'vip-role-trainer';
    }
}

// Calculate toggle destination
$currentUri = $_SERVER['REQUEST_URI'] ?? '../public/index.php';
$targetTheme = ($activeTheme === 'dark') ? 'light' : 'dark';
$themeToggleUrl = "../pages/set_theme.php?theme=" . urlencode($targetTheme) . "&return=" . urlencode($currentUri);
?>
<script>
  // Instant client-side theme application to prevent FOUC
  (function() {
    var theme = "<?php echo $activeTheme; ?>";
    document.documentElement.setAttribute('data-bs-theme', theme);
    if (document.body) {
      if (theme === 'dark') {
        document.body.classList.add('theme-dark');
      } else {
        document.body.classList.remove('theme-dark');
      }
    }
  })();
</script>
<header>
  <nav class="navbar navbar-expand-lg glass-navbar sticky-top shadow-sm py-3">
    <div class="container">
      <a class="navbar-brand fw-bold text-uppercase d-flex align-items-center gap-2" href="../public/index.php">
        <span class="badge rounded-pill px-2 py-1" style="background: var(--apex-crimson); font-size: 0.72rem; letter-spacing: 0.1em;">FIT</span>
        <span class="font-headline" style="letter-spacing: 0.06em; font-size: 1.15rem;">Apex Fitness Club</span>
        <span class="live-beacon ms-1 d-none d-sm-inline-block" title="Apex Core Systems Online"></span>
      </a>
      <button
        class="navbar-toggler border-0 shadow-none"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#mainNavbar"
        aria-controls="mainNavbar"
        aria-expanded="false"
        aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="mainNavbar">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center">
          <li class="nav-item">
            <a class="nav-link fw-semibold px-3" href="../public/index.php">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link fw-semibold px-3" href="../pages/classes.php">Training Classes</a>
          </li>
          <li class="nav-item">
            <a class="nav-link fw-semibold px-3" href="../public/index.php#trainers">Trainers</a>
          </li>
          <?php if ($isAuth): ?>
            <li class="nav-item">
              <a class="nav-link fw-semibold px-3 text-warning" href="../pages/dashboard.php">Dashboard</a>
            </li>
          <?php endif; ?>
          <?php if ($isStaff): ?>
            <li class="nav-item">
              <a class="btn btn-sm btn-outline-apex ms-lg-2 my-2 my-lg-0 px-3 py-1" href="../pages/class_form.php">
                + Add Class
              </a>
            </li>
          <?php endif; ?>
        </ul>

        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-2">
          <!-- Theme Toggle (Task 5.2 Cookie: apex_theme) -->
          <li class="nav-item">
            <a href="<?php echo htmlspecialchars($themeToggleUrl); ?>" 
               class="btn btn-sm btn-outline-apex d-flex align-items-center gap-2 px-3 py-1"
               title="Switch to <?php echo ucfirst($targetTheme); ?> Gym Mode (Cookie-persisted for 30 days)">
              <?php if ($activeTheme === 'dark'): ?>
                <span class="text-warning">&#9728;</span> <span class="small font-headline">LIGHT</span>
              <?php else: ?>
                <span class="text-info">&#9790;</span> <span class="small font-headline">DARK MODE</span>
              <?php endif; ?>
            </a>
          </li>

          <?php if ($isAuth): ?>
            <li class="nav-item d-flex align-items-center gap-2 ms-lg-2">
              <span class="small d-none d-xl-inline opacity-75">Athlete:</span>
              <span class="vip-role-badge <?php echo $roleClass; ?> shadow-sm">
                <span><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                <span class="opacity-75">[<?php echo htmlspecialchars(strtoupper($currentUser['role'])); ?>]</span>
              </span>
            </li>
            <li class="nav-item ms-lg-1">
              <a class="btn btn-outline-apex btn-sm px-3" href="../auth/logout.php">
                Log Out
              </a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="btn btn-outline-apex btn-sm px-3" href="../auth/login.php">
                Log In
              </a>
            </li>
            <li class="nav-item">
              <a class="btn btn-danger btn-sm px-3 shadow-sm" href="../auth/register.php">
                Register
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>
</header>
