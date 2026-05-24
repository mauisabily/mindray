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
You are a medical monitor OCR assistant for Mindray uMEC10. Analyze the image very carefully and extract the EXACT values from their specific locations.

CRITICAL LOCATIONS ON THIS MONITOR (DO NOT MIX UP VALUES):

1. NIBP area (bottom right of screen):
   - Shows as "SYS/DIA (MAP)" like "119/72 (80)"
   - systolic = the FIRST number before / (e.g., 119)
   - diastolic = the SECOND number after / (e.g., 72)
   - map = the number in PARENTHESES (e.g., 80)

2. SpO2 area (right side, large number):
   - spo2 = the large number shown with SpO2 label (e.g., 100)

3. PR area (in the list or next to SpO2):
   - pr = the number labeled PR (pulse rate) from the list (e.g., 80, 82, etc.)

4. Additional vital signs (if visible):
   - temperature: body temperature in °C
   - respiratory_rate: respiratory rate (breaths per minute)
   - etco2: End-tidal CO2 in mmHg
   - cvp: Central Venous Pressure in mmHg
   - icp: Intracranial Pressure in mmHg

RETURN ONLY A VALID JSON OBJECT, NO OTHER TEXT:
{
  "systolic": number or null,
  "diastolic": number or null,
  "map": number or null,
  "pr": number or null,
  "spo2": number or null,
  "temperature": number or null,
  "respiratory_rate": number or null,
  "etco2": number or null,
  "cvp": number or null,
  "icp": number or null
}
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
