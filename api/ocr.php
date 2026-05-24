<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config.php';

// Check if config.php exists and has OCR_API_KEY
if (!defined('OCR_API_KEY') || OCR_API_KEY == 'YOUR_OCR_API_KEY') {
    echo json_encode([
        'success' => false, 
        'error' => 'OCR API key belum diisi dalam config.php. Daftar percuma di https://ocr.space/OCRAPI'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed. Use POST.']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $errorMsg = 'Gagal muat naik gambar. Error code: ' . ($_FILES['image']['error'] ?? 'no file');
    echo json_encode(['success' => false, 'error' => $errorMsg]);
    exit;
}

// Check if uploads folder exists and writable
$uploadDir = '../uploads/';
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        echo json_encode(['success' => false, 'error' => 'Gagal buat folder uploads. Sila buat folder "uploads" secara manual dan set permission 755.']);
        exit;
    }
}

if (!is_writable($uploadDir)) {
    echo json_encode(['success' => false, 'error' => 'Folder uploads tidak boleh ditulis. Sila set permission 755: chmod 755 uploads']);
    exit;
}

// Save image
$filename = time() . '_' . preg_replace('/[^a-zA-Z0-9.]/', '', $_FILES['image']['name']);
$filepath = $uploadDir . $filename;

if (!move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
    echo json_encode(['success' => false, 'error' => 'Gagal simpan gambar. Sila pastikan folder uploads boleh ditulis.']);
    exit;
}

// Check if cURL is enabled
if (!function_exists('curl_init')) {
    echo json_encode(['success' => false, 'error' => 'cURL tidak diaktifkan. Sila aktifkan extension=curl dalam php.ini dan restart Apache.']);
    exit;
}

// Call OCR.space API
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.ocr.space/parse/image');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'apikey' => OCR_API_KEY,
    'file' => new CURLFile($filepath),
    'language' => 'eng',
    'OCREngine' => 2,
    'scale' => 'true',
    'isTable' => 'true'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(['success' => false, 'error' => 'cURL error: ' . $curlError]);
    exit;
}

if ($httpCode !== 200) {
    echo json_encode(['success' => false, 'error' => "OCR API gagal. HTTP Code: $httpCode"]);
    exit;
}

$data = json_decode($response, true);
if (!$data || isset($data['IsErroredOnProcessing']) && $data['IsErroredOnProcessing']) {
    echo json_encode(['success' => false, 'error' => 'OCR API returns error: ' . ($data['ErrorMessage'][0] ?? 'Unknown')]);
    exit;
}

$ocrText = $data['ParsedResults'][0]['ParsedText'] ?? '';

// Extract data with various regex patterns
$systolic = $diastolic = $map = $pr = $spo2 = $time = null;

// Pattern 1: 117/58 (70) or 117/58(70)
preg_match('/(\d{2,3})\s*\/\s*(\d{2,3})\s*\((\d{2,3})\)/', $ocrText, $bp1);
if ($bp1) {
    $systolic = $bp1[1];
    $diastolic = $bp1[2];
    $map = $bp1[3];
}

// Pattern 2: 117/58
if (!$systolic) {
    preg_match('/(\d{2,3})\s*\/\s*(\d{2,3})/', $ocrText, $bp2);
    if ($bp2) {
        $systolic = $bp2[1];
        $diastolic = bp2[2];
    }
}

// Pattern for PR: "PR 65", "PR:65", "PR65"
preg_match('/PR\s*:?\s*(\d{2,3})/i', $ocrText, $pr1);
if ($pr1) $pr = $pr1[1];

if (!$pr) {
    preg_match('/(\d{2,3})\s*(?:bpm|BPM)/', $ocrText, $pr2);
    if ($pr2) $pr = $pr2[1];
}

// Pattern for SpO2
preg_match('/SpO2?\s*:?\s*(\d{2,3})/i', $ocrText, $spo2_1);
if ($spo2_1) $spo2 = $spo2_1[1];

if (!$spo2) {
    preg_match('/(\d{2,3})\s*%/i', $ocrText, $spo2_2);
    if ($spo2_2) $spo2 = $spo2_2[1];
}

// Pattern for Time
preg_match('/(\d{2}:\d{2})/', $ocrText, $timeMatch);
if ($timeMatch) $time = $timeMatch[1];

// Calculate MAP if missing
if ($systolic && $diastolic && !$map) {
    $map = round($diastolic + ($systolic - $diastolic) / 3);
}

$result = [
    'systolic' => $systolic,
    'diastolic' => $diastolic,
    'map' => $map,
    'pr' => $pr,
    'spo2' => $spo2,
    'time' => $time ?: date('H:i'),
    'image_path' => 'uploads/' . $filename
];

echo json_encode(['success' => true, 'data' => $result]);
?>