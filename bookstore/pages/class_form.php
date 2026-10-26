<?php
/**
 * Fitness Class Create & Update Form (Lab 2 Tasks 3 & 6)
 * Server-side RBAC: Restricted exclusively to admin and trainer roles
 * Prepared Statement insertions and updates with comprehensive validation
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/preferences.php';

// Enforce strict staff authorization (HTTP 403 Forbidden for members/guests)
requireRole(['admin', 'trainer']);

$currentUser = getCurrentUser();
$editId = filter_var($_POST['class_id'] ?? $_POST['id'] ?? $_GET['id'] ?? null, FILTER_VALIDATE_INT);
$isEdit = !empty($editId);
$errors = [];

// Available standard preset images
$presetImages = [
    'images/item1.jpg' => 'Item 1 - Heavy Barbell & Deadlift',
    'images/item2.jpg' => 'Item 2 - Battle Ropes HIIT',
    'images/item3.jpg' => 'Item 3 - Olympic Weightlifting',
    'images/item4.jpg' => 'Item 4 - Turf Sled Push',
    'images/item5.jpg' => 'Item 5 - Yoga & Mobility Flow',
    'images/item6.jpg' => 'Item 6 - Boxing & Heavy Bag',
];

// Fetch categories from DB
try {
    $catStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $catStmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to load categories: " . $e->getMessage());
    $categories = [];
}

// Default form state
$formData = [
    'title'         => '',
    'category_id'   => '',
    'trainer_name'  => $currentUser['full_name'] ?? '',
    'schedule_info' => '',
    'price'         => '',
    'image_url'     => 'images/item1.jpg',
    'description'   => ''
];

// If in Edit mode, load current class record
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

        // Populate initial data for sticky form
        $formData = [
            'title'         => $existingClass['title'],
            'category_id'   => (string)$existingClass['category_id'],
            'trainer_name'  => $existingClass['trainer_name'],
            'schedule_info' => $existingClass['schedule_info'],
            'price'         => (string)$existingClass['price'],
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

// Handle Form Submission (Create or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['title']         = trim($_POST['title'] ?? '');
    $formData['category_id']   = trim($_POST['category_id'] ?? '');
    $formData['trainer_name']  = trim($_POST['trainer_name'] ?? '');
    $formData['schedule_info'] = trim($_POST['schedule_info'] ?? '');
    $formData['price']         = trim($_POST['price'] ?? '');
    $formData['image_url']     = trim($_POST['image_url'] ?? 'images/item1.jpg');
    $formData['description']   = trim($_POST['description'] ?? '');

    // Validation 1: Title
    if (empty($formData['title'])) {
        $errors['title'] = 'Program title is required.';
    } elseif (mb_strlen($formData['title']) < 3 || mb_strlen($formData['title']) > 150) {
        $errors['title'] = 'Title must be between 3 and 150 characters.';
    }

    // Validation 2: Category
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

    // Validation 3: Trainer Name
    if (empty($formData['trainer_name'])) {
        $errors['trainer_name'] = 'Lead trainer name is required.';
    } elseif (mb_strlen($formData['trainer_name']) < 2 || mb_strlen($formData['trainer_name']) > 100) {
        $errors['trainer_name'] = 'Trainer name must be between 2 and 100 characters.';
    }

    // Validation 4: Schedule Info
    if (empty($formData['schedule_info'])) {
        $errors['schedule_info'] = 'Training schedule information is required (e.g. Mon, Wed • 07:00 AM).';
    } elseif (mb_strlen($formData['schedule_info']) < 3 || mb_strlen($formData['schedule_info']) > 100) {
        $errors['schedule_info'] = 'Schedule info must be between 3 and 100 characters.';
    }

    // Validation 5: Price
    if ($formData['price'] === '' || !is_numeric($formData['price']) || (float)$formData['price'] < 0) {
        $errors['price'] = 'Please provide a valid session price greater than or equal to 0.';
    }

    // Validation 6: Description
    if (empty($formData['description'])) {
        $errors['description'] = 'Program description is required.';
    } elseif (mb_strlen($formData['description']) < 10) {
        $errors['description'] = 'Description must contain at least 10 characters.';
    }

    // Execution when validation passes
    if (empty($errors)) {
        try {
            if ($isEdit) {
                // UPDATE operation via Prepared Statement
                $updateSql = "
                    UPDATE fitness_classes SET
                        category_id   = :category_id,
                        title         = :title,
                        description   = :description,
                        trainer_name  = :trainer_name,
                        schedule_info = :schedule_info,
                        price         = :price,
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
                    'image_url'     => $formData['image_url'],
                    'id'            => $editId
                ]);

                setFlash('success', "Fitness class '{$formData['title']}' updated successfully!");
                header("Location: classes.php");
                exit;
            } else {
                // CREATE operation via Prepared Statement
                $insertSql = "
                    INSERT INTO fitness_classes 
                        (category_id, title, description, trainer_name, schedule_info, price, image_url, created_at)
                    VALUES 
                        (:category_id, :title, :description, :trainer_name, :schedule_info, :price, :image_url, NOW())
                ";
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute([
                    'category_id'   => $catIdVal,
                    'title'         => $formData['title'],
                    'description'   => $formData['description'],
                    'trainer_name'  => $formData['trainer_name'],
                    'schedule_info' => $formData['schedule_info'],
                    'price'         => number_format((float)$formData['price'], 2, '.', ''),
                    'image_url'     => $formData['image_url']
                ]);

                setFlash('success', "New fitness class '{$formData['title']}' created successfully!");
                header("Location: classes.php");
                exit;
            }
        } catch (PDOException $e) {
            error_log("Class form save error: " . $e->getMessage());
            $errors['system'] = "Database save failed: " . $e->getMessage();
        }
    }
}
?>
<?php $activeTheme = getActiveTheme(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $isEdit ? 'Edit Fitness Class' : 'Create New Fitness Class'; ?> - Apex Fitness</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../public/css/style.css">
</head>
<body class="<?php echo ($activeTheme === 'dark') ? 'theme-dark' : ''; ?>" data-bs-theme="<?php echo $activeTheme; ?>">

<?php require_once __DIR__ . '/../components/header.php'; ?>

<main class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">
      <!-- Breadcrumb -->
      <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
          <li class="breadcrumb-item"><a href="../public/index.php" class="text-decoration-none">Home</a></li>
          <li class="breadcrumb-item"><a href="classes.php" class="text-decoration-none">Classes</a></li>
          <li class="breadcrumb-item active text-white" aria-current="page"><?php echo $isEdit ? 'Edit Class #' . $editId : 'New Class'; ?></li>
        </ol>
      </nav>

      <div class="glass-panel overflow-hidden mb-5">
        <div class="p-4 border-bottom border-secondary border-opacity-25" style="background: rgba(9, 13, 22, 0.6);">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div>
              <span class="hero-badge mb-2">Staff Portal (Task 3 & 6)</span>
              <h3 class="fw-bold font-headline text-uppercase mb-1"><?php echo $isEdit ? 'Edit Fitness Class' : 'Create New Fitness Class'; ?></h3>
              <p class="text-secondary small mb-0">Authorized as: <strong><?php echo htmlspecialchars($currentUser['full_name']); ?></strong> (<?php echo htmlspecialchars(strtoupper($currentUser['role'])); ?>)</p>
            </div>
            <a href="classes.php" class="btn btn-outline-apex btn-sm px-3">
              &larr; Cancel
            </a>
          </div>
        </div>

        <div class="card-body p-4 p-md-5">
          <?php if (!empty($errors['system'])): ?>
            <div class="alert alert-danger shadow-sm py-2 mb-4" role="alert">
              <?php echo htmlspecialchars($errors['system']); ?>
            </div>
          <?php endif; ?>

          <form action="<?php echo $isEdit ? 'class_form.php?id=' . urlencode($editId) : 'class_form.php'; ?>" method="POST" novalidate>
            <!-- Title -->
            <div class="mb-4">
              <label for="title" class="form-label fw-semibold">Class Title <span class="text-danger">*</span></label>
              <input
                type="text"
                class="form-control <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>"
                id="title"
                name="title"
                placeholder="e.g. Power Barbell Mastery"
                value="<?php echo htmlspecialchars($formData['title']); ?>"
                required>
              <?php if (isset($errors['title'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['title']); ?></div>
              <?php endif; ?>
            </div>

            <div class="row g-3 mb-4">
              <!-- Category -->
              <div class="col-12 col-md-6">
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
                      <?php echo htmlspecialchars($cat['name']); ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <?php if (isset($errors['category_id'])): ?>
                  <div class="invalid-feedback"><?php echo htmlspecialchars($errors['category_id']); ?></div>
                <?php endif; ?>
              </div>

              <!-- Price -->
              <div class="col-12 col-md-6">
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
                    value="<?php echo htmlspecialchars($formData['price']); ?>"
                    required>
                  <?php if (isset($errors['price'])): ?>
                    <div class="invalid-feedback"><?php echo htmlspecialchars($errors['price']); ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div class="row g-3 mb-4">
              <!-- Lead Trainer -->
              <div class="col-12 col-md-6">
                <label for="trainer_name" class="form-label fw-semibold">Lead Coach / Trainer <span class="text-danger">*</span></label>
                <input
                  type="text"
                  class="form-control <?php echo isset($errors['trainer_name']) ? 'is-invalid' : ''; ?>"
                  id="trainer_name"
                  name="trainer_name"
                  placeholder="e.g. Marcus Vance"
                  value="<?php echo htmlspecialchars($formData['trainer_name']); ?>"
                  required>
                <?php if (isset($errors['trainer_name'])): ?>
                  <div class="invalid-feedback"><?php echo htmlspecialchars($errors['trainer_name']); ?></div>
                <?php endif; ?>
              </div>

              <!-- Schedule Info -->
              <div class="col-12 col-md-6">
                <label for="schedule_info" class="form-label fw-semibold">Schedule Time & Days <span class="text-danger">*</span></label>
                <input
                  type="text"
                  class="form-control <?php echo isset($errors['schedule_info']) ? 'is-invalid' : ''; ?>"
                  id="schedule_info"
                  name="schedule_info"
                  placeholder="e.g. Mon, Wed, Fri • 06:00 AM - 07:30 AM"
                  value="<?php echo htmlspecialchars($formData['schedule_info']); ?>"
                  required>
                <?php if (isset($errors['schedule_info'])): ?>
                  <div class="invalid-feedback"><?php echo htmlspecialchars($errors['schedule_info']); ?></div>
                <?php endif; ?>
              </div>
            </div>

            <!-- Image Selection -->
            <div class="mb-4">
              <label for="image_url" class="form-label fw-semibold">Class Banner Image</label>
              <select class="form-select mb-2" id="image_url" name="image_url" onchange="previewSelectedImage(this.value)">
                <?php foreach ($presetImages as $path => $label): ?>
                  <option value="<?php echo htmlspecialchars($path); ?>" <?php echo ($formData['image_url'] === $path) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($label); ?> (<?php echo htmlspecialchars($path); ?>)
                  </option>
                <?php endforeach; ?>
              </select>
              <!-- Live Image Preview -->
              <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-3 border">
                <img
                  id="image_preview"
                  src="../public/<?php echo htmlspecialchars($formData['image_url'] ?: 'images/item1.jpg'); ?>"
                  alt="Preview"
                  class="rounded object-fit-cover shadow-sm"
                  style="width: 120px; height: 80px;">
                <div class="small text-muted">
                  Selected image preview. Stored in <code>public/images/</code>.
                </div>
              </div>
            </div>

            <!-- Description -->
            <div class="mb-4">
              <label for="description" class="form-label fw-semibold">Detailed Description & Syllabus <span class="text-danger">*</span></label>
              <textarea
                class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>"
                id="description"
                name="description"
                rows="4"
                placeholder="Comprehensive overview of training movements, targeted adaptations, and member guidelines..."
                required><?php echo htmlspecialchars($formData['description']); ?></textarea>
              <?php if (isset($errors['description'])): ?>
                <div class="invalid-feedback"><?php echo htmlspecialchars($errors['description']); ?></div>
              <?php else: ?>
                <div class="form-text small text-muted">At least 10 characters detailing what attendees will practice.</div>
              <?php endif; ?>
            </div>

            <!-- Submit CTA -->
            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
              <a href="classes.php" class="btn btn-outline-secondary px-4">
                Discard Changes
              </a>
              <button type="submit" class="btn btn-danger btn-lg px-5 fw-semibold shadow-sm">
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
