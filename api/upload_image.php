<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

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

$tmpFile = $_FILES['image']['tmp_name'];
$imageInfo = getimagesize($tmpFile);

if (!$imageInfo) {
    echo json_encode(['success' => false, 'error' => 'Fail bukan gambar']);
    exit;
}

$mimeType = $imageInfo['mime'];
$width = $imageInfo[0];
$height = $imageInfo[1];

$sourceImage = null;
switch ($mimeType) {
    case 'image/jpeg':
        $sourceImage = imagecreatefromjpeg($tmpFile);
        break;
    case 'image/png':
        $sourceImage = imagecreatefrompng($tmpFile);
        break;
    case 'image/webp':
        $sourceImage = imagecreatefromwebp($tmpFile);
        break;
    default:
        echo json_encode(['success' => false, 'error' => 'Jenis gambar tidak disokong']);
        exit;
}

if (!$sourceImage) {
    echo json_encode(['success' => false, 'error' => 'Gagal baca gambar']);
    exit;
}

$maxWidth = 1920;
$maxHeight = 1080;

if ($width > $maxWidth || $height > $maxHeight) {
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = (int)($width * $ratio);
    $newHeight = (int)($height * $ratio);
    
    $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
    if ($mimeType === 'image/png') {
        imagealphablending($resizedImage, false);
        imagesavealpha($resizedImage, true);
    }
    imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    imagedestroy($sourceImage);
    $sourceImage = $resizedImage;
}

$filename = time() . '_' . uniqid() . '.jpg';
$filepath = $uploadDir . $filename;

$quality = 85;
$maxSizeKB = 400;

do {
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    imagejpeg($sourceImage, $filepath, $quality);
    $fileSizeKB = filesize($filepath) / 1024;
    $quality -= 5;
} while ($fileSizeKB > $maxSizeKB && $quality > 10);

imagedestroy($sourceImage);

if (file_exists($filepath)) {
    echo json_encode(['success' => true, 'image_path' => 'uploads/' . $filename]);
} else {
    echo json_encode(['success' => false, 'error' => 'Gagal simpan gambar']);
}
?>