# Sistem Pemantauan Pesakit - Product Requirements Document (PRD)

## 📋 Gambaran Keseluruhan
Sistem SaaS untuk memantau pesakit dengan ciri log masuk pengguna, kelulusan admin, dan integrasi Telegram & WhatsApp.

## 🎯 Matlamat
- Membolehkan pengguna mendaftar dan log masuk
- Membolehkan pengguna menambah pesakit dan menjemput pengguna lain untuk bekerjasama
- Memerlukan kelulusan admin sebelum pesakit boleh diakses
- Auto hantar bacaan ke Telegram selepas simpan
- Butang WhatsApp untuk copy data dan buka WhatsApp

---

## 👥 Peranan Pengguna

### 1. Pengguna Biasa
- Daftar akaun dan log masuk
- Tambah pesakit baru (status pending)
- Lihat dan keyin data pesakit yang diluluskan
- Jemput pengguna lain untuk bekerjasama pada pesakit yang dicipta
- Konfigurasi Bot Token dan Chat ID Telegram untuk pesakit
- Auto hantar bacaan ke Telegram selepas simpan
- Butang WhatsApp untuk copy data dan buka WhatsApp

### 2. Admin
- Semua kebolehan pengguna biasa
- Akses penuh ke semua pesakit (tidak kira status)
- Lulus atau tolak pesakit yang pending
- Lihat senarai semua pengguna dan pesakit
- Lihat statistik jumlah pengguna, pesakit, dan bacaan

---

## 📊 Aliran Kerja

### Aliran Pengguna
1. Pengguna daftar akaun di `register.php`
2. Pengguna log masuk di `login.php`
3. Pengguna tambah pesakit baru di `dashboard.php` → status pending
4. Admin log masuk ke `admin_dashboard.php` dan luluskan pesakit
5. Pengguna boleh:
   - Lihat status pesakit di `index.php`
   - Keyin data dan muat naik gambar di `admin.php`
   - Konfigurasi Telegram di `admin.php`
   - Jemput pengguna lain di `dashboard.php`
6. Selepas simpan data, mesej auto dihantar ke Telegram

### Aliran Admin
1. Admin log masuk ke `admin_dashboard.php`
2. Lihat senarai pesakit pending dan lulus/tolak
3. Lihat statistik dan senarai semua pengguna & pesakit
4. Boleh akses mana-mana pesakit untuk lihat status atau input data

---

## 🗄️ Skema Pangkalan Data

### Jadual `users`
| Lajur | Jenis | Keterangan |
|-------|-------|------------|
| id | INT (PK) | ID pengguna |
| name | VARCHAR(100) | Nama pengguna |
| email | VARCHAR(100) (UNIQUE) | Email pengguna |
| password | VARCHAR(255) | Kata laluan (bcrypt) |
| role | ENUM('user', 'admin') | Peranan pengguna |
| created_at | TIMESTAMP | Tarikh daftar |

### Jadual `patients`
| Lajur | Jenis | Keterangan |
|-------|-------|------------|
| id | INT (PK) | ID pesakit |
| name | VARCHAR(100) | Nama pesakit |
| created_by | INT (FK) | ID pengguna yang mencipta |
| status | ENUM('pending', 'approved', 'rejected') | Status pesakit |
| created_at | TIMESTAMP | Tarikh dicipta |

### Jadual `patient_collaborators`
| Lajur | Jenis | Keterangan |
|-------|-------|------------|
| id | INT (PK) | ID kolaborasi |
| patient_id | INT (FK) | ID pesakit |
| user_id | INT (FK) | ID pengguna yang dijemput |
| invited_by | INT (FK) | ID pengguna yang menjemput |
| created_at | TIMESTAMP | Tarikh jemputan |

### Jadual `patient_readings`
| Lajur | Jenis | Keterangan |
|-------|-------|------------|
| id | INT (PK) | ID bacaan |
| patient_id | INT (FK) | ID pesakit |
| recorded_at | DATETIME | Masa bacaan |
| systolic | INT | Tekanan darah systolic |
| diastolic | INT | Tekanan darah diastolic |
| map | INT | Mean Arterial Pressure |
| pr | INT | Nadi (Pulse Rate) |
| spo2 | INT (NULL) | Oksigen (SpO₂) |
| image_path | VARCHAR(255) | Laluan gambar monitor |
| created_by | INT (FK) | ID pengguna yang menyimpan |
| created_at | TIMESTAMP | Tarikh simpan |

### Jadual `telegram_config`
| Lajur | Jenis | Keterangan |
|-------|-------|------------|
| id | INT (PK) | ID konfigurasi |
| patient_id | INT (FK) | ID pesakit |
| bot_token | VARCHAR(100) | Token Bot Telegram |
| chat_id | VARCHAR(50) | Chat ID Telegram |
| created_at | TIMESTAMP | Tarikh simpan |

---

## 📱 Senarai Halaman

| Halaman | Keterangan | Akses |
|---------|------------|-------|
| `login.php` | Halaman log masuk | Semua |
| `register.php` | Halaman daftar akaun | Semua |
| `dashboard.php` | Dashboard pengguna (mobile-first) | Pengguna & Admin |
| `index.php` | Status pesakit (mobile-first) | Pengguna & Admin |
| `admin.php` | Input data pesakit (mobile-first) | Pengguna & Admin |
| `admin_dashboard.php` | Dashboard admin (desktop-first) | Admin sahaja |
| `logout.php` | Log keluar | Semua yang log masuk |

---

## 🔌 API Endpoints

| Endpoint | Kaedah | Keterangan |
|----------|--------|------------|
| `api/upload_image.php` | POST | Muat naik gambar monitor |
| `api/save_data.php` | POST | Simpan bacaan pesakit & hantar Telegram |
| `api/get_latest.php` | GET | Dapatkan bacaan terkini pesakit |
| `api/get_history.php` | GET | Dapatkan 5 bacaan terakhir pesakit |
| `api/save_telegram_config.php` | POST | Simpan konfigurasi Telegram pesakit |

---

## 🤖 Integrasi Telegram

### Format Mesej Telegram
```
📋 *Bacaan Pesakit Baru*
👤 Nama: *Nama Pesakit*
📅 Masa: 25 Mei 2026, 14:30

❤️ *Tekanan Darah*: 117/59 mmHg
🩸 *MAP*: 67 mmHg
💓 *Nadi*: 65 bpm
🫁 *SpO₂*: 99%
```

---

## 💬 Integrasi WhatsApp

### Format Mesej WhatsApp
```
📋 Bacaan Pesakit: Nama Pesakit
📅 Masa: 25 Mei 2026, 14:30

❤️ Tekanan Darah: 117/59 mmHg
🩸 MAP: 67 mmHg
💓 Nadi: 65 bpm
🫁 SpO₂: 99%
```

---

## 📋 Setup Sistem

### 1. Pangkalan Data
- Import `database.sql` ke MariaDB/MySQL
- Atau jalankan `migrate.php` untuk migrasi automatik

### 2. Konfigurasi
- Edit `config.php` untuk tetapan pangkalan data

### 3. Akses Awal
- Admin lalai: `admin@example.com` / `password`
- Daftar akaun baru sebagai pengguna biasa

---

## ✅ Ciri-Ciri Utama
- ✅ Autentikasi pengguna (log masuk & daftar)
- ✅ Kelulusan pesakit oleh admin
- ✅ Kolaborasi pengguna pada pesakit
- ✅ Input data bacaan dengan gambar
- ✅ Paparan status dan graf trend
- ✅ Auto hantar ke Telegram
- ✅ Butang WhatsApp (copy & buka)
- ✅ UI mobile-first untuk pengguna
- ✅ UI desktop-first untuk admin
