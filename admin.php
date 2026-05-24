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

$stmt = $pdo->prepare("SELECT * FROM telegram_config WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$telegram_config = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Input Data - <?php echo htmlspecialchars($patient['name']); ?></title>
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
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] {
            -moz-appearance: textfield;
        }
        .whatsapp-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
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
                <p class="text-gray-500 text-sm">Input Data</p>
                <h1 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($patient['name']); ?></h1>
            </div>
            <div class="w-10"></div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm p-5 mb-6">
            <div class="flex items-center justify-between cursor-pointer" onclick="toggleTelegramConfig()">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🤖</span>
                    <h2 class="text-lg font-semibold text-gray-800">Konfigurasi Telegram</h2>
                </div>
                <svg id="telegramArrow" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </div>
            
            <div id="telegramConfig" class="mt-4 hidden">
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2 text-sm">Bot Token</label>
                    <input type="text" id="telegramBotToken" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11" value="<?php echo htmlspecialchars($telegram_config['bot_token'] ?? ''); ?>">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2 text-sm">Chat ID</label>
                    <input type="text" id="telegramChatId" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="-123456789" value="<?php echo htmlspecialchars($telegram_config['chat_id'] ?? ''); ?>">
                </div>
                <button onclick="saveTelegramConfig()" class="w-full bg-gradient-to-r from-blue-500 to-blue-600 text-white py-3 rounded-xl font-medium hover:opacity-90">
                    💾 Simpan Konfigurasi
                </button>
                <div id="telegramMessage" class="mt-3 text-sm text-center"></div>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm p-5 mb-6">
            <div class="flex items-center gap-2 mb-6 pb-4 border-b border-gray-100">
                <span class="text-green-600 text-2xl">✏️</span>
                <h2 class="text-lg font-semibold text-gray-800">Input Bacaan Baru</h2>
            </div>
            
            <form id="dataForm" enctype="multipart/form-data">
                <input type="hidden" id="patient_id" value="<?php echo $patient['id']; ?>">
                
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-3 flex items-center gap-2">
                        <span class="text-xl">📷</span>
                        Gambar Monitor <span class="text-red-500">*</span>
                    </label>
                    <div class="border-2 border-dashed border-gray-300 rounded-2xl p-6 text-center hover:border-blue-400 transition">
                        <input type="file" id="imageInput" accept="image/*" capture="environment" class="hidden" required>
                        <div id="imagePreview" class="mb-4 hidden">
                            <img id="previewImg" class="rounded-xl border max-h-64 w-full object-contain bg-gray-50 mx-auto">
                            <button type="button" id="removeImageBtn" class="text-red-500 text-sm mt-3 font-medium">✖️ Ganti gambar</button>
                        </div>
                        <div id="uploadPlaceholder">
                            <div class="text-4xl mb-2">📸</div>
                            <p class="text-gray-600 mb-4">Ambil gambar monitor pesakit</p>
                            <div class="flex gap-3 justify-center">
                                <button type="button" id="cameraBtn" class="bg-blue-500 text-white px-6 py-3 rounded-xl font-medium hover:bg-blue-600">
                                    📸 Kamera
                                </button>
                                <button type="button" id="galleryBtn" class="bg-gray-100 text-gray-700 px-6 py-3 rounded-xl font-medium hover:bg-gray-200">
                                    🖼️ Galeri
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-3 flex items-center gap-2">
                        <span class="text-xl">🕒</span>
                        Masa Bacaan <span class="text-red-500">*</span>
                    </label>
                    <input type="datetime-local" id="datetime" class="w-full px-4 py-4 bg-gray-50 border border-gray-200 rounded-2xl focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-3 flex items-center gap-2">
                        <span class="text-xl">❤️</span>
                        Tekanan Darah (NIBP)
                    </label>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="text-xs text-gray-500 mb-2 block">Systolic <span class="text-red-500">*</span></label>
                            <input type="number" id="systolic" class="w-full px-4 py-4 bg-gray-50 border border-gray-200 rounded-2xl text-center text-2xl font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="117" required>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 mb-2 block">Diastolic <span class="text-red-500">*</span></label>
                            <input type="number" id="diastolic" class="w-full px-4 py-4 bg-gray-50 border border-gray-200 rounded-2xl text-center text-2xl font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="59" required>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 mb-2 block">MAP <span class="text-red-500">*</span></label>
                            <input type="number" id="map" class="w-full px-4 py-4 bg-gray-50 border border-gray-200 rounded-2xl text-center text-2xl font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="67" required>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-2">📝 MAP diisi manual berdasarkan bacaan monitor</p>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-8">
                    <div>
                        <label class="block text-gray-700 font-medium mb-3 flex items-center gap-2">
                            <span class="text-xl">💓</span>
                            Nadi (PR) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="pr" class="w-full px-4 py-4 bg-gray-50 border border-gray-200 rounded-2xl text-center text-2xl font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="65" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-3 flex items-center gap-2">
                            <span class="text-xl">🫁</span>
                            SpO₂ (%)
                        </label>
                        <input type="number" id="spo2" class="w-full px-4 py-4 bg-gray-50 border border-gray-200 rounded-2xl text-center text-2xl font-semibold focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="99">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <button type="submit" id="saveBtn" class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white py-4 rounded-2xl font-bold text-lg shadow-md active:scale-98 transition">
                        💾 Simpan Data
                    </button>
                    <button type="button" id="whatsappBtn" onclick="copyToWhatsApp()" disabled class="whatsapp-btn w-full bg-gradient-to-r from-green-600 to-green-700 text-white py-4 rounded-2xl font-bold text-lg shadow-md active:scale-98 transition">
                        💬 WhatsApp
                    </button>
                </div>
            </form>
            
            <div id="message" class="mt-4 text-center text-sm p-4 rounded-xl"></div>
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
            <a href="index.php?patient_id=<?php echo $patient_id; ?>" class="nav-item flex flex-col items-center gap-1 px-4 py-2 text-gray-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                </svg>
                <span class="text-xs font-medium">Status</span>
            </a>
        </div>
    </div>

    <script>
        let lastSavedData = null;
        const patientId = <?php echo $patient_id; ?>;
        
        function setDefaultDatetime() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            document.getElementById('datetime').value = `${year}-${month}-${day}T${hours}:${minutes}`;
        }
        
        function toggleTelegramConfig() {
            const config = document.getElementById('telegramConfig');
            const arrow = document.getElementById('telegramArrow');
            config.classList.toggle('hidden');
            arrow.classList.toggle('rotate-180');
        }
        
        async function saveTelegramConfig() {
            const botToken = document.getElementById('telegramBotToken').value.trim();
            const chatId = document.getElementById('telegramChatId').value.trim();
            const messageDiv = document.getElementById('telegramMessage');
            
            if (!botToken || !chatId) {
                messageDiv.innerHTML = '<span class="text-red-600">❌ Sila lengkapkan semua medan</span>';
                return;
            }
            
            try {
                const res = await fetch('api/save_telegram_config.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        patient_id: patientId,
                        bot_token: botToken,
                        chat_id: chatId
                    })
                });
                const data = await res.json();
                
                if (data.success) {
                    messageDiv.innerHTML = '<span class="text-green-600">✅ Konfigurasi berjaya disimpan!</span>';
                } else {
                    messageDiv.innerHTML = '<span class="text-red-600">❌ ' + data.error + '</span>';
                }
            } catch (err) {
                messageDiv.innerHTML = '<span class="text-red-600">❌ Ralat: ' + err.message + '</span>';
            }
        }
        
        const imageInput = document.getElementById('imageInput');
        const previewDiv = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        const uploadPlaceholder = document.getElementById('uploadPlaceholder');
        const saveBtn = document.getElementById('saveBtn');
        const whatsappBtn = document.getElementById('whatsappBtn');
        
        document.getElementById('cameraBtn').addEventListener('click', () => {
            imageInput.click();
        });
        
        document.getElementById('galleryBtn').addEventListener('click', () => {
            imageInput.click();
        });
        
        document.getElementById('removeImageBtn')?.addEventListener('click', () => {
            imageInput.value = '';
            previewDiv.classList.add('hidden');
            uploadPlaceholder.classList.remove('hidden');
            previewImg.src = '';
        });
        
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = async function(event) {
                    previewImg.src = event.target.result;
                    previewDiv.classList.remove('hidden');
                    uploadPlaceholder.classList.add('hidden');
                    
                    await scanOCR(file);
                };
                reader.readAsDataURL(file);
            }
        });
        
        async function scanOCR(file) {
            showMessage('🤖 Mengimbas gambar dengan AI...', 'blue');
            
            const formData = new FormData();
            formData.append('image', file);
            
            try {
                const res = await fetch('api/ocr.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                
                if (data.success) {
                    document.getElementById('systolic').value = data.data.systolic;
                    document.getElementById('diastolic').value = data.data.diastolic;
                    document.getElementById('map').value = data.data.map;
                    document.getElementById('pr').value = data.data.pr;
                    if (data.data.spo2) {
                        document.getElementById('spo2').value = data.data.spo2;
                    }
                    showMessage('✅ Data berjaya diekstrak!', 'green');
                } else {
                    showMessage('⚠️ Gagal mengekstrak data, sila isi manual', 'red');
                }
            } catch (err) {
                showMessage('⚠️ Ralat OCR, sila isi manual', 'red');
            }
        }
        
        function showMessage(text, color) {
            const messageDiv = document.getElementById('message');
            const colorMap = {
                'red': 'text-red-600 bg-red-50',
                'green': 'text-green-600 bg-green-50',
                'blue': 'text-blue-600 bg-blue-50'
            };
            messageDiv.innerHTML = `<span class="${colorMap[color]} p-4 block rounded-xl font-medium">${text}</span>`;
        }
        
        function generateWhatsAppText() {
            if (!lastSavedData) return null;
            
            const data = lastSavedData;
            const patientName = '<?php echo addslashes($patient['name']); ?>';
            
            const date = new Date(data.recorded_at.replace(' ', 'T'));
            const months = ['Jan', 'Feb', 'Mac', 'Apr', 'Mei', 'Jun', 'Jul', 'Ogos', 'Sep', 'Okt', 'Nov', 'Dis'];
            const formattedDate = `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}, ${String(date.getHours()).padStart(2, '0')}:${String(date.getMinutes()).padStart(2, '0')}`;
            
            let bp_status = '';
            if (data.map < 70) {
                bp_status = '⚠️ Tekanan darah rendah';
            } else if (data.map > 100) {
                bp_status = '⚠️ Tekanan darah tinggi';
            } else {
                bp_status = '✅ Tekanan darah normal';
            }
            
            let pr_status = '';
            if (data.pr < 60) {
                pr_status = '😴 Nadi perlahan';
            } else if (data.pr > 100) {
                pr_status = '🏃 Nadi laju';
            } else {
                pr_status = '😊 Nadi tenang';
            }
            
            let spo2_status = '';
            if (data.spo2) {
                if (data.spo2 < 95) {
                    spo2_status = '⚠️ Oksigen rendah';
                } else {
                    spo2_status = '✅ Oksigen baik';
                }
            }
            
            let text = `📋 Bacaan Pesakit: ${patientName}\n`;
            text += `📅 Masa: ${formattedDate}\n\n`;
            text += `❤️ Tekanan Darah: ${data.systolic}/${data.diastolic} mmHg\n`;
            text += `🩸 MAP: ${data.map} mmHg\n`;
            text += `   ${bp_status}\n\n`;
            text += `💓 Nadi: ${data.pr} bpm\n`;
            text += `   ${pr_status}`;
            if (data.spo2) {
                text += `\n\n🫁 Oksigen: ${data.spo2}%\n`;
                text += `   ${spo2_status}`;
            }
            
            return text;
        }
        
        async function copyToWhatsApp() {
            const text = generateWhatsAppText();
            if (!text) {
                showMessage('❌ Sila simpan data terlebih dahulu', 'red');
                return;
            }
            
            try {
                await navigator.clipboard.writeText(text);
                showMessage('✅ Data disalin ke papan keratan!', 'green');
                
                setTimeout(() => {
                    window.open('https://wa.me/', '_blank');
                }, 500);
            } catch (err) {
                showMessage('❌ Gagal menyalin data', 'red');
            }
        }
        
        document.getElementById('dataForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const imageFile = document.getElementById('imageInput').files[0];
            if (!imageFile) {
                showMessage('❌ Sila ambil gambar monitor terlebih dahulu', 'red');
                return;
            }
            
            const datetime = document.getElementById('datetime').value;
            if (!datetime) {
                showMessage('❌ Sila isi masa bacaan', 'red');
                return;
            }
            
            const systolic = parseInt(document.getElementById('systolic').value);
            const diastolic = parseInt(document.getElementById('diastolic').value);
            const map = parseInt(document.getElementById('map').value);
            const pr = parseInt(document.getElementById('pr').value);
            const spo2 = document.getElementById('spo2').value ? parseInt(document.getElementById('spo2').value) : null;
            
            if (!systolic || !diastolic || !map || !pr) {
                showMessage('❌ Sila lengkapkan: Systolic, Diastolic, MAP dan Nadi', 'red');
                return;
            }
            
            saveBtn.disabled = true;
            saveBtn.innerHTML = '⏳ Menyimpan...';
            showMessage('📤 Menyimpan data...', 'blue');
            
            const formData = new FormData();
            formData.append('image', imageFile);
            
            try {
                const uploadRes = await fetch('api/upload_image.php', {
                    method: 'POST',
                    body: formData
                });
                const uploadData = await uploadRes.json();
                
                if (!uploadData.success) {
                    throw new Error(uploadData.error);
                }
                
                const recordedAt = datetime.replace('T', ' ');
                
                const payload = {
                    patient_id: patientId,
                    recorded_at: recordedAt,
                    systolic: systolic,
                    diastolic: diastolic,
                    map: map,
                    pr: pr,
                    spo2: spo2,
                    image_path: uploadData.image_path
                };
                
                const saveRes = await fetch('api/save_data.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const saveData = await saveRes.json();
                
                if (saveData.success) {
                    lastSavedData = payload;
                    whatsappBtn.disabled = false;
                    showMessage('✅ Data berjaya disimpan! Klik WhatsApp untuk hantar.', 'green');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '💾 Simpan Data';
                } else {
                    throw new Error(saveData.error || 'Gagal simpan');
                }
            } catch (err) {
                showMessage(`❌ Error: ${err.message}`, 'red');
                saveBtn.disabled = false;
                saveBtn.innerHTML = '💾 Simpan Data';
            }
        });
        
        setDefaultDatetime();
    </script>
</body>
</html>
