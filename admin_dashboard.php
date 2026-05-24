<?php
require 'config.php';
require_admin();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_patient'])) {
    $patient_id = intval($_POST['patient_id']);
    $stmt = $pdo->prepare("UPDATE patients SET status = 'approved' WHERE id = ?");
    if ($stmt->execute([$patient_id])) {
        $message = 'Pesakit berjaya diluluskan!';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_patient'])) {
    $patient_id = intval($_POST['patient_id']);
    $stmt = $pdo->prepare("UPDATE patients SET status = 'rejected' WHERE id = ?");
    if ($stmt->execute([$patient_id])) {
        $message = 'Pesakit berjaya ditolak!';
    }
}

$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT p.*, u.name as creator_name 
    FROM patients p 
    JOIN users u ON p.created_by = u.id 
    ORDER BY p.created_at DESC
");
$patients = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT COUNT(*) as count FROM patient_readings");
$readings_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sistem Pemantauan Pesakit</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="max-w-6xl mx-auto p-4 pb-8">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">🔐 Admin Dashboard</h1>
                <p class="text-gray-500">Selamat datang, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
            </div>
            <div class="flex gap-3">
                <a href="dashboard.php" class="bg-blue-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-blue-600 transition">
                    User Dashboard
                </a>
                <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-red-600 transition">
                    Log Keluar
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-50 text-green-600 p-3 rounded-lg mb-6 text-center">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="text-4xl mb-2">👥</div>
                <h3 class="text-3xl font-bold text-gray-800"><?php echo count($users); ?></h3>
                <p class="text-gray-500">Jumlah Pengguna</p>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="text-4xl mb-2">🏥</div>
                <h3 class="text-3xl font-bold text-gray-800"><?php echo count($patients); ?></h3>
                <p class="text-gray-500">Jumlah Pesakit</p>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="text-4xl mb-2">⏳</div>
                <?php
                $pending_count = 0;
                foreach ($patients as $p) {
                    if ($p['status'] === 'pending') $pending_count++;
                }
                ?>
                <h3 class="text-3xl font-bold text-yellow-600"><?php echo $pending_count; ?></h3>
                <p class="text-gray-500">Menunggu Kelulusan</p>
            </div>
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <div class="text-4xl mb-2">📊</div>
                <h3 class="text-3xl font-bold text-gray-800"><?php echo $readings_count; ?></h3>
                <p class="text-gray-500">Jumlah Bacaan</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">⏳ Pesakit Menunggu Kelulusan</h2>
            <?php
            $pending_patients = array_filter($patients, function($p) { return $p['status'] === 'pending'; });
            if (empty($pending_patients)):
            ?>
                <div class="text-center text-gray-500 py-8">Tiada pesakit menunggu kelulusan</div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($pending_patients as $patient): ?>
                        <div class="flex items-center justify-between p-4 border rounded-xl">
                            <div>
                                <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($patient['name']); ?></h4>
                                <p class="text-sm text-gray-500">Dicipta oleh: <?php echo htmlspecialchars($patient['creator_name']); ?></p>
                            </div>
                            <div class="flex gap-2">
                                <form method="POST">
                                    <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
                                    <button type="submit" name="approve_patient" class="bg-green-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-green-600">
                                        Lulus
                                    </button>
                                </form>
                                <form method="POST">
                                    <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
                                    <button type="submit" name="reject_patient" class="bg-red-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-red-600">
                                        Tolak
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">👥 Senarai Pengguna</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-gray-500 border-b">
                        <tr>
                            <th class="text-left py-3 px-4">Nama</th>
                            <th class="text-left py-3 px-4">Email</th>
                            <th class="text-left py-3 px-4">Peranan</th>
                            <th class="text-left py-3 px-4">Tarikh Daftar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-3 px-4 font-medium text-gray-800"><?php echo htmlspecialchars($user['name']); ?></td>
                                <td class="py-3 px-4 text-gray-600"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700'; ?>">
                                        <?php echo htmlspecialchars($user['role']); ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-gray-500"><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">🏥 Senarai Pesakit</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-gray-500 border-b">
                        <tr>
                            <th class="text-left py-3 px-4">Nama Pesakit</th>
                            <th class="text-left py-3 px-4">Dicipta Oleh</th>
                            <th class="text-left py-3 px-4">Status</th>
                            <th class="text-left py-3 px-4">Tarikh Dicipta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($patients as $patient): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-3 px-4 font-medium text-gray-800"><?php echo htmlspecialchars($patient['name']); ?></td>
                                <td class="py-3 px-4 text-gray-600"><?php echo htmlspecialchars($patient['creator_name']); ?></td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-1 rounded-full text-xs font-medium <?php 
                                        echo $patient['status'] === 'approved' ? 'bg-green-100 text-green-700' : 
                                             ($patient['status'] === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'); 
                                    ?>">
                                        <?php echo htmlspecialchars($patient['status']); ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-gray-500"><?php echo date('d/m/Y H:i', strtotime($patient['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
