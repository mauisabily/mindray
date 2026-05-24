CREATE DATABASE monitor_db;
USE monitor_db;

CREATE TABLE patient_readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recorded_at DATETIME NOT NULL,
    systolic INT NOT NULL,
    diastolic INT NOT NULL,
    map INT NOT NULL,
    pr INT NOT NULL,
    spo2 INT DEFAULT NULL,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE telegram_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bot_token VARCHAR(100) NOT NULL,
    chat_id VARCHAR(50) NOT NULL
);

-- Contoh config (anda perlu masukkan sendiri)
INSERT INTO telegram_config (bot_token, chat_id) VALUES ('8695279385:AAHOJWSk1gRW16tXZIYCQb5ILqVvaUDFUpE', '138809985');