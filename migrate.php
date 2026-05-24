<?php
require 'config.php';

try {
    echo "<h2>Migrasi Pangkalan Data</h2>";
    
    // 1. Create users table if not exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if (!$stmt->fetch()) {
        $pdo->exec("
            CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role ENUM('user', 'admin') DEFAULT 'user',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "✅ Created 'users' table<br>";
        
        // Insert default admin
        $pdo->exec("
            INSERT INTO users (name, email, password, role) VALUES 
            ('Admin', 'admin@example.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')
        ");
        echo "✅ Inserted default admin user<br>";
    } else {
        echo "ℹ️ 'users' table already exists<br>";
    }
    
    // 2. Create patients table if not exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'patients'");
    if (!$stmt->fetch()) {
        $pdo->exec("
            CREATE TABLE patients (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                created_by INT NOT NULL,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        echo "✅ Created 'patients' table<br>";
    } else {
        // Add status column if missing
        $stmt = $pdo->query("SHOW COLUMNS FROM patients LIKE 'status'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE patients ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' AFTER created_by");
            $pdo->exec("UPDATE patients SET status = 'approved'");
            echo "✅ Added 'status' column to patients table<br>";
        } else {
            echo "ℹ️ 'status' column already exists in patients table<br>";
        }
        
        // Add created_by column if missing
        $stmt = $pdo->query("SHOW COLUMNS FROM patients LIKE 'created_by'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE patients ADD COLUMN created_by INT NOT NULL AFTER name");
            // Set default created_by to first admin
            $admin = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch();
            if ($admin) {
                $pdo->exec("UPDATE patients SET created_by = " . $admin['id']);
            }
            echo "✅ Added 'created_by' column to patients table<br>";
        }
    }
    
    // 3. Create patient_collaborators table if not exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'patient_collaborators'");
    if (!$stmt->fetch()) {
        $pdo->exec("
            CREATE TABLE patient_collaborators (
                id INT AUTO_INCREMENT PRIMARY KEY,
                patient_id INT NOT NULL,
                user_id INT NOT NULL,
                invited_by INT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_collaboration (patient_id, user_id)
            )
        ");
        echo "✅ Created 'patient_collaborators' table<br>";
    } else {
        echo "ℹ️ 'patient_collaborators' table already exists<br>";
    }
    
    // 4. Update patient_readings table
    $stmt = $pdo->query("SHOW TABLES LIKE 'patient_readings'");
    if ($stmt->fetch()) {
        // Add patient_id column if missing
        $stmt = $pdo->query("SHOW COLUMNS FROM patient_readings LIKE 'patient_id'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE patient_readings ADD COLUMN patient_id INT NOT NULL FIRST");
            // If there's only one patient, set it
            $patient = $pdo->query("SELECT id FROM patients LIMIT 1")->fetch();
            if ($patient) {
                $pdo->exec("UPDATE patient_readings SET patient_id = " . $patient['id']);
            }
            echo "✅ Added 'patient_id' column to patient_readings table<br>";
        } else {
            echo "ℹ️ 'patient_id' column already exists in patient_readings table<br>";
        }
        
        // Add created_by column if missing
        $stmt = $pdo->query("SHOW COLUMNS FROM patient_readings LIKE 'created_by'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE patient_readings ADD COLUMN created_by INT NOT NULL AFTER image_path");
            $admin = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch();
            if ($admin) {
                $pdo->exec("UPDATE patient_readings SET created_by = " . $admin['id']);
            }
            echo "✅ Added 'created_by' column to patient_readings table<br>";
        }
    }
    
    // 5. Create/update telegram_config table
    $stmt = $pdo->query("SHOW TABLES LIKE 'telegram_config'");
    if (!$stmt->fetch()) {
        $pdo->exec("
            CREATE TABLE telegram_config (
                id INT AUTO_INCREMENT PRIMARY KEY,
                patient_id INT NOT NULL,
                bot_token VARCHAR(100) NOT NULL,
                chat_id VARCHAR(50) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
            )
        ");
        echo "✅ Created 'telegram_config' table<br>";
    } else {
        // Add patient_id column if missing
        $stmt = $pdo->query("SHOW COLUMNS FROM telegram_config LIKE 'patient_id'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE telegram_config ADD COLUMN patient_id INT NOT NULL FIRST");
            $patient = $pdo->query("SELECT id FROM patients LIMIT 1")->fetch();
            if ($patient) {
                $pdo->exec("UPDATE telegram_config SET patient_id = " . $patient['id']);
            }
            echo "✅ Added 'patient_id' column to telegram_config table<br>";
        }
    }
    
    echo "<br><h3>Migrasi Selesai!</h3>";
    echo "<p>Sila padam fail <code>migrate.php</code> untuk keselamatan.</p>";
    
} catch (PDOException $e) {
    echo "❌ Ralat: " . $e->getMessage();
}
?>