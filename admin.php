<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Admin - Sistem Pemantauan Pesakit</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .btn-primary { transition: all 0.2s ease; }
        .btn-primary:active { transform: scale(0.97); }
        .btn-disabled { opacity: 0.5; pointer-events: none; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="max-w-lg mx-auto p-4 pb-20">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-2xl p-5 mb-5 shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">🏥 Admin Panel</h1>
                    <p class="text-blue-100 text-sm mt-1">Sistem Input Data Pesakit</p>
                </div>
                <div class="bg-white/20 rounded-full p-3">
                    <span class="text-2xl">📸</span>
                </div>
            </div>
        </div>

        <!-- Card Form -->
        <div class="bg-white rounded-2xl shadow-lg p-5">
            <div class="flex items-center gap-2 mb-4 pb-2 border-b border-gray-100">
                <span class="text-green-600 text-xl">✏️</span>
                <h2 class="text-lg font-semibold text-gray-800">Input Bacaan Baru</h2>
            </div>
            
            <form id="dataForm" enctype="multipart/form-data">
                <!-- Gambar Monitor -->
                <div class="mb-5">
                    <label class="block text-gray-700 font-medium mb-2">
                        📷 Gambar Monitor <span class="text-red-500">*</span>
                    </label>
                    <div class="border-2 border-dashed border-gray-300 rounded-xl p-4 text-center hover:border-blue-400 transition">
                        <input type="file" id="imageInput" accept="image/*" capture="environment" class="hidden" required>
                        <button type="button" id="cameraBtn" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-200">
                            📸 Ambil Gambar
                        </button>
                        <button type="button" id="galleryBtn" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 ml-2">
                            🖼️ Pilih dari Galeri
                        </button>
                        <div id="imagePreview" class="mt-3 hidden">
                            <img id="previewImg" class="rounded-xl border max-h-48 w-full object-contain bg-gray-50">
                            <button type="button" id="removeImageBtn" class="text-red-500 text-xs mt-1">✖️ Ganti gambar</button>
                        </div>
                    </div>
                </div>
                
                <!-- Masa Bacaan -->
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">
                        🕒 Masa Bacaan <span class="text-red-500">*</span>
                    </label>
                    <input type="datetime-local" id="datetime" class="w-full p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-400 focus:border-transparent" required>
                </div>
                
                <!-- Tekanan Darah (NIBP) -->
                <div class="mb-4">
                    <label class="block text-gray-700 font-medium mb-2">
                        ❤️ Tekanan Darah (NIBP)
                    </label>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="text-xs text-gray-500">Systolic <span class="text-red-500">*</span></label>
                            <input type="number" id="systolic" class="w-full p-3 border border-gray-300 rounded-xl text-center text-lg font-semibold focus:ring-2 focus:ring-blue-400" placeholder="117" required>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Diastolic <span class="text-red-500">*</span></label>
                            <input type="number" id="diastolic" class="w-full p-3 border border-gray-300 rounded-xl text-center text-lg font-semibold focus:ring-2 focus:ring-blue-400" placeholder="59" required>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">MAP <span class="text-red-500">*</span></label>
                            <input type="number" id="map" class="w-full p-3 border border-gray-300 rounded-xl text-center text-lg font-semibold focus:ring-2 focus:ring-blue-400" placeholder="67" required>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">📝 MAP diisi manual berdasarkan bacaan monitor</p>
                </div>
                
                <!-- Nadi & SpO2 -->
                <div class="grid grid-cols-2 gap-3 mb-6">
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">
                            💓 Nadi (PR) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" id="pr" class="w-full p-3 border border-gray-300 rounded-xl text-center text-lg font-semibold focus:ring-2 focus:ring-blue-400" placeholder="65" required>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-medium mb-2">
                            🫁 SpO₂ (%)
                        </label>
                        <input type="number" id="spo2" class="w-full p-3 border border-gray-300 rounded-xl text-center text-lg font-semibold focus:ring-2 focus:ring-blue-400" placeholder="99">
                    </div>
                </div>
                
                <!-- Butang Actions -->
                <div class="flex gap-3">
                    <button type="submit" id="saveBtn" class="flex-1 bg-gradient-to-r from-green-500 to-green-600 text-white py-3 rounded-xl font-semibold btn-primary shadow-md">
                        💾 Simpan & Hantar ke Telegram
                    </button>
                    <button type="button" id="whatsappBtn" class="flex-1 bg-gradient-to-r from-teal-500 to-teal-600 text-white py-3 rounded-xl font-semibold btn-primary shadow-md btn-disabled" disabled>
                        📱 Hantar WhatsApp
                    </button>
                </div>
            </form>
            
            <!-- Message Area -->
            <div id="message" class="mt-4 text-center text-sm p-2 rounded-lg"></div>
        </div>
        
        <!-- Info Footer -->
        <div class="mt-5 text-center text-xs text-gray-400">
            ✅ Tiada OCR - Staf taip nombor berdasarkan gambar monitor<br>
            📸 Gambar disimpan sebagai rekod dan bukti<br>
            🤖 Telegram akan dihantar automatik selepas simpan<br>
            📱 WhatsApp hanya boleh dihantar SELEPAS data berjaya disimpan
        </div>
    </div>

    <script>
        // Store last saved data for WhatsApp
        let lastSavedData = null;
        
        // Set default datetime to now (Malaysia time)
        function setDefaultDatetime() {
            const now = new Date();
            const malaysiaTime = new Date(now.getTime() + (8 * 60 * 60 * 1000));
            const year = malaysiaTime.getUTCFullYear();
            const month = String(malaysiaTime.getUTCMonth() + 1).padStart(2, '0');
            const day = String(malaysiaTime.getUTCDate()).padStart(2, '0');
            const hours = String(malaysiaTime.getUTCHours()).padStart(2, '0');
            const minutes = String(malaysiaTime.getUTCMinutes()).padStart(2, '0');
            document.getElementById('datetime').value = `${year}-${month}-${day}T${hours}:${minutes}`;
        }
        
        // No auto MAP calculation - manual entry only
        
        // Camera and gallery handlers
        const imageInput = document.getElementById('imageInput');
        const previewDiv = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
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
            previewImg.src = '';
            // Reset WhatsApp button when new image is selected
            whatsappBtn.disabled = true;
            whatsappBtn.classList.add('btn-disabled');
            lastSavedData = null;
        });
        
        // Preview image when selected
        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    previewImg.src = event.target.result;
                    previewDiv.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
            // Reset WhatsApp button when new image is selected
            whatsappBtn.disabled = true;
            whatsappBtn.classList.add('btn-disabled');
            lastSavedData = null;
        });
        
        // Format date to Malay readable format
        function formatDateToMalay(datetimeStr) {
            if (!datetimeStr) return '';
            const months = ['Januari', 'Februari', 'Mac', 'April', 'Mei', 'Jun', 
                            'Julai', 'Ogos', 'September', 'Oktober', 'November', 'Disember'];
            const date = new Date(datetimeStr);
            const malaysiaDate = new Date(date.getTime() + (8 * 60 * 60 * 1000));
            const day = malaysiaDate.getUTCDate();
            const month = months[malaysiaDate.getUTCMonth()];
            const year = malaysiaDate.getUTCFullYear();
            const hours = String(malaysiaDate.getUTCHours()).padStart(2, '0');
            const minutes = String(malaysiaDate.getUTCMinutes()).padStart(2, '0');
            return `${hours}:${minutes}, ${day} ${month} ${year}`;
        }
        
        // Get status for BP
        function getBPStatus(systolic, diastolic) {
            if (systolic < 90 || diastolic < 60) return '⚠️ Rendah';
            if (systolic > 140 || diastolic > 90) return '⚠️ Tinggi';
            return '✅ Normal';
        }
        
        // Get status for PR
        function getPRStatus(pr) {
            if (pr < 60) return '😴 Perlahan';
            if (pr > 100) return '🏃 Cepat';
            return '😊 Tenang';
        }
        
        // Get status for SpO2
        function getSpO2Status(spo2) {
            if (!spo2) return '';
            if (spo2 < 90) return '⚠️ Rendah';
            if (spo2 < 95) return '⚡ Sederhana';
            return '✅ Baik';
        }
        
        // Build WhatsApp message
        function buildWhatsAppMessage(data) {
            const formattedDate = formatDateToMalay(data.datetime);
            const bpStatus = getBPStatus(data.systolic, data.diastolic);
            const prStatus = getPRStatus(data.pr);
            const spo2Status = getSpO2Status(data.spo2);
            
            let msg = `━━━━━━━━━━━━━━━━━━━━━━━━━━%0A`;
            msg += `🩺 *STATUS PESAKIT TERKINI*%0A`;
            msg += `━━━━━━━━━━━━━━━━━━━━━━━━━━%0A%0A`;
            msg += `❤️ *Tekanan Darah:* ${data.systolic}/${data.diastolic} mmHg%0A`;
            msg += `   ↳ ${bpStatus}%0A%0A`;
            msg += `💓 *Nadi:* ${data.pr} bpm%0A`;
            msg += `   ↳ ${prStatus}%0A%0A`;
            if (data.spo2) {
                msg += `🫁 *Oksigen:* ${data.spo2}%%0A`;
                msg += `   ↳ ${spo2Status}%0A%0A`;
            }
            msg += `⏰ *Masa:* ${formattedDate}%0A%0A`;
            msg += `━━━━━━━━━━━━━━━━━━━━━━━━━━%0A`;
            msg += `ℹ️ *Makluman:*%0A`;
            msg += `Jika nombor keluar dari julat normal,%0A`;
            msg += `staf wad akan menghubungi anda.%0A%0A`;
            msg += `_Sistem Pemantauan Pesakit - Wad_`;
            
            return msg;
        }
        
        // Handle form submit (Save & Send to Telegram)
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
            
            // Disable save button to prevent double submit
            saveBtn.disabled = true;
            saveBtn.innerHTML = '⏳ Menyimpan...';
            showMessage('📤 Menyimpan data...', 'blue');
            
            const formData = new FormData();
            formData.append('image', imageFile);
            
            try {
                // Upload image
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
                    recorded_at: recordedAt,
                    systolic: systolic,
                    diastolic: diastolic,
                    map: map,
                    pr: pr,
                    spo2: spo2,
                    image_path: uploadData.image_path
                };
                
                // Save data and send Telegram
                const saveRes = await fetch('api/save_data.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const saveData = await saveRes.json();
                
                if (saveData.success) {
                    // Store saved data for WhatsApp
                    lastSavedData = {
                        datetime: datetime,
                        systolic: systolic,
                        diastolic: diastolic,
                        map: map,
                        pr: pr,
                        spo2: spo2
                    };
                    
                    // Enable WhatsApp button now that data is saved
                    whatsappBtn.disabled = false;
                    whatsappBtn.classList.remove('btn-disabled');
                    
                    showMessage('✅ Data disimpan & Telegram dihantar ke keluarga! Kini boleh hantar WhatsApp', 'green');
                    
                    // Reset form but keep last saved data for WhatsApp
                    document.getElementById('dataForm').reset();
                    document.getElementById('imagePreview').classList.add('hidden');
                    document.getElementById('previewImg').src = '';
                    setDefaultDatetime();
                    
                    // Reset save button
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '💾 Simpan & Hantar ke Telegram';
                    
                    setTimeout(() => {
                        document.getElementById('message').innerHTML = '';
                    }, 5000);
                } else {
                    throw new Error(saveData.error || 'Gagal simpan');
                }
            } catch (err) {
                showMessage(`❌ Error: ${err.message}`, 'red');
                saveBtn.disabled = false;
                saveBtn.innerHTML = '💾 Simpan & Hantar ke Telegram';
            }
        });
        
        // WhatsApp send button - ONLY after data is saved
        document.getElementById('whatsappBtn').addEventListener('click', () => {
            if (!lastSavedData) {
                showMessage('❌ Sila simpan data terlebih dahulu sebelum hantar WhatsApp', 'red');
                return;
            }
            
            const msg = buildWhatsAppMessage(lastSavedData);
            window.open(`https://wa.me/?text=${msg}`, '_blank');
        });
        
        function showMessage(text, color) {
            const messageDiv = document.getElementById('message');
            const colorMap = {
                'red': 'text-red-600 bg-red-50',
                'green': 'text-green-600 bg-green-50',
                'blue': 'text-blue-600 bg-blue-50'
            };
            messageDiv.innerHTML = `<span class="${colorMap[color]} p-2 block rounded-lg">${text}</span>`;
        }
        
        // Initialize
        setDefaultDatetime();
    </script>
</body>
</html>