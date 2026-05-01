CREATE DATABASE IF NOT EXISTS cyber_incident CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cyber_incident;

CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    email      VARCHAR(150)  UNIQUE NOT NULL,
    password   VARCHAR(255)  NOT NULL,
    role       ENUM('employee','admin') DEFAULT 'employee',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS incidents (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT,
    title         VARCHAR(255) NOT NULL,
    incident_type ENUM('phishing','malware','unauthorized_access','data_breach','other') NOT NULL,
    description   TEXT NOT NULL,
    severity      ENUM('low','medium','high','critical') DEFAULT 'medium',
    status        ENUM('open','investigating','resolved','closed') DEFAULT 'open',
    ai_analysis   TEXT,
    reported_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS alerts (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    incident_id    INT,
    alert_message  TEXT NOT NULL,
    severity       ENUM('low','medium','high','critical') DEFAULT 'medium',
    is_read        BOOLEAN DEFAULT FALSE,
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS investigation_log (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    incident_id INT,
    admin_id    INT,
    action      VARCHAR(255) NOT NULL,
    notes       TEXT,
    logged_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id)    REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO users (name,email,password,role)
VALUES (
'Admin User',
'admin@company.com',
'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9q3sWk9C7VwFh6Zz7Z8WlW',
'admin'
),
('John Employee','john@company.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9q3sWk9C7VwFh6Zz7Z8WlW',  'employee');

INSERT INTO incidents (user_id, title, incident_type, description, severity, status, ai_analysis) VALUES
(2, 'Suspicious Phishing Email Received',  'phishing',            'Received an email from unknown@hacker.com asking for login credentials. Email had a suspicious link.',         'high',     'open',          '[Risk Score: 85/100 — CRITICAL] Phishing attack vector detected. T1566 - Phishing. HIGH PRIORITY — 2hr response. Do NOT click links. Report sender to IT immediately.'),
(2, 'Malware Alert on Workstation WS-14',  'malware',             'Antivirus flagged suspicious executable running from temp folder. Process name: svchost32.exe.',               'critical', 'investigating', '[Risk Score: 95/100 — CRITICAL] Malware infection risk. T1204 - User Execution. ESCALATE TO CISO NOW. Isolate device immediately.'),
(2, 'Unauthorized SSH Access Attempt',     'unauthorized_access', 'Multiple failed SSH login attempts from IP 45.33.32.156. Account locked after 5 attempts.',                   'medium',   'resolved',      '[Risk Score: 70/100 — HIGH] Unauthorized access attempt. T1110 - Brute Force. HIGH PRIORITY — 2hr response. Review firewall rules.');

INSERT INTO alerts (incident_id, alert_message, severity) VALUES
(1, 'HIGH ALERT: New phishing incident reported by John Employee. Review immediately.',        'high'),
(2, 'CRITICAL ALERT: Malware detected on WS-14. Incident requires immediate investigation.',  'critical'),
(3, 'MEDIUM ALERT: Unauthorized SSH attempt on server. Account has been locked.',             'medium');