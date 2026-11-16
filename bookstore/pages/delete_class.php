<?php
/**
 * Delete Fitness Class Action (Labs 2 & 3 - RBAC + CSRF + POST Enforcement)
 * Server-side enforced via requireRole(['admin', 'trainer']) and requireCsrfToken()
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

requireRole(['admin', 'trainer']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'Invalid request method. Deletions must be performed via POST.');
    header('Location: classes.php');
    exit;
}

requireCsrfToken(false);

$classId = filter_var($_POST['class_id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);

if (!$classId) {
    setFlash('error', 'Invalid or missing fitness class identifier.');
    header('Location: classes.php');
    exit;
}

try {
    $checkStmt = $pdo->prepare("SELECT id, title FROM fitness_classes WHERE id = :id LIMIT 1");
    $checkStmt->execute(['id' => $classId]);
    $existing = $checkStmt->fetch();

    if (!$existing) {
        setFlash('error', "Fitness class (#{$classId}) not found or already deleted.");
        header('Location: classes.php');
        exit;
    }

    $deleteStmt = $pdo->prepare("DELETE FROM fitness_classes WHERE id = :id");
    $deleteStmt->execute(['id' => $classId]);

    setFlash('success', "Fitness class '{$existing['title']}' has been permanently deleted.");
} catch (PDOException $e) {
    error_log("Class deletion error: " . $e->getMessage());
    setFlash('error', 'A database error occurred while trying to delete the class.');
}

header('Location: classes.php');
exit;
