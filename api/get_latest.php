<?php
require_once '../config.php';

$sql = "SELECT * FROM patient_readings ORDER BY recorded_at DESC LIMIT 1";
$stmt = $pdo->query($sql);
$latest = $stmt->fetch(PDO::FETCH_ASSOC);

if ($latest) {
    // Interpretasi layman
    if ($latest['map'] < 70) {
        $latest['status_map'] = '⚠️ Tekanan darah rendah, kena perhatian.';
    } elseif ($latest['map'] > 100) {
        $latest['status_map'] = '⚠️ Tekanan darah tinggi.';
    } else {
        $latest['status_map'] = '✅ Tekanan darah dalam julat sihat.';
    }
    
    if ($latest['pr'] < 60) {
        $latest['status_pr'] = 'Nadi agak perlahan.';
    } elseif ($latest['pr'] > 100) {
        $latest['status_pr'] = 'Nadi laju.';
    } else {
        $latest['status_pr'] = 'Nadi normal.';
    }
}

echo json_encode($latest ?: null);
?>