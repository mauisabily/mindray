<?php
require_once '../config.php';

$sql = "SELECT recorded_at, systolic, diastolic, map, pr, spo2 
        FROM patient_readings 
        ORDER BY recorded_at DESC LIMIT 5";
$stmt = $pdo->query($sql);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(array_reverse($history));
?>