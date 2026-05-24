# 🏥 Sistem Pemantauan Pesakit Koma - Keluarga

Sistem web ringkas untuk membantu **keluarga pesakit koma** memantau status terkini pesakit (Tekanan Darah, Nadi, SpO₂) dalam bahasa mudah. Direka untuk kegunaan di wad hospital dengan jaringan terhad.

---

## 📋 Ringkasan

Sistem ini membolehkan **staf wad** mengambil gambar monitor pesakit, menaip data secara manual, dan **secara automatik menghantar notifikasi ke Telegram keluarga**. Data disimpan dalam pangkalan data MariaDB/MySQL dan dipaparkan dalam bentuk grafik ringkas untuk keluarga.

**Tiada OCR digunakan** kerana ketidakstabilan membaca nombor dari gambar monitor. Staf hanya taip nombor berdasarkan gambar yang diambil.

---

## ✨ Ciri-ciri Utama

| Ciri | Keterangan |
|------|-------------|
| 📸 **Input Manual + Gambar** | Staf ambil gambar monitor, taip nombor secara manual |
| 💾 **Penyimpanan Data** | Semua bacaan disimpan ke MariaDB/MySQL |
| 🤖 **Telegram Auto** | Selepas simpan, sistem auto hantar ke keluarga |
| 📱 **WhatsApp Manual** | Staf boleh kongsi secara manual ke WhatsApp |
| 📊 **Graf Trend** | Keluarga lihat trend 5 bacaan terakhir |
| 🟢 **Status Layman** | Paparan mudah dengan emoji & warna |
| 📱 **Responsif** | Boleh diakses dari telefon keluarga |

---

## 🛠️ Teknologi

| Komponen | Teknologi |
|----------|-----------|
| Frontend | HTML5, Tailwind CSS, JavaScript |
| Backend | PHP 7.4+ |
| Database | MariaDB / MySQL |
| Notifikasi | Telegram Bot API |
| Hosting | XAMPP / Laragon / Any PHP server |

---

## 📁 Struktur Folder

```
monitor-family/
├── index.php              # Halaman keluarga (papar status terkini)
├── admin.php              # Halaman admin (input data)
├── config.php             # Konfigurasi database & API
├── setup.sql              # Skema pangkalan data
├── api/
│   ├── upload_image.php   # Upload gambar ke server
│   ├── save_data.php      # Simpan data & hantar Telegram
│   ├── get_latest.php     # Ambil bacaan terkini (JSON)
│   └── get_history.php    # Ambil 5 bacaan terakhir (JSON)
├── uploads/               # Folder simpan gambar (perlu write permission)
└── README.md
```

---

## 🚀 Cara Pemasangan

### 1. Prasyarat

- **XAMPP / Laragon** (PHP + MariaDB/MySQL)
- **Telegram Bot Token** & **Chat ID** (dapat dari BotFather)
- Web browser (Chrome/Firefox)

### 2. Langkah-langkah

```bash
# Clone atau download repository
git clone https://github.com/username/monitor-family.git
cd monitor-family

# Buat folder uploads
mkdir uploads
chmod 755 uploads   # (Linux/Mac) atau set permission secara manual di Windows
```

### 3. Setup Pangkalan Data

1. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`)
2. Buka tab **SQL**
3. **Copy & paste** kandungan `setup.sql`
4. Klik **Go**

### 4. Konfigurasi

Edit `config.php`:

```php
<?php
$host = 'localhost';
$dbname = 'monitor_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
```

> **Nota**: Sistem ini tidak memerlukan OCR API key kerana menggunakan input manual.

### 5. Setup Telegram Bot

1. Buka Telegram → cari **BotFather**
2. Hantar: `/newbot` → ikut arahan → dapatkan **Bot Token**
3. Cari **Get My ID Bot** → dapatkan **Chat ID** (untuk kumpulan atau peribadi)
4. Masukkan ke dalam database:

```sql
INSERT INTO telegram_config (bot_token, chat_id) VALUES 
('YOUR_BOT_TOKEN', 'YOUR_CHAT_ID');
```

### 6. Jalankan Sistem

- Letakkan folder `monitor-family` dalam `htdocs` (XAMPP) atau `www` (Laragon)
- Akses:
  - **Admin (Staf)**: `http://localhost/monitor-family/admin.php`
  - **Keluarga**: `http://localhost/monitor-family/index.php`

---

## 🖥️ Cara Penggunaan

### Untuk Staf Wad (admin.php)

| Langkah | Tindakan |
|---------|----------|
| 1 | Ambil gambar monitor pesakit |
| 2 | Taip nombor: Systolic, Diastolic, MAP, Nadi, SpO₂ (optional) |
| 3 | Klik **Simpan & Hantar ke Telegram** |
| 4 | Telegram auto dihantar ke keluarga |
| 5 | (Optional) Klik **Hantar WhatsApp** untuk share manual |

### Untuk Keluarga (index.php)

| Paparan | Keterangan |
|---------|------------|
| ❤️ Tekanan Darah | Paparan + status (Normal/Rendah/Tinggi) |
| 💓 Nadi | Paparan + status (Tenang/Perlahan/Cepat) |
| 🫁 Oksigen | Paparan + status (Baik/Sederhana/Rendah) |
| 📈 Graf | Trend 5 bacaan terakhir (MAP & Nadi) |
| ⏰ Masa | Tarikh & masa bacaan terkini |

---

## 📊 Contoh Output

### Mesej Telegram / WhatsApp

```
━━━━━━━━━━━━━━━━━━━━━━━━━━
🩺 STATUS PESAKIT TERKINI
━━━━━━━━━━━━━━━━━━━━━━━━━━

❤️ Tekanan Darah: 117/59 mmHg
   ↳ ✅ Normal

💓 Nadi: 65 bpm
   ↳ 😊 Tenang

🫁 Oksigen: 99%
   ↳ ✅ Baik

⏰ Masa: 22:00, 24 Mei 2026

━━━━━━━━━━━━━━━━━━━━━━━━━━
ℹ️ Makluman:
Jika nombor keluar dari julat normal,
staf wad akan menghubungi anda.

_Sistem Pemantauan Pesakit - Wad_
```

---

## 🧪 Penyelesaian Masalah (Troubleshooting)

| Masalah | Penyelesaian |
|---------|---------------|
| Data tidak tersimpan | Semak `config.php` & pastikan database `monitor_db` wujud |
| Telegram tidak hantar | Semak `bot_token` & `chat_id` dalam table `telegram_config` |
| Gambar tidak boleh upload | Pastikan folder `uploads/` ada & boleh write (permission 755) |
| WhatsApp button kelabu | Mesti **simpan data dahulu** sebelum boleh hantar WhatsApp |
| Halaman keluarga kosong | Semak ada data dalam `patient_readings` |

---

## 🔄 Aliran Data

```
[Monitor Pesakit] 
       ↓
[Staf ambil gambar & taip data]
       ↓
[admin.php] → upload_image.php → simpan gambar
       ↓
[save_data.php] → INSERT ke MariaDB
       ↓
[save_data.php] → Telegram Bot API → hantar ke keluarga
       ↓
[WhatsApp button aktif] → staf klik untuk hantar manual
       ↓
[index.php] → keluarga lihat status terkini
```

---

## 📝 Penambahbaikan Masa Depan

- [ ] Autentikasi login untuk halaman admin
- [ ] Multiple patient support
- [ ] Export data ke PDF/Excel
- [ ] Peringatan automatik jika bacaan tidak normal
- [ ] Integrasi dengan monitor pesakit (HL7/FHIR)
- [ ] App mobile untuk keluarga

---

## 📜 Lesen

MIT License - Bebas digunakan, diubah suai, dan dikongsi.

---

## 👥 Kontribusi

Pull requests dialu-alukan. Untuk perubahan besar, sila buka isu terlebih dahulu.

---

## 🙏 Penghargaan

- Tailwind CSS untuk styling
- Chart.js untuk grafik
- Telegram Bot API untuk notifikasi

---

**Dibangunkan untuk memudahkan komunikasi antara wad hospital dan keluarga pesakit.** 💙
```

---

## 📦 Cara Upload ke GitHub

```bash
# Inisialisasi git repository
cd monitor-family
git init

# Tambah semua file
git add .

# Commit
git commit -m "Initial commit: Sistem Pemantauan Pesakit untuk Keluarga"

# Tambah remote repository (ganti dengan repo URL anda)
git remote add origin https://github.com/username/monitor-family.git

# Push ke GitHub
git branch -M main
git push -u origin main
```

---

## ✅ Ringkasan

`README.md` ini mengandungi:

| Bahagian | Kandungan |
|----------|-----------|
| Pengenalan | Apa sistem ini buat |
| Ciri-ciri | Senarai fungsi utama |
| Teknologi | Stack yang digunakan |
| Struktur Folder | Susunan file |
| Cara Pemasangan | Langkah demi langkah |
| Cara Penggunaan | Untuk staf & keluarga |
| Contoh Output | Mesej Telegram/WhatsApp |
| Troubleshooting | Penyelesaian masalah umum |
| Aliran Data | Visual proses sistem |
| Penambahbaikan | Rancangan masa depan |
| Lesen | MIT License |