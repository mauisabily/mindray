<?php
require 'config.php';
require_login();

$user_id = $_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_patient'])) {
    $patient_name = trim($_POST['patient_name']);
    if (!empty($patient_name)) {
        $stmt = $pdo->prepare("INSERT INTO patients (name, created_by) VALUES (?, ?)");
        if ($stmt->execute([$patient_name, $user_id])) {
            $message = 'Pesakit berjaya ditambah! Sila tunggu kelulusan admin.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['invite_user'])) {
    $patient_id = intval($_POST['patient_id']);
    $invite_email = trim($_POST['invite_email']);
    
    $stmt = $pdo->prepare("SELECT id, status FROM patients WHERE id = ? AND created_by = ?");
    $stmt->execute([$patient_id, $user_id]);
    $patient = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($patient && $patient['status'] === 'approved') {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$invite_email]);
        $invited_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($invited_user) {
            try {
                $stmt = $pdo->prepare("INSERT INTO patient_collaborators (patient_id, user_id, invited_by) VALUES (?, ?, ?)");
                $stmt->execute([$patient_id, $invited_user['id'], $user_id]);
                $message = 'Pengguna berjaya dijemput!';
            } catch (PDOException $e) {
                $message = 'Pengguna sudah dijemput untuk pesakit ini';
            }
        } else {
            $message = 'Pengguna dengan email tersebut tidak wujud';
        }
    }
}

