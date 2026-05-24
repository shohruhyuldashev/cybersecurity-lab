-- AdminPanel Database Initialization
-- VULNERABILITY: MySQL FILE privilege enabled for INTO OUTFILE webshell
-- VULNERABILITY: secure_file_priv disabled

CREATE DATABASE IF NOT EXISTS adminpanel;
USE adminpanel;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    role ENUM('admin','analyst','viewer') DEFAULT 'viewer',
    department VARCHAR(50),
    last_login DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    active TINYINT(1) DEFAULT 1
);

-- Reports table
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    category VARCHAR(50),
    severity ENUM('critical','high','medium','low') DEFAULT 'medium',
    status ENUM('open','in_progress','resolved','closed') DEFAULT 'open',
    description TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Seed users
INSERT INTO users (username, password, email, role, department) VALUES
('admin',    MD5('SuperAdmin2024'), 'admin@corp.local',   'admin',   'IT Security'),
('jdoe',     MD5('JohnPass123'),    'jdoe@corp.local',    'analyst', 'SOC'),
('msmith',   MD5('Mary@secure'),    'msmith@corp.local',  'analyst', 'Compliance'),
('rwilson',  MD5('R0bert!pass'),    'rwilson@corp.local', 'viewer',  'Finance'),
('tlee',     MD5('TomPass#99'),     'tlee@corp.local',    'viewer',  'Operations');

-- Seed reports
INSERT INTO reports (user_id, title, category, severity, status, description) VALUES
(2, 'Suspicious login attempts from 192.168.1.x',   'Authentication', 'high',     'open',        'Multiple failed logins from subnet 192.168.1.0/24 over 2 hours'),
(2, 'Unencrypted data in transit detected',          'Encryption',     'critical', 'in_progress', 'TLS 1.0 still enabled on legacy web server port 8080'),
(3, 'Weak password policy violation - Finance dept', 'Policy',         'medium',   'open',        'Several Finance users using passwords shorter than 8 chars'),
(3, 'Missing patches on Windows Server 2019',        'Patching',       'high',     'open',        'KB5031364 and KB5032190 missing on 3 servers'),
(4, 'VPN split tunneling misconfiguration',          'Network',        'medium',   'resolved',    'Fixed: all traffic now routed through corporate VPN'),
(5, 'USB storage policy not enforced on floor 3',    'Endpoint',       'low',      'open',        'USB autorun disabled but write access still permitted'),
(2, 'Phishing campaign targeting HR department',     'Phishing',       'critical', 'open',        'Credential harvesting site mimicking internal HR portal'),
(3, 'Log retention policy breach - 30 day gap',      'Compliance',     'high',     'closed',      'Logs from Jan-Feb 2024 missing from SIEM');

-- Create MySQL user with FILE privilege for SQLi -> webshell
CREATE USER IF NOT EXISTS 'webapp'@'localhost' IDENTIFIED BY 'WebApp#2024!';
GRANT ALL PRIVILEGES ON adminpanel.* TO 'webapp'@'localhost';
GRANT FILE ON *.* TO 'webapp'@'localhost';
FLUSH PRIVILEGES;
