<?php
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['image'])) {
    echo json_encode(['success' => false, 'error' => 'Tiada gambar']);
    exit;
}

$image = $_FILES['image'];

if ($image['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Ralat muat naik gambar']);
    exit;
}

$allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
if (!in_array($image['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Jenis fail tidak dibenarkan']);
    exit;
}

$maxSize = 10 * 1024 * 1024;
if ($image['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'Saiz gambar terlalu besar (maks 10MB)']);
    exit;
}

$imageData = file_get_contents($image['tmp_name']);
$base64Image = base64_encode($imageData);

$prompt = <<<'EOT'
You are a medical monitor OCR assistant. Analyze the medical monitor image and extract the following vital signs as integers. Return ONLY a valid JSON object without any extra text or markdown.

Fields to extract:
- systolic: systolic blood pressure (top number)
- diastolic: diastolic blood pressure (bottom number)
- map: mean arterial pressure
- pr: pulse rate (heart rate)
- spo2: oxygen saturation percentage (can be null if not visible)

Example output:
{"systolic": 120, "diastolic": 80, "map": 90, "pr": 75, "spo2": 98}
EOT;

$ollamaUrl = 'http://192.168.1.50:11434/api/generate';

$postData = [
    'model' => 'gemma4:e2b',
    'prompt' => $prompt,
    'images' => [$base64Image],
    'stream' => false,
    'format' => 'json'
];

$ch = curl_init($ollamaUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 120);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(['success' => false, 'error' => 'Sambungan Ollama gagal: ' . $curlError]);
    exit;
}

if ($httpCode !== 200) {
    echo json_encode(['success' => false, 'error' => 'Ralat Ollama (HTTP ' . $httpCode . ')']);
    exit;
}

$ollamaData = json_decode($response, true);

if (!$ollamaData) {
    echo json_encode(['success' => false, 'error' => 'Respons Ollama tidak sah']);
    exit;
}

if (!isset($ollamaData['response'])) {
    echo json_encode(['success' => false, 'error' => 'Tiada output daripada Ollama']);
    exit;
}

$responseText = trim($ollamaData['response']);
$extractedData = json_decode($responseText, true);

if (!$extractedData) {
    $jsonMatch = [];
    if (preg_match('/\{[\s\S]*\}/', $responseText, $jsonMatch)) {
        $extractedData = json_decode(trim($jsonMatch[0]), true);
    }
    
    if (!$extractedData) {
        echo json_encode(['success' => false, 'error' => 'Gagal parse JSON daripada Ollama']);
        exit;
    }
}

function safeInt($value, $default = null) {
    if (is_int($value)) return $value;
    if (is_string($value) && ctype_digit($value)) return (int)$value;
    if (is_numeric($value)) return (int)$value;
    return $default;
}

$systolic = safeInt($extractedData['systolic'] ?? null);
$diastolic = safeInt($extractedData['diastolic'] ?? null);
$map = safeInt($extractedData['map'] ?? null);
$pr = safeInt($extractedData['pr'] ?? null);
$spo2 = safeInt($extractedData['spo2'] ?? null);

$missing = [];
if ($systolic === null) $missing[] = 'systolic';
if ($diastolic === null) $missing[] = 'diastolic';
if ($map === null) $missing[] = 'map';
if ($pr === null) $missing[] = 'pr';

if (!empty($missing)) {
    echo json_encode(['success' => false, 'error' => 'Medan tidak lengkap: ' . implode(', ', $missing)]);
    exit;
}

echo json_encode([
    'success' => true,
    'data' => [
        'systolic' => $systolic,
        'diastolic' => $diastolic,
        'map' => $map,
        'pr' => $pr,
        'spo2' => $spo2
    ]
]);
?>
