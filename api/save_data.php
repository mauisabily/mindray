<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

require_once '../config.php';
require_login();

$user_id = $_SESSION['user_id'];

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

$required = ['patient_id', 'recorded_at', 'systolic', 'diastolic', 'map', 'pr'];
foreach ($required as $field) {
    if (!isset($input[$field]) || empty($input[$field])) {
        echo json_encode(['success' => false, 'error' => "Missing field: $field"]);
        exit;
    }
}

$patient_id = intval($input['patient_id']);
$recorded_at = $input['recorded_at'];
$systolic = (int)$input['systolic'];
$diastolic = (int)$input['diastolic'];
$map = (int)$input['map'];
$pr = (int)$input['pr'];
$spo2 = isset($input['spo2']) && !empty($input['spo2']) ? (int)$input['spo2'] : null;
$image_path = $input['image_path'] ?? null;

$stmt = $pdo->prepare("
    SELECT p.id, p.name 
    FROM patients p
    WHERE p.id = ? 
    AND (? = 'admin' OR p.status = 'approved')
    AND (? = 'admin' OR p.created_by = ? OR p.id IN (SELECT patient_id FROM patient_collaborators WHERE user_id = ?))
");
$stmt->execute([$patient_id, $_SESSION['user_role'], $_SESSION['user_role'], $user_id, $user_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    echo json_encode(['success' => false, 'error' => 'Anda tidak mempunyai akses kepada pesakit ini']);
    exit;
}

try {
    $sql = "INSERT INTO patient_readings (patient_id, recorded_at, systolic, diastolic, map, pr, spo2, image_path, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$patient_id, $recorded_at, $systolic, $diastolic, $map, $pr, $spo2, $image_path, $user_id]);
    $insertId = $pdo->lastInsertId();
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM telegram_config WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$telegram_config = $stmt->fetch(PDO::FETCH_ASSOC);

if ($telegram_config) {
    $date = new DateTime($recorded_at);
    $months = ['Jan', 'Feb', 'Mac', 'Apr', 'Mei', 'Jun', 'Jul', 'Ogos', 'Sep', 'Okt', 'Nov', 'Dis'];
    $formattedDate = $date->format('d') . ' ' . $months[$date->format('n') - 1] . ' ' . $date->format('Y') . ', ' . $date->format('H:i');
    
    $bp_status = '';
    if ($map < 70) {
        $bp_status = '⚠️ Tekanan darah rendah';
    } elseif ($map > 100) {
        $bp_status = '⚠️ Tekanan darah tinggi';
    } else {
        $bp_status = '✅ Tekanan darah normal';
    }
    
    $pr_status = '';
    if ($pr < 60) {
        $pr_status = '😴 Nadi perlahan';
    } elseif ($pr > 100) {
        $pr_status = '🏃 Nadi laju';
    } else {
        $pr_status = '😊 Nadi tenang';
    }
    
    $spo2_status = '';
    if ($spo2) {
        if ($spo2 < 95) {
            $spo2_status = '⚠️ Oksigen rendah';
        } else {
            $spo2_status = '✅ Oksigen baik';
        }
    }
    
    $message = "📋 *Bacaan Pesakit*\n";
    $message .= "👤 Nama: *" . $patient['name'] . "*\n";
    $message .= "📅 Masa: $formattedDate\n\n";
    $message .= "❤️ Tekanan Darah: $systolic/$diastolic mmHg\n";
    $message .= "🩸 MAP: $map mmHg\n";
    $message .= "   $bp_status\n\n";
    $message .= "💓 Nadi: $pr bpm\n";
    $message .= "   $pr_status\n";
    if ($spo2) {
        $message .= "\n🫁 Oksigen: $spo2%\n";
        $message .= "   $spo2_status\n";
    }
    
    $telegramUrl = "https://api.telegram.org/bot" . $telegram_config['bot_token'] . "/sendMessage";
    $postData = [
        'chat_id' => $telegram_config['chat_id'],
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $telegramUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

echo json_encode(['success' => true, 'id' => $insertId]);
?>
