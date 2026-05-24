<?php
require_once '../config.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$patient_id = intval($input['patient_id'] ?? 0);
$bot_token = trim($input['bot_token'] ?? '');
$chat_id = trim($input['chat_id'] ?? '');

if (!$patient_id || !$bot_token || !$chat_id) {
    echo json_encode(['success' => false, 'error' => 'Sila lengkapkan semua medan']);
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT id FROM patients 
    WHERE id = ? 
    AND (? = 'admin' OR status = 'approved')
    AND (? = 'admin' OR created_by = ? OR id IN (SELECT patient_id FROM patient_collaborators WHERE user_id = ?))
");
$stmt->execute([$patient_id, $_SESSION['user_role'], $_SESSION['user_role'], $user_id, $user_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    echo json_encode(['success' => false, 'error' => 'Tiada akses kepada pesakit ini']);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM telegram_config WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    $stmt = $pdo->prepare("UPDATE telegram_config SET bot_token = ?, chat_id = ? WHERE patient_id = ?");
    $stmt->execute([$bot_token, $chat_id, $patient_id]);
} else {
    $stmt = $pdo->prepare("INSERT INTO telegram_config (patient_id, bot_token, chat_id) VALUES (?, ?, ?)");
    $stmt->execute([$patient_id, $bot_token, $chat_id]);
}

echo json_encode(['success' => true]);
?>
