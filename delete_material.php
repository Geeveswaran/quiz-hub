<?php
session_start();
require_once 'config/mongodb.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: index.php");
    exit;
}

$error = '';
$material_id = $_GET['id'] ?? null;

if (!$material_id) {
    header("Location: upload_study_material.php");
    exit;
}

$db = getDatabase();

// Get the material to verify ownership and get filename
$material = $db->study_materials->findOne(['_id' => $material_id, 'teacher_id' => $_SESSION['user_id']]);

if (!$material) {
    header("Location: upload_study_material.php?error=Material not found or you don't have permission to delete it");
    exit;
}

// Delete the file from uploads folder
if (!empty($material['filename'])) {
    $filepath = __DIR__ . '/uploads/' . $material['filename'];
    if (file_exists($filepath)) {
        unlink($filepath);
    }
}

// Delete from database
$result = $db->study_materials->deleteMany(['_id' => $material_id]);

if ($result->getDeletedCount() > 0) {
    header("Location: upload_study_material.php?success=Material deleted successfully!");
    exit;
} else {
    header("Location: upload_study_material.php?error=Failed to delete material");
    exit;
}
?>
