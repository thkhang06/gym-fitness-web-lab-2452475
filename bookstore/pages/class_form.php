<?php
/**
 * Fitness Class Create & Update Form (Labs 2 & 3 - CRUD, RBAC & CSRF Hardening)
 * Server-side RBAC: Restricted exclusively to admin and trainer roles
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/preferences.php';

requireRole(['admin', 'trainer']);

$currentUser = getCurrentUser();
$editId = filter_var($_POST['class_id'] ?? $_POST['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
$isEdit = !empty($editId);
$errors = [];

$presetImages = [
    'images/item1.jpg' => 'Item 1 - Heavy Barbell & Deadlift',
    'images/item2.jpg' => 'Item 2 - Battle Ropes HIIT',
    'images/item3.jpg' => 'Item 3 - Olympic Weightlifting',
    'images/item4.jpg' => 'Item 4 - Turf Sled Push',
    'images/item5.jpg' => 'Item 5 - Yoga & Mobility Flow',
    'images/item6.jpg' => 'Item 6 - Boxing & Heavy Bag',
];

try {
    $catStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $catStmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to load categories: " . $e->getMessage());
    $categories = [];
}

$formData = [
    'title'         => '',
    'category_id'   => '',
    'trainer_name'  => $currentUser['full_name'] ?? '',
    'schedule_info' => '',
    'price'         => '',
    'max_capacity'  => '15',
    'image_url'     => 'images/item1.jpg',
    'description'   => ''
];

if ($isEdit) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM fitness_classes WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $editId]);
        $existingClass = $stmt->fetch();

        if (!$existingClass) {
            setFlash('error', "Fitness class (#{$editId}) could not be found.");
            header('Location: classes.php');
            exit;
        }

        $formData = [
            'title'         => $existingClass['title'],
            'category_id'   => (string)$existingClass['category_id'],
            'trainer_name'  => $existingClass['trainer_name'],
            'schedule_info' => $existingClass['schedule_info'],
            'price'         => (string)$existingClass['price'],
            'max_capacity'  => (string)($existingClass['max_capacity'] ?? 15),
            'image_url'     => $existingClass['image_url'],
            'description'   => $existingClass['description']
        ];
    } catch (PDOException $e) {
        error_log("Failed to load class for edit: " . $e->getMessage());
        setFlash('error', "Database error while loading class details.");
        header('Location: classes.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken(false);

    $formData['title']         = trim($_POST['title'] ?? '');
    $formData['category_id']   = trim($_POST['category_id'] ?? '');
    $formData['trainer_name']  = trim($_POST['trainer_name'] ?? '');
    $formData['schedule_info'] = trim($_POST['schedule_info'] ?? '');
    $formData['price']         = trim($_POST['price'] ?? '');
    $formData['max_capacity']  = trim($_POST['max_capacity'] ?? '15');
    $formData['image_url']     = trim($_POST['image_url'] ?? 'images/item1.jpg');
    $formData['description']   = trim($_POST['description'] ?? '');

    if ($formData['title'] === '') {
        $errors['title'] = 'Program title is required.';
    } elseif (mb_strlen($formData['title']) < 3 || mb_strlen($formData['title']) > 150) {
        $errors['title'] = 'Title must be between 3 and 150 characters.';
    }

    $catIdVal = filter_var($formData['category_id'], FILTER_VALIDATE_INT);
    if ($catIdVal === false) {
        $errors['category_id'] = 'Please select a valid discipline category.';
    } else {
        $validCat = false;
        foreach ($categories as $cat) {
            if ((int)$cat['id'] === $catIdVal) {
                $validCat = true;
                break;
            }
        }
        if (!$validCat) {
            $errors['category_id'] = 'Selected category does not exist.';
        }
    }

    if ($formData['trainer_name'] === '') {
        $errors['trainer_name'] = 'Lead trainer name is required.';
    } elseif (mb_strlen($formData['trainer_name']) < 2 || mb_strlen($formData['trainer_name']) > 100) {
        $errors['trainer_name'] = 'Trainer name must be between 2 and 100 characters.';
    }

    if ($formData['schedule_info'] === '') {
        $errors['schedule_info'] = 'Training schedule information is required.';
    } elseif (mb_strlen($formData['schedule_info']) < 3 || mb_strlen($formData['schedule_info']) > 100) {
        $errors['schedule_info'] = 'Schedule info must be between 3 and 100 characters.';
    }

    if ($formData['price'] === '' || !is_numeric($formData['price']) || (float)$formData['price'] < 0 || (float)$formData['price'] > 9999) {
        $errors['price'] = 'Please provide a valid session price between $0 and $9999.';
    }

    $maxCapVal = filter_var($formData['max_capacity'], FILTER_VALIDATE_INT);
    if ($maxCapVal === false || $maxCapVal < 1 || $maxCapVal > 100) {
        $errors['max_capacity'] = 'Class capacity must be a whole number between 1 and 100.';
    }

    if (!array_key_exists($formData['image_url'], $presetImages)) {
        $formData['image_url'] = 'images/item1.jpg';
    }

    if ($formData['description'] === '') {
        $errors['description'] = 'Program description is required.';
    } elseif (mb_strlen($formData['description']) < 10) {
        $errors['description'] = 'Description must contain at least 10 characters.';
    }

    if (empty($errors)) {
        try {
            if ($isEdit) {
                $updateSql = "
                    UPDATE fitness_classes SET
                        category_id   = :category_id,
                        title         = :title,
                        description   = :description,
                        trainer_name  = :trainer_name,
                        schedule_info = :schedule_info,
                        price         = :price,
                        max_capacity  = :max_capacity,
                        image_url     = :image_url
                    WHERE id = :id
                ";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    'category_id'   => $catIdVal,
                    'title'         => $formData['title'],
                    'description'   => $formData['description'],
                    'trainer_name'  => $formData['trainer_name'],
                    'schedule_info' => $formData['schedule_info'],
                    'price'         => number_format((float)$formData['price'], 2, '.', ''),
                    'max_capacity'  => $maxCapVal,
                    'image_url'     => $formData['image_url'],
                    'id'            => $editId
                ]);

                setFlash('success', "Fitness class '{$formData['title']}' updated successfully!");
                header("Location: classes.php");
                exit;
            } else {
                $insertSql = "
                    INSERT INTO fitness_classes
                        (category_id, title, description, trainer_name, schedule_info, price, max_capacity, image_url, created_at)
                    VALUES
                        (:category_id, :title, :description, :trainer_name, :schedule_info, :price, :max_capacity, :image_url, NOW())
                ";
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute([
                    'category_id'   => $catIdVal,
                    'title'         => $formData['title'],
                    'description'   => $formData['description'],
                    'trainer_name'  => $formData['trainer_name'],
                    'schedule_info' => $formData['schedule_info'],
                    'price'         => number_format((float)$formData['price'], 2, '.', ''),
                    'max_capacity'  => $maxCapVal,
                    'image_url'     => $formData['image_url']
                ]);

                setFlash('success', "New fitness class '{$formData['title']}' created successfully!");
                header("Location: classes.php");
                exit;
            }
        } catch (PDOException $e) {
            error_log("Class form save error: " . $e->getMessage());
            $errors['system'] = "Unable to save class right now due to a database error. Please try again.";
        }
    }
}

$activeTheme = getActiveTheme();
$seo = [
    'title'       => ($isEdit ? 'Edit Fitness Class' : 'Create New Fitness Class') . ' - TIGER FITNESS',
    'description' => 'Staff management form to create or update fitness class details and maximum capacity.',
    'robots'      => 'noindex, nofollow',
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
  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="../public/index.php" class="text-decoration-none">Home</a></li>
          <li class="breadcrumb-item"><a href="classes.php" class="text-decoration-none">Classes</a></li>
          <li class="breadcrumb-item active text-white" aria-current="page"><?php echo $isEdit ? 'Edit Class #' . (int)$editId : 'New Class'; ?></li>
        </ol>
      </nav>

      <div class="glass-panel overflow-hidden mb-5">
        <div class="p-4 border-bottom border-secondary border-opacity-25" style="background: rgba(9, 13, 22, 0.25);">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
              <span class="hero-badge mb-2">Staff Portal (RBAC + CSRF)</span>
              <h1 class="h3 fw-bold font-headline text-uppercase mb-1"><?php echo $isEdit ? 'Edit Fitness Class' : 'Create New Fitness Class'; ?></h1>
              <p class="text-secondary small mb-0">Authorized as: <strong><?php echo e($currentUser['full_name']); ?></strong> (<?php echo e(strtoupper($currentUser['role'])); ?>)</p>
            </div>
            <a href="classes.php" class="btn btn-outline-apex btn-sm px-3">&larr; Cancel</a>
          </div>
        </div>

        <div class="card-body p-4 p-md-5">
          <?php if (!empty($errors['system'])): ?>
            <div class="alert alert-danger shadow-sm py-2 mb-4" role="alert">
              <?php echo e($errors['system']); ?>
            </div>
          <?php endif; ?>

          <form action="<?php echo $isEdit ? 'class_form.php?id=' . urlencode((string)$editId) : 'class_form.php'; ?>" method="POST" novalidate>
            <?php echo csrfField(); ?>

            <div class="mb-4">
              <label for="title" class="form-label fw-semibold">Class Title <span class="text-danger">*</span></label>
              <input
                type="text"
                class="form-control <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>"
                id="title"
                name="title"
                placeholder="e.g. Power Barbell Mastery"
                value="<?php echo e($formData['title']); ?>"
                required>
              <?php if (isset($errors['title'])): ?>
                <div class="invalid-feedback"><?php echo e($errors['title']); ?></div>
              <?php endif; ?>
            </div>

            <div class="row g-3 mb-4">
              <div class="col-12 col-md-4">
                <label for="category_id" class="form-label fw-semibold">Discipline Category <span class="text-danger">*</span></label>
                <select
                  class="form-select <?php echo isset($errors['category_id']) ? 'is-invalid' : ''; ?>"
                  id="category_id"
                  name="category_id"
                  required>
                  <option value="">Select Category...</option>
                  <?php foreach ($categories as $cat): ?>
                    <option
                      value="<?php echo (int)$cat['id']; ?>"
                      <?php echo ($formData['category_id'] === (string)$cat['id']) ? 'selected' : ''; ?>>
                      <?php echo e($cat['name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php if (isset($errors['category_id'])): ?>
                  <div class="invalid-feedback"><?php echo e($errors['category_id']); ?></div>
                <?php endif; ?>
              </div>

              <div class="col-12 col-md-4">
                <label for="price" class="form-label fw-semibold">Session Price ($ USD) <span class="text-danger">*</span></label>
                <div class="input-group">
                  <span class="input-group-text">$</span>
                  <input
                    type="number"
                    step="0.01"
                    min="0"
                    class="form-control <?php echo isset($errors['price']) ? 'is-invalid' : ''; ?>"
                    id="price"
                    name="price"
                    placeholder="35.00"
                    value="<?php echo e($formData['price']); ?>"
                    required>
                  <?php if (isset($errors['price'])): ?>
                    <div class="invalid-feedback"><?php echo e($errors['price']); ?></div>
                  <?php endif; ?>
                </div>
              </div>

              <div class="col-12 col-md-4">
                <label for="max_capacity" class="form-label fw-semibold">Max Capacity <span class="text-danger">*</span></label>
                <input
                  type="number"
                  min="1"
                  max="100"
                  class="form-control <?php echo isset($errors['max_capacity']) ? 'is-invalid' : ''; ?>"
                  id="max_capacity"
                  name="max_capacity"
                  value="<?php echo e($formData['max_capacity']); ?>"
                  required>
                <?php if (isset($errors['max_capacity'])): ?>
                  <div class="invalid-feedback"><?php echo e($errors['max_capacity']); ?></div>
                <?php endif; ?>
              </div>
            </div>

            <div class="row g-3 mb-4">
              <div class="col-12 col-md-6">
                <label for="trainer_name" class="form-label fw-semibold">Lead Coach / Trainer <span class="text-danger">*</span></label>
                <input
                  type="text"
                  class="form-control <?php echo isset($errors['trainer_name']) ? 'is-invalid' : ''; ?>"
                  id="trainer_name"
                  name="trainer_name"
                  placeholder="e.g. Marcus Vance"
                  value="<?php echo e($formData['trainer_name']); ?>"
                  required>
                <?php if (isset($errors['trainer_name'])): ?>
                  <div class="invalid-feedback"><?php echo e($errors['trainer_name']); ?></div>
                <?php endif; ?>
              </div>

              <div class="col-12 col-md-6">
                <label for="schedule_info" class="form-label fw-semibold">Schedule Time &amp; Days <span class="text-danger">*</span></label>
                <input
                  type="text"
                  class="form-control <?php echo isset($errors['schedule_info']) ? 'is-invalid' : ''; ?>"
                  id="schedule_info"
                  name="schedule_info"
                  placeholder="e.g. Mon, Wed, Fri • 06:00 AM - 07:30 AM"
                  value="<?php echo e($formData['schedule_info']); ?>"
                  required>
                <?php if (isset($errors['schedule_info'])): ?>
                  <div class="invalid-feedback"><?php echo e($errors['schedule_info']); ?></div>
                <?php endif; ?>
              </div>
            </div>

            <div class="mb-4">
              <label for="image_url" class="form-label fw-semibold">Class Banner Image</label>
              <select class="form-select mb-2" id="image_url" name="image_url" onchange="previewSelectedImage(this.value)">
                <?php foreach ($presetImages as $path => $label): ?>
                  <option value="<?php echo e($path); ?>" <?php echo ($formData['image_url'] === $path) ? 'selected' : ''; ?>>
                    <?php echo e($label); ?> (<?php echo e($path); ?>)
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="d-flex align-items-center gap-3 p-3 rounded-3 border">
                <img
                  id="image_preview"
                  src="../public/<?php echo e($formData['image_url'] ?: 'images/item1.jpg'); ?>"
                  alt="Class Banner Preview"
                  class="rounded object-fit-cover shadow-sm"
                  style="width: 120px; height: 80px;">
                <div class="small text-secondary">
                  Selected image preview. Stored in <code>public/images/</code>.
                </div>
              </div>
            </div>

            <div class="mb-4">
              <label for="description" class="form-label fw-semibold">Detailed Description &amp; Syllabus <span class="text-danger">*</span></label>
              <textarea
                class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>"
                id="description"
                name="description"
                rows="4"
                placeholder="Comprehensive overview of training movements..."
                required><?php echo e($formData['description']); ?></textarea>
              <?php if (isset($errors['description'])): ?>
                <div class="invalid-feedback"><?php echo e($errors['description']); ?></div>
              <?php else: ?>
                <div class="form-text small text-muted">At least 10 characters detailing what attendees will practice.</div>
              <?php endif; ?>
            </div>

            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
              <a href="classes.php" class="btn btn-outline-apex px-4">Discard Changes</a>
              <button type="submit" class="btn btn-apex-crimson btn-lg px-5 fw-semibold shadow-sm">
                <?php echo $isEdit ? 'Update Class' : 'Create Class'; ?>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function previewSelectedImage(val) {
  document.getElementById('image_preview').src = '../public/' + val;
}
</script>
</body>
</html>
