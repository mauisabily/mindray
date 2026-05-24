<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Turn off error reporting
error_reporting(0);
ini_set('display_errors', 0);

$uploadDir = '../uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Gagal muat naik gambar']);
    exit;
}

$filename = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '', $_FILES['image']['name']);
$filepath = $uploadDir . $filename;

if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
    echo json_encode(['success' => true, 'image_path' => 'uploads/' . $filename]);
} else {
    echo json_encode(['success' => false, 'error' => 'Gagal simpan gambar']);
}
?>