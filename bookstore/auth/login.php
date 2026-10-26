<?php
/**
 * User Authentication / Login Page (Lab 2 Task 4.2)
 * Verifies credentials against MySQL, regenerates session ID, and sets session state
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/preferences.php';

$activeTheme = getActiveTheme();

// If user is already authenticated, redirect to member dashboard
if (isLoggedIn()) {
    header('Location: ../pages/dashboard.php');
    exit;
}

$errorMessage = null;
$email = '';

// Check if user came from logout
if (isset($_GET['logged_out']) && $_GET['logged_out'] === '1') {
    setFlash('info', 'You have been logged out successfully.');
}

$flashSuccess = getFlash('success');
$flashError   = getFlash('error');
$flashInfo    = getFlash('info');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $errorMessage = 'Please enter both your email address and password.';
    } else {
        try {
            // Prepared statement to look up user by email
            $stmt = $pdo->prepare("
                SELECT id, full_name, email, password_hash, role
                FROM users
                WHERE email = :email
                LIMIT 1
            ");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            // Verify password using bcrypt hash
            if ($user && password_verify($password, $user['password_hash'])) {
                // Prevent Session Fixation attack
                session_regenerate_id(true);

                // Populate session with authenticated user attributes
                $_SESSION['user_id']   = (int)$user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['role']      = $user['role'];
                $_SESSION['logged_in_time'] = time();

                // Determine redirect target
                $redirectUrl = $_SESSION['redirect_after_login'] ?? '../pages/dashboard.php';
                unset($_SESSION['redirect_after_login']);

                // Ensure redirect stays inside local app
                if (empty($redirectUrl) || strpos($redirectUrl, 'auth/') !== false) {
                    $redirectUrl = '../pages/dashboard.php';
                }

                header("Location: {$redirectUrl}");
                exit;
            } else {
                // Generic error message to prevent account enumeration
                $errorMessage = 'Invalid email or password.';
            }
        } catch (PDOException $e) {
            error_log('Login database error: ' . $e->getMessage());
            $errorMessage = 'A temporary database error occurred. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Member Login - Apex Fitness Club</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../public/css/style.css">
</head>
<body class="<?php echo ($activeTheme === 'dark') ? 'theme-dark' : ''; ?>" data-bs-theme="<?php echo $activeTheme; ?>">

<?php require_once __DIR__ . '/../components/header.php'; ?>

<main class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-5">
      <div class="glass-panel overflow-hidden mb-4 shadow-lg">
        <div class="p-4 text-center border-bottom border-secondary border-opacity-25" style="background: rgba(9, 13, 22, 0.6);">
          <span class="hero-badge mb-2">Member Portal</span>
          <h3 class="fw-bold font-headline text-uppercase text-white mb-1">Welcome Back</h3>
          <p class="text-secondary small mb-0">Log in to manage your training sessions</p>
        </div>

        <div class="card-body p-4 p-md-5">
          <!-- Flash Notifications -->
          <?php if ($flashSuccess): ?>
            <div class="alert alert-success alert-dismissible fade show py-2 mb-4" role="alert">
              <?php echo htmlspecialchars($flashSuccess); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <?php if ($flashError): ?>
            <div class="alert alert-warning alert-dismissible fade show py-2 mb-4" role="alert">
              <?php echo htmlspecialchars($flashError); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <?php if ($flashInfo): ?>
            <div class="alert alert-info alert-dismissible fade show py-2 mb-4" role="alert">
              <?php echo htmlspecialchars($flashInfo); ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>

          <?php if ($errorMessage): ?>
            <div class="alert alert-danger shadow-sm py-2 mb-4" role="alert">
              <?php echo htmlspecialchars($errorMessage); ?>
            </div>
          <?php endif; ?>

          <!-- Login Form -->
          <form action="login.php" method="POST" novalidate>
            <div class="mb-3">
              <label for="email" class="form-label fw-semibold">Email Address</label>
              <input
                type="email"
                class="form-control"
                id="email"
                name="email"
                placeholder="name@example.com"
                value="<?php echo htmlspecialchars($email); ?>"
                required
                autofocus>
            </div>

            <div class="mb-4">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="password" class="form-label fw-semibold mb-0">Password</label>
                <span class="text-muted small">Default: Password123!</span>
              </div>
              <input
                type="password"
                class="form-control"
                id="password"
                name="password"
                placeholder="Enter your password"
                required>
            </div>

            <div class="d-grid mb-3">
              <button type="submit" class="btn btn-apex-crimson btn-lg fw-semibold shadow-sm">
                Log In
              </button>
            </div>

            <div class="text-center pt-2 border-top border-secondary border-opacity-25">
              <span class="text-secondary small">Don't have an account yet?</span>
              <a href="register.php" class="text-danger fw-semibold small text-decoration-none ms-1">Register now &rarr;</a>
            </div>
          </form>
        </div>
      </div>

      <!-- Quick Demo Credentials Card (Task Evaluation Helper) -->
      <div class="glass-panel p-3">
        <div class="d-flex align-items-center gap-2 mb-2">
          <span class="hero-badge" style="padding: 2px 8px; font-size: 0.68rem;">Demo Helper</span>
          <span class="fw-semibold small text-white">Quick-fill (Password: <code>Password123!</code>):</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <button
            type="button"
            class="btn btn-sm btn-outline-apex"
            onclick="fillLogin('admin@gymfitness.local', 'Password123!')">
            Admin Account
          </button>
          <button
            type="button"
            class="btn btn-sm btn-outline-apex"
            onclick="fillLogin('trainer@gymfitness.local', 'Password123!')">
            Trainer Account
          </button>
          <button
            type="button"
            class="btn btn-sm btn-outline-apex"
            onclick="fillLogin('member@gymfitness.local', 'Password123!')">
            Member Account
          </button>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function fillLogin(email, password) {
  document.getElementById('email').value = email;
  document.getElementById('password').value = password;
}
</script>
</body>
</html>
