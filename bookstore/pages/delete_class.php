<?php
/**
 * Delete Fitness Class Action (Lab 2 Tasks 3 & 6)
 * Server-side enforced via requireRole(['admin', 'trainer'])
 * Requires POST method and prepared statement execution
 */
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

// Enforce strict staff role authorization (HTTP 403 if unauthorized member/guest)
requireRole(['admin', 'trainer']);

// Enforce POST method only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    setFlash('error', 'Invalid request method. Deletions must be performed via POST.');
    header('Location: classes.php');
    exit;
}

$classId = filter_var($_POST['class_id'] ?? null, FILTER_VALIDATE_INT);

if (!$classId) {
    setFlash('error', 'Invalid or missing fitness class identifier.');
    header('Location: classes.php');
    exit;
}

try {
    // Check if class exists
    $checkStmt = $pdo->prepare("SELECT id, title FROM fitness_classes WHERE id = :id LIMIT 1");
    $checkStmt->execute(['id' => $classId]);
    $existing = $checkStmt->fetch();

    if (!$existing) {
        setFlash('error', "Fitness class (#{$classId}) not found or already deleted.");
        header('Location: classes.php');
        exit;
    }

    // Perform permanent deletion via Prepared Statement
    $deleteStmt = $pdo->prepare("DELETE FROM fitness_classes WHERE id = :id");
    $deleteStmt->execute(['id' => $classId]);

    setFlash('success', "Fitness class '{$existing['title']}' has been permanently deleted.");
} catch (PDOException $e) {
    error_log("Class deletion error: " . $e->getMessage());
    setFlash('error', 'A database error occurred while trying to delete the class.');
}

header('Location: classes.php');
exit;
