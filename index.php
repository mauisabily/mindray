<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pesakit - Keluarga</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="max-w-md mx-auto p-4">
        <!-- Header -->
        <div class="bg-blue-600 text-white rounded-2xl p-6 mb-6 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">🏥 Status Pesakit</h1>
                    <p class="text-blue-100 mt-1">Maklumat terkini untuk keluarga</p>
                </div>
                <div class="text-right">
                    <div class="text-3xl">❤️</div>
                </div>
            </div>
        </div>

        <!-- Latest Reading Card -->
        <div id="latestCard" class="bg-white rounded-2xl shadow-lg p-6 mb-6">
            <div class="text-center mb-4">
                <span class="text-gray-500 text-sm">📅 Bacaan Terkini</span>
                <div id="timestamp" class="text-lg font-semibold text-gray-700">--:--</div>
            </div>
            
            <div class="grid grid-cols-3 gap-4 text-center mb-6">
                <div>
                    <div class="text-2xl font-bold text-blue-600" id="bp">--/--</div>
                    <div class="text-xs text-gray-500">Tekanan Darah</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-green-600" id="pr">--</div>
                    <div class="text-xs text-gray-500">Nadi (bpm)</div>
                </div>
                <div>
                    <div class="text-2xl font-bold text-purple-600" id="spo2">--</div>
                    <div class="text-xs text-gray-500">Oksigen</div>
                </div>
            </div>
            
            <div class="space-y-2 text-sm">
                <div id="bpStatus" class="p-2 rounded-lg bg-gray-50">--</div>
                <div id="prStatus" class="p-2 rounded-lg bg-gray-50">--</div>
            </div>
        </div>

        <!-- Trend Chart -->
        <div class="bg-white rounded-2xl shadow-lg p-4 mb-6">
            <h3 class="font-semibold text-gray-700 mb-3">📈 Trend 5 Bacaan Terakhir</h3>
            <canvas id="trendChart" height="200"></canvas>
        </div>

        <!-- Info Footer -->
        <div class="text-center text-gray-400 text-xs p-4">
            Data dikemaskini secara berkala oleh pihak hospital.<br>
            Sebarang pertanyaan, sila hubungi wad.
        </div>
    </div>

    <script>
        let chart;

        async function loadData() {
            try {
                // Load latest
                const latestRes = await fetch('api/get_latest.php');
                const latest = await latestRes.json();
                
                if (latest) {
                    document.getElementById('bp').innerText = `${latest.systolic}/${latest.diastolic}`;
                    document.getElementById('pr').innerText = latest.pr;
                    document.getElementById('spo2').innerText = latest.spo2 || '--';
                    document.getElementById('timestamp').innerText = latest.recorded_at;
                    document.getElementById('bpStatus').innerHTML = latest.status_map;
                    document.getElementById('prStatus').innerHTML = latest.status_pr;
                }
                
                // Load history for chart
                const historyRes = await fetch('api/get_history.php');
                const history = await historyRes.json();
                
                if (history.length > 0 && chart) {
                    chart.data.labels = history.map(h => h.recorded_at.substring(11, 16));
                    chart.data.datasets[0].data = history.map(h => h.map);
                    chart.data.datasets[1].data = history.map(h => h.pr);
                    chart.update();
                }
            } catch (error) {
                console.error('Error loading data:', error);
            }
        }
        
        // Init chart
        const ctx = document.getElementById('trendChart').getContext('2d');
        chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    { label: 'MAP (mmHg)', data: [], borderColor: '#3b82f6', tension: 0.3, fill: false },
                    { label: 'Nadi (bpm)', data: [], borderColor: '#10b981', tension: 0.3, fill: false }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
        
        loadData();
        setInterval(loadData, 30000); // Refresh every 30 seconds
<!-- Dalam <script> di index.php, ganti fungsi loadData() dengan ini -->

async function loadData() {
    try {
        const latestRes = await fetch('api/get_latest.php');
        const latest = await latestRes.json();
        
        if (latest) {
            document.getElementById('bp').innerText = `${latest.systolic}/${latest.diastolic}`;
            document.getElementById('pr').innerText = latest.pr;
            document.getElementById('spo2').innerText = latest.spo2 || '--';
            
            // Format datetime
            const date = new Date(latest.recorded_at);
            const months = ['Jan', 'Feb', 'Mac', 'Apr', 'Mei', 'Jun', 'Jul', 'Ogos', 'Sep', 'Okt', 'Nov', 'Dis'];
            const formattedDate = `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}, ${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
            document.getElementById('timestamp').innerHTML = `<span class="text-gray-500">📅 ${formattedDate}</span>`;
            
            // Status message with emoji
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
        
        // Load history for chart
        const historyRes = await fetch('api/get_history.php');
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
    </script>
</body>
</html>