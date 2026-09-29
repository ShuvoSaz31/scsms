USE student_service_management;

CREATE TABLE IF NOT EXISTS user_emails (
    email_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
    INDEX (user_id)
);

CREATE TABLE IF NOT EXISTS user_phones (
    phone_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    phone VARCHAR(30) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
    UNIQUE (user_id, phone),
    INDEX (user_id)
);

INSERT IGNORE INTO user_emails (user_id, email, is_primary)
SELECT user_id, email, 1 FROM users;

INSERT IGNORE INTO user_phones (user_id, phone, is_primary)
SELECT user_id, phone, 1 FROM users WHERE phone IS NOT NULL AND TRIM(phone) <> '';