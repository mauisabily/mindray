<?php
$host = 'localhost';
$dbname = 'sql_monitor';
$username = 'sql_monitor';
$password = 'fe064904f60a38';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// GANTI DENGAN API KEY ANDA - Daftar percuma di https://ocr.space/OCRAPI
define('OCR_API_KEY', 'K82215081688957'); // <-- Masukkan API key sebenar di sini

// Jika tiada API key, sistem akan beri mesej error
?>