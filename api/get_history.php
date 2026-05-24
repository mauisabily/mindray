<?php
require_once '../config.php';
require_login();

$user_id = $_SESSION['user_id'];
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

$stmt = $pdo->prepare("
    SELECT pr.recorded_at, pr.systolic, pr.diastolic, pr.map, pr.pr, pr.spo2 
    FROM patient_readings pr
    JOIN patients p ON pr.patient_id = p.id
    WHERE pr.patient_id = ? 
    AND (? = 'admin' OR p.status = 'approved')
    AND (? = 'admin' OR p.created_by = ? OR p.id IN (SELECT patient_id FROM patient_collaborators WHERE user_id = ?))
    ORDER BY pr.recorded_at DESC 
    LIMIT 5
");
$stmt->execute([$patient_id, $_SESSION['user_role'], $_SESSION['user_role'], $user_id, $user_id]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(array_reverse($history));
?>
