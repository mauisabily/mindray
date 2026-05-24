<?php
// Turn off all error reporting for clean JSON output
error_reporting(0);
ini_set('display_errors', 0);

// Set JSON header FIRST before any output
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Debug: Log to file instead of output
function debugLog($msg) {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ' - ' . $msg . PHP_EOL, FILE_APPEND);
}

debugLog('save_data.php called');

// Check if config.php exists
if (!file_exists('../config.php')) {
    echo json_encode(['success' => false, 'error' => 'config.php not found']);
    exit;
}

require_once '../config.php';

// Check if PDO connection works
if (!isset($pdo) || !$pdo) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get JSON input
$inputJSON = file_get_contents('php://input');
debugLog('Input: ' . $inputJSON);

$input = json_decode($inputJSON, true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

// Validate required fields
$required = ['recorded_at', 'systolic', 'diastolic', 'map', 'pr'];
foreach ($required as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        echo json_encode(['success' => false, 'error' => "Missing field: $field"]);
        exit;
    }
}

$recorded_at = $input['recorded_at'];
$systolic = (int)$input['systolic'];
$diastolic = (int)$input['diastolic'];
$map = (int)$input['map'];
$pr = (int)$input['pr'];
$spo2 = isset($input['spo2']) && !empty($input['spo2']) ? (int)$input['spo2'] : null;
$image_path = $input['image_path'] ?? null;

// Insert into database
try {
    $sql = "INSERT INTO patient_readings (recorded_at, systolic, diastolic, map, pr, spo2, image_path) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$recorded_at, $systolic, $diastolic, $map, $pr, $spo2, $image_path]);
    $insertId = $pdo->lastInsertId();
    debugLog('Inserted ID: ' . $insertId);
} catch (PDOException $e) {
    debugLog('DB Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// Format date for message
$dateObj = new DateTime($recorded_at);
$formattedDate = $dateObj->format('H:i, j F Y');

// Status functions
function getBPStatus($systolic, $diastolic) {
    if ($systolic < 90 || $diastolic < 60) return "⚠️ Rendah";
    if ($systolic > 140 || $diastolic > 90) return "⚠️ Tinggi";
    return "✅ Normal";
}

function getPRStatus($pr) {
    if ($pr < 60) return "😴 Perlahan (Bradikardia)";
    if ($pr > 100) return "🏃 Cepat (Takikardia)";
    return "😊 Tenang";
}

function getSpO2Status($spo2) {
    if (!$spo2) return "";
    if ($spo2 < 90) return "⚠️ Rendah (Perlu Oksigen)";
    if ($spo2 < 95) return "⚡ Sederhana";
    return "✅ Baik";
}

$bpStatus = getBPStatus($systolic, $diastolic);
$prStatus = getPRStatus($pr);
$spo2Status = getSpO2Status($spo2);

// Build message
$message = "━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$message .= "🩺 *STATUS PESAKIT TERKINI*\n";
$message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
$message .= "❤️ *Tekanan Darah:* $systolic/$diastolic mmHg\n";
$message .= "   ↳ $bpStatus\n\n";
$message .= "💓 *Nadi:* $pr bpm\n";
$message .= "   ↳ $prStatus\n\n";
if ($spo2) {
    $message .= "🫁 *Oksigen:* $spo2%\n";
    $message .= "   ↳ $spo2Status\n\n";
}
$message .= "⏰ *Masa:* $formattedDate\n\n";
$message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$message .= "ℹ️ *Makluman:*\n";
$message .= "Jika nombor keluar dari julat normal,\n";
$message .= "staf wad akan menghubungi anda.\n\n";
$message .= "_Sistem Pemantauan Pesakit - Wad_";

// Send to Telegram
$telegramSent = false;
try {
    $stmt = $pdo->query("SELECT bot_token, chat_id FROM telegram_config LIMIT 1");
    $telegram = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($telegram && $telegram['bot_token'] && $telegram['bot_token'] != 'YOUR_BOT_TOKEN' && $telegram['chat_id']) {
        $url = "https://api.telegram.org/bot{$telegram['bot_token']}/sendMessage";
        $post = [
            'chat_id' => $telegram['chat_id'], 
            'text' => $message, 
            'parse_mode' => 'Markdown'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode == 200) {
            $telegramSent = true;
            debugLog('Telegram sent successfully');
        } else {
            debugLog('Telegram failed. HTTP: ' . $httpCode . ' Response: ' . $result);
        }
    } else {
        debugLog('Telegram not configured');
    }
} catch (Exception $e) {
    debugLog('Telegram error: ' . $e->getMessage());
}

// Return success response
echo json_encode([
    'success' => true, 
    'id' => $insertId,
    'telegram_sent' => $telegramSent
]);
?>