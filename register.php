<?php
require 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $error = 'Kata laluan tidak sepadan';
    } elseif (strlen($password) < 6) {
        $error = 'Kata laluan mestilah sekurang-kurangnya 6 aksara';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'Email sudah didaftarkan';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
            
            if ($stmt->execute([$name, $email, $hashed_password])) {
                $success = 'Pendaftaran berjaya! Sila log masuk';
            } else {
                $error = 'Ralat semasa pendaftaran';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akaun - Sistem Pemantauan Pesakit</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-lg p-8">
        <div class="text-center mb-8">
            <div class="text-5xl mb-4">🏥</div>
            <h1 class="text-2xl font-bold text-gray-800">Daftar Akaun</h1>
            <p class="text-gray-500 mt-2">Sistem Pemantauan Pesakit</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-6 text-center">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-50 text-green-600 p-3 rounded-lg mb-6 text-center">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <div>
                <label class="block text-gray-700 font-medium mb-2">Nama</label>
                <input type="text" name="name" required class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-400 focus:border-transparent" placeholder="Nama penuh anda">
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">Email</label>
                <input type="email" name="email" required class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-400 focus:border-transparent" placeholder="contoh@email.com">
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">Kata Laluan</label>
                <input type="password" name="password" required class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-400 focus:border-transparent" placeholder="Sekurang-kurangnya 6 aksara">
            </div>
            <div>
                <label class="block text-gray-700 font-medium mb-2">Sahkan Kata Laluan</label>
                <input type="password" name="confirm_password" required class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-400 focus:border-transparent" placeholder="Masukkan semula kata laluan">
            </div>
            <button type="submit" class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white py-3 rounded-xl font-semibold hover:opacity-90 transition">
                Daftar Akaun
            </button>
        </form>

        <div class="mt-6 text-center text-gray-500 text-sm">
            Sudah ada akaun? <a href="login.php" class="text-blue-600 font-medium hover:underline">Log masuk di sini</a>
        </div>
    </div>
</body>
</html>
