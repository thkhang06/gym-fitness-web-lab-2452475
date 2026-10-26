<?php
/**
 * User Registration Page (Lab 2 Task 4.1)
 * Handles input validation, uniqueness check, and secure password hashing
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

$errors = [];
$fullName = '';
$email = '';
$role = 'member';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName        = trim($_POST['full_name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $password        = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role            = trim($_POST['role'] ?? 'member');

    // 1. Required fields check
    if (empty($fullName)) {
        $errors['full_name'] = 'Full Name is required.';
    } elseif (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
        $errors['full_name'] = 'Full Name must be between 2 and 100 characters.';
    }

    if (empty($email)) {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    // 2. Role validation (allowing member and trainer for academic evaluation)
    $allowedRoles = ['member', 'trainer'];
    if (!in_array($role, $allowedRoles, true)) {
        $role = 'member';
    }

    // 3. Password strength and matching validation
    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors['password'] = 'Password must include both letters and numbers.';
    }

    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Password confirmation does not match.';
    }

    // 4. Duplicate email prevention using PDO Prepared Statement
    if (empty($errors['email'])) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                $errors['email'] = 'An account with this email address already exists. Please log in or use another email.';
            }
        } catch (PDOException $e) {
            error_log('Registration email check error: ' . $e->getMessage());
            $errors['system'] = 'A temporary database error occurred. Please try again.';
        }
    }

    // 5. If validation passes, hash password and insert into users table
    if (empty($errors)) {
        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO users (full_name, email, password_hash, role, created_at)
                VALUES (:full_name, :email, :password_hash, :role, NOW())
            ");
            $stmt->execute([
                'full_name'     => $fullName,
                'email'         => $email,
                'password_hash' => $passwordHash,
                'role'          => $role
            ]);

            setFlash('success', 'Registration successful! You can now log in with your credentials.');
            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            error_log('Registration insert error: ' . $e->getMessage());
            $errors['system'] = 'Failed to create your account due to a system error. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Athlete Account - TIGER FITNESS</title>
  <link rel="icon" type="image/svg+xml" href="../public/images/favicon.svg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../public/css/style.css">
</head>
<body class="<?php echo ($activeTheme === 'dark') ? 'theme-dark' : ''; ?>" data-bs-theme="<?php echo $activeTheme; ?>">

<?php require_once __DIR__ . '/../components/header.php'; ?>

<main class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
      <div class="glass-panel overflow-hidden shadow-lg">
        <div class="p-4 text-center border-bottom border-secondary border-opacity-25" style="background: rgba(10, 11, 14, 0.7);">
          <div class="mb-2">
            <img src="../public/images/tiger-logo.svg" alt="Tiger Fitness Mascot" width="54" height="54" style="filter: drop-shadow(0 0 10px rgba(255, 107, 0, 0.5));">
          </div>
          <span class="hero-badge mb-2">Join Tiger Fitness</span>
          <h3 class="fw-bold font-headline text-uppercase text-white mb-1">Create Account</h3>
          <p class="text-secondary small mb-0">Begin your high-performance training journey today</p>
        </div>

        <div class="card-body p-4 p-md-5">
          <?php if (!empty($errors['system'])): ?>
            <div class="alert alert-danger shadow-sm py-2 mb-4" role="alert">
              <?php echo htmlspecialchars($errors['system']); ?>
            </div>
          <?php endif; ?>

          <form action="register.php" method="POST" novalidate>
            <!-- Full Name -->
            <div class="mb-3">
              <label for="full_name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
              <input
                type="text"
                class="form-control <?php echo isset($errors['full_name']) ? 'is-invalid' : ''; ?>"
                id="full_name"
                name="full_name"
                placeholder="e.g. John Doe"
                value="<?php echo htmlspecialchars($fullName); ?>"
                required>
              <?php if (isset($errors['full_name'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['full_name']); ?></div>
              <?php endif; ?>
            </div>

            <!-- Email -->
            <div class="mb-3">
              <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
              <input
                type="email"
                class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                id="email"
                name="email"
                placeholder="name@example.com"
                value="<?php echo htmlspecialchars($email); ?>"
                required>
              <?php if (isset($errors['email'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['email']); ?></div>
              <?php endif; ?>
            </div>

            <!-- Role Selection -->
            <div class="mb-3">
              <label for="role" class="form-label fw-semibold">Account Role (Lab Demo)</label>
              <select class="form-select" id="role" name="role">
                <option value="member" <?php echo $role === 'member' ? 'selected' : ''; ?>>Club Member (Default)</option>
                <option value="trainer" <?php echo $role === 'trainer' ? 'selected' : ''; ?>>Fitness Trainer / Coach</option>
              </select>
              <div class="form-text small text-muted">Allows testing trainer & member privileges for Lab evaluation.</div>
            </div>

            <!-- Password -->
            <div class="mb-3">
              <label for="password" class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
              <input
                type="password"
                class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                id="password"
                name="password"
                placeholder="Minimum 8 characters (letters & numbers)"
                required>
              <?php if (isset($errors['password'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['password']); ?></div>
              <?php else: ?>
                <div class="form-text small text-muted">At least 8 characters with both letters and numbers.</div>
              <?php endif; ?>
            </div>

            <!-- Confirm Password -->
            <div class="mb-4">
              <label for="confirm_password" class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
              <input
                type="password"
                class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>"
                id="confirm_password"
                name="confirm_password"
                placeholder="Re-enter your password"
                required>
              <?php if (isset($errors['confirm_password'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['confirm_password']); ?></div>
              <?php endif; ?>
            </div>

            <!-- Submit -->
            <div class="d-grid mb-3">
              <button type="submit" class="btn btn-apex-crimson btn-lg fw-semibold shadow-sm">
                Register Account
              </button>
            </div>

            <div class="text-center pt-2 border-top border-secondary border-opacity-25">
              <span class="text-secondary small">Already have an account?</span>
              <a href="login.php" class="text-danger fw-semibold small text-decoration-none ms-1">Log in here &rarr;</a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
