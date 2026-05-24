<?php
require 'config.php';
require_login();

$user_id = $_SESSION['user_id'];
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

$stmt = $pdo->prepare("
    SELECT p.* 
    FROM patients p
    WHERE p.id = ? 
    AND (p.status = 'approved' OR ? = 'admin')
    AND (? = 'admin' OR p.created_by = ? OR p.id IN (SELECT patient_id FROM patient_collaborators WHERE user_id = ?))
");
$stmt->execute([$patient_id, $_SESSION['user_role'], $_SESSION['user_role'], $user_id, $user_id]);
$patient = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$patient) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?php echo htmlspecialchars($patient['name']); ?> - Status</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .vital-card {
            transition: transform 0.2s ease;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-lg mx-auto px-4 pt-6">
        <div class="flex items-center justify-between mb-6">
            <a href="dashboard.php" class="p-2 rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div class="text-center flex-1">
                <p class="text-gray-500 text-sm">Status Pesakit</p>
                <h1 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($patient['name']); ?></h1>
            </div>
            <div class="w-10"></div>
        </div>

        <div id="latestCard" class="bg-white rounded-3xl shadow-sm p-6 mb-6">
            <div class="text-center mb-6">
                <span class="text-gray-400 text-sm">📅 Bacaan Terkini</span>
                <div id="timestamp" class="text-lg font-semibold text-gray-700 mt-1">--:--</div>
            </div>
            
            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="vital-card bg-blue-50 rounded-2xl p-4 text-center">
                    <div class="text-3xl font-bold text-blue-600" id="bp">--/--</div>
                    <div class="text-xs text-gray-500 mt-1">Tekanan Darah</div>
                </div>
                <div class="vital-card bg-green-50 rounded-2xl p-4 text-center">
                    <div class="text-3xl font-bold text-green-600" id="pr">--</div>
                    <div class="text-xs text-gray-500 mt-1">Nadi (bpm)</div>
                </div>
                <div class="vital-card bg-purple-50 rounded-2xl p-4 text-center">
                    <div class="text-3xl font-bold text-purple-600" id="spo2">--</div>
                    <div class="text-xs text-gray-500 mt-1">Oksigen (%)</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="vital-card bg-orange-50 rounded-2xl p-4 text-center">
                    <div class="text-2xl font-bold text-orange-600" id="temperature">--</div>
                    <div class="text-xs text-gray-500 mt-1">Suhu (°C)</div>
                </div>
                <div class="vital-card bg-cyan-50 rounded-2xl p-4 text-center">
                    <div class="text-2xl font-bold text-cyan-600" id="respiratory_rate">--</div>
                    <div class="text-xs text-gray-500 mt-1">Kadar Pernafasan</div>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-4 mb-6">
                <div class="vital-card bg-teal-50 rounded-2xl p-4 text-center">
                    <div class="text-xl font-bold text-teal-600" id="etco2">--</div>
                    <div class="text-xs text-gray-500 mt-1">EtCO₂ (mmHg)</div>
                </div>
                <div class="vital-card bg-indigo-50 rounded-2xl p-4 text-center">
                    <div class="text-xl font-bold text-indigo-600" id="cvp">--</div>
                    <div class="text-xs text-gray-500 mt-1">CVP (mmHg)</div>
                </div>
                <div class="vital-card bg-pink-50 rounded-2xl p-4 text-center">
                    <div class="text-xl font-bold text-pink-600" id="icp">--</div>
                    <div class="text-xs text-gray-500 mt-1">ICP (mmHg)</div>
                </div>
            </div>
            
            <div class="space-y-3">
                <div id="bpStatus" class="p-3 rounded-xl bg-gray-50 text-sm"></div>
                <div id="prStatus" class="p-3 rounded-xl bg-gray-50 text-sm"></div>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm p-5 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                    <span class="text-xl">📈</span>
                    Trend Bacaan
                </h3>
                <div class="flex gap-2">
                    <button id="btn5" onclick="switchHistory(5)" class="px-3 py-1 text-sm rounded-full bg-blue-600 text-white">5</button>
                    <button id="btn10" onclick="switchHistory(10)" class="px-3 py-1 text-sm rounded-full bg-gray-200 text-gray-600">10</button>
                    <button id="btn25" onclick="switchHistory(25)" class="px-3 py-1 text-sm rounded-full bg-gray-200 text-gray-600">25</button>
                </div>
            </div>
            <canvas id="trendChart" height="200"></canvas>
        </div>

        <div class="text-center text-gray-400 text-xs pb-4">
            Data dikemaskini setiap 30 saat<br>
            Sebarang pertanyaan, sila hubungi wad
        </div>
    </div>

    <div class="bottom-nav">
        <div class="max-w-lg mx-auto flex items-center justify-around py-3">
            <a href="dashboard.php" class="nav-item flex flex-col items-center gap-1 px-4 py-2 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="text-xs font-medium">Home</span>
            </a>
            <a href="admin.php?patient_id=<?php echo $patient_id; ?>" class="nav-item flex flex-col items-center gap-1 px-4 py-2 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                <span class="text-xs font-medium">Input</span>
            </a>
        </div>
    </div>

    <script>
        let chart;
        let currentLimit = 5;
        const patientId = <?php echo $patient_id; ?>;

        async function loadData() {
            try {
                const latestRes = await fetch(`api/get_latest.php?patient_id=${patientId}`);
                const latest = await latestRes.json();
                
                if (latest) {
                    document.getElementById('bp').innerText = `${latest.systolic}/${latest.diastolic}`;
                    document.getElementById('pr').innerText = latest.pr;
                    document.getElementById('spo2').innerText = latest.spo2 || '--';
                    document.getElementById('temperature').innerText = latest.temperature ? latest.temperature.toFixed(1) : '--';
                    document.getElementById('respiratory_rate').innerText = latest.respiratory_rate || '--';
                    document.getElementById('etco2').innerText = latest.etco2 || '--';
                    document.getElementById('cvp').innerText = latest.cvp || '--';
                    document.getElementById('icp').innerText = latest.icp || '--';
                    
                    const date = new Date(latest.recorded_at);
                    const months = ['Jan', 'Feb', 'Mac', 'Apr', 'Mei', 'Jun', 'Jul', 'Ogos', 'Sep', 'Okt', 'Nov', 'Dis'];
                    const formattedDate = `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}, ${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
                    document.getElementById('timestamp').innerHTML = `<span class="text-gray-500">📅 ${formattedDate}</span>`;
                    
                    let bpMsg = '', prMsg = '';
                    
                    if (latest.map < 70) bpMsg = '⚠️ Tekanan darah rendah. Perlu perhatian.';
                    else if (latest.map > 100) bpMsg = '⚠️ Tekanan darah tinggi. Perlu perhatian.';
                    else bpMsg = '✅ Tekanan darah dalam julat sihat.';
                    
                    if (latest.pr < 60) prMsg = '😴 Nadi perlahan.';
                    else if (latest.pr > 100) prMsg = '🏃 Nadi laju.';
                    else prMsg = '😊 Nadi normal.';
                    
                    document.getElementById('bpStatus').innerHTML = bpMsg;
                    document.getElementById('prStatus').innerHTML = prMsg;
                }
                
                const historyRes = await fetch(`api/get_history.php?patient_id=${patientId}&limit=${currentLimit}`);
                const history = await historyRes.json();
                
                if (history.length > 0 && chart) {
                    chart.data.labels = history.map(h => {
                        const time = h.recorded_at.substring(11, 16);
                        return time;
                    });
                    chart.data.datasets[0].data = history.map(h => h.map);
                    chart.data.datasets[1].data = history.map(h => h.pr);
                    chart.update();
                }
            } catch (error) {
                console.error('Error loading data:', error);
            }
        }

        function switchHistory(limit) {
            currentLimit = limit;
            document.getElementById('btn5').className = `px-3 py-1 text-sm rounded-full ${limit === 5 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600'}`;
            document.getElementById('btn10').className = `px-3 py-1 text-sm rounded-full ${limit === 10 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600'}`;
            document.getElementById('btn25').className = `px-3 py-1 text-sm rounded-full ${limit === 25 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600'}`;
            loadData();
        }
        
        const ctx = document.getElementById('trendChart').getContext('2d');
        chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    { 
                        label: 'MAP (mmHg)', 
                        data: [], 
                        borderColor: '#3b82f6', 
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.3, 
                        fill: true,
                        pointRadius: 4
                    },
                    { 
                        label: 'Nadi (bpm)', 
                        data: [], 
                        borderColor: '#10b981', 
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.3, 
                        fill: true,
                        pointRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { 
                    legend: { 
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    } 
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        grid: {
                            color: 'rgba(0,0,0,0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        
        loadData();
        setInterval(loadData, 30000);
    </script>
</body>
</html>
