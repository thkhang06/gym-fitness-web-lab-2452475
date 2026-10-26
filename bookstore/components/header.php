<?php
require_once __DIR__ . '/../config/session.php';
$isAuth = isLoggedIn();
$currentUser = getCurrentUser();
$isStaff = hasRole(['admin', 'trainer']);
?>
<header>
  <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold text-uppercase d-flex align-items-center gap-2" href="../public/index.php">
        <span class="badge bg-danger rounded-pill px-2 py-1">FIT</span>
        <span>Apex Fitness Club</span>
      </a>
      <button
        class="navbar-toggler"
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
            <a class="nav-link" href="../public/index.php">Home</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="../pages/classes.php">Training Classes</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="../public/index.php#trainers">Trainers</a>
          </li>
          <?php if ($isAuth): ?>
            <li class="nav-item">
              <a class="nav-link text-warning fw-semibold" href="../pages/dashboard.php">Dashboard</a>
            </li>
          <?php endif; ?>
          <?php if ($isStaff): ?>
            <li class="nav-item">
              <a class="btn btn-sm btn-outline-danger ms-lg-2 my-2 my-lg-0 fw-semibold px-2" href="../pages/class_form.php">
                + Add Class
              </a>
            </li>
          <?php endif; ?>
        </ul>

        <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
          <?php if ($isAuth): ?>
            <li class="nav-item me-lg-3 my-2 my-lg-0 text-light small d-flex align-items-center gap-2">
              <span>Hi, <strong class="text-white"><?php echo htmlspecialchars($currentUser['full_name']); ?></strong></span>
              <span class="badge bg-danger text-uppercase px-2 py-1 small">
                <?php echo htmlspecialchars($currentUser['role']); ?>
              </span>
            </li>
            <li class="nav-item">
              <a class="btn btn-outline-danger btn-sm px-3 fw-semibold" href="../auth/logout.php">
                Log Out
              </a>
            </li>
          <?php else: ?>
            <li class="nav-item me-lg-2">
              <a class="btn btn-outline-light btn-sm px-3" href="../auth/login.php">
                Log In
              </a>
            </li>
            <li class="nav-item mt-2 mt-lg-0">
              <a class="btn btn-danger btn-sm px-3 fw-semibold shadow-sm" href="../auth/register.php">
                Register
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>
</header>