if (is_admin()) {
    $stmt = $pdo->query("
        SELECT p.*, 
               (SELECT COUNT(*) FROM patient_readings WHERE patient_id = p.id) as reading_count,
               (SELECT recorded_at FROM patient_readings WHERE patient_id = p.id ORDER BY recorded_at DESC LIMIT 1) as last_reading
        FROM patients p
        ORDER BY p.created_at DESC
    ");
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               (SELECT COUNT(*) FROM patient_readings WHERE patient_id = p.id) as reading_count,
               (SELECT recorded_at FROM patient_readings WHERE patient_id = p.id ORDER BY recorded_at DESC LIMIT 1) as last_reading
        FROM patients p
        WHERE p.created_by = ? 
        OR p.id IN (SELECT patient_id FROM patient_collaborators WHERE user_id = ?)
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user_id, $user_id]);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard - Sistem Pemantauan Pesakit</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        * { font-family: 'Inter', sans-serif; }
        body { 
            padding-bottom: 80px; 
            -webkit-tap-highlight-color: transparent;
        }
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
            z-index: 50;
        }
        .nav-item {
            transition: all 0.2s ease;
        }
        .nav-item.active {
            color: #2563eb;
        }
        .patient-card {
            transition: transform 0.2s ease;
        }
        .patient-card:active {
            transform: scale(0.98);
        }
        .status-badge {
            font-size: 10px;
            padding: 4px 8px;
        }
        .fab {
            position: fixed;
            bottom: 90px;
            right: 16px;
            z-index: 40;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 60;
            align-items: flex-end;
            justify-content: center;
        }
        .modal.active {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 20px 20px 0 0;
            width: 100%;
            max-height: 80vh;
            padding: 24px;
            animation: slideUp 0.3s ease;
        }
        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-lg mx-auto px-4 pt-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <p class="text-gray-500 text-sm">Selamat datang,</p>
                <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($_SESSION['user_name']); ?> 👋</h1>
            </div>
            <a href="logout.php" class="p-2 rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </a>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-5 text-white">
                <div class="text-3xl mb-2">🏥</div>
                <p class="text-blue-100 text-sm">Jumlah Pesakit</p>
                <p class="text-3xl font-bold"><?php echo count($patients); ?></p>
            </div>
            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-2xl p-5 text-white">
                <div class="text-3xl mb-2">✅</div>
                <p class="text-green-100 text-sm">Diluluskan</p>
                <p class="text-3xl font-bold">
                    <?php 
                    $approved = 0;
                    foreach ($patients as $p) {
                        if ($p['status'] === 'approved') $approved++;
                    }
                    echo $approved;
                    ?>
                </p>
            </div>
        </div>

        <h2 class="text-lg font-semibold text-gray-800 mb-4">Senarai Pesakit</h2>
        
        <div class="space-y-3">
            <?php if (empty($patients)): ?>
                <div class="bg-white rounded-2xl p-8 text-center">
                    <div class="text-6xl mb-4">🏥</div>
                    <p class="text-gray-500">Tiada pesakit lagi</p>
                    <p class="text-gray-400 text-sm mt-1">Tekan butang + untuk tambah</p>
                </div>
            <?php else: ?>
                <?php foreach ($patients as $patient): ?>
                    <div class="bg-white rounded-2xl p-4 shadow-sm patient-card">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="font-semibold text-gray-900 text-lg"><?php echo htmlspecialchars($patient['name']); ?></h3>
                                    <span class="status-badge rounded-full font-medium <?php 
                                        echo $patient['status'] === 'approved' ? 'bg-green-100 text-green-700' : 
                                             ($patient['status'] === 'pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'); 
                                    ?>">
                                        <?php echo htmlspecialchars($patient['status']); ?>
                                    </span>
                                </div>
                                <p class="text-gray-500 text-sm">
                                    <?php echo $patient['reading_count']; ?> bacaan
                                    <?php if ($patient['last_reading']): ?>
                                        • <?php echo date('d/m H:i', strtotime($patient['last_reading'])); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?php if ($patient['status'] === 'approved'): ?>
                                <div class="flex gap-2">
                                    <a href="index.php?patient_id=<?php echo $patient['id']; ?>" class="p-3 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                    <a href="admin.php?patient_id=<?php echo $patient['id']; ?>" class="p-3 bg-green-50 text-green-600 rounded-xl hover:bg-green-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                        </svg>
                                    </a>
                                    <a href="admin.php?patient_id=<?php echo $patient['id']; ?>" class="p-3 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-100" title="Konfigurasi Telegram">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 11.944 0zm5.462 8.198c.112 2.933.164 5.866.164 8.798 0 .443-.197.64-.637.403-3.493-2.577-5.812-4.27-7.073-5.113-.239-.16-.364-.343-.392-.625-.165-1.652-.26-3.305-.26-4.957 0-.386.193-.58.578-.58.301 0 .588.174.861.524 1.217 1.56 2.813 3.608 4.788 6.145.21.27.368.374.609.315 1.733-.427 2.905-1.277 3.517-2.547.136-.283.186-.5.064-.736z"/>
                                        </svg>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($patient['created_by'] == $user_id && $patient['status'] === 'approved'): ?>
                            <div class="mt-4 pt-4 border-t border-gray-100">
                                <form method="POST" class="flex gap-2">
                                    <input type="hidden" name="patient_id" value="<?php echo $patient['id']; ?>">
                                    <input type="email" name="invite_email" required class="flex-1 px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Email untuk dijemput">
                                    <button type="submit" name="invite_user" class="px-4 py-2 bg-purple-500 text-white rounded-xl text-sm font-medium hover:bg-purple-600">
                                        Jemput
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <button onclick="document.getElementById('addModal').classList.add('active')" class="fab w-14 h-14 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-full shadow-lg flex items-center justify-center hover:shadow-xl active:scale-95 transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
    </button>

    <div class="bottom-nav">
        <div class="max-w-lg mx-auto flex items-center justify-around py-3">
            <a href="dashboard.php" class="nav-item flex flex-col items-center gap-1 px-4 py-2 active">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="text-xs font-medium">Home</span>
            </a>
            <?php if (is_admin()): ?>
                <a href="admin_dashboard.php" class="nav-item flex flex-col items-center gap-1 px-4 py-2 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span class="text-xs font-medium">Admin</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <div id="addModal" class="modal" onclick="if(event.target === this) this.classList.remove('active')">
        <div class="modal-content">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-gray-900">Tambah Pesakit Baru</h3>
                <button onclick="document.getElementById('addModal').classList.remove('active')" class="p-2 rounded-full hover:bg-gray-100">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <form method="POST">
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2">Nama Pesakit</label>
                    <input type="text" name="patient_name" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Masukkan nama pesakit">
                </div>
                <button type="submit" name="add_patient" class="w-full bg-gradient-to-r from-blue-500 to-blue-600 text-white py-3 rounded-xl font-semibold">
                    Tambah Pesakit
                </button>
            </form>
        </div>
    </div>
</body>
</html>
