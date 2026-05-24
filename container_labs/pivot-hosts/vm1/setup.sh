#!/bin/bash
set -e

# Create SQLite database directory
mkdir -p /var/db
touch /var/db/users.db
chown www-data:www-data /var/db/users.db
chmod 664 /var/db/users.db
chown www-data:www-data /var/db
chmod 775 /var/db

# Create uploads directory
mkdir -p /var/www/html/uploads
chown www-data:www-data /var/www/html/uploads
chmod 777 /var/www/html/uploads

# Initialize SQLite database
sqlite3 /var/db/users.db "
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    email TEXT,
    avatar TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS files (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER,
    filename TEXT NOT NULL,
    original_name TEXT,
    file_type TEXT,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id)
);
INSERT OR IGNORE INTO users (username, password, email) VALUES 
    ('admin', '$(echo -n "admin@docushare2024" | sha256sum | cut -d" " -f1)', 'admin@docushare.local'),
    ('john', '$(echo -n "Welcome123!" | sha256sum | cut -d" " -f1)', 'john@docushare.local');
"

# ============= PRIVILEGE ESCALATION SETUP =============
# Create 'filebackup' group (the vulnerable group)
groupadd -f filebackup

# Add www-data to filebackup group
usermod -aG filebackup www-data

# Set backup script permissions:
# - Owner: root (so cron runs as root)
# - Group: filebackup (www-data can write to it!)
# - Permissions: 775 (group writable)
chown root:filebackup /usr/local/bin/backup.sh
chmod 775 /usr/local/bin/backup.sh

# Create backup directory
mkdir -p /var/backups/docushare
chown root:filebackup /var/backups/docushare
chmod 775 /var/backups/docushare

# Add cronjob - runs backup.sh as root every minute
echo "* * * * * root /usr/local/bin/backup.sh" > /etc/cron.d/docushare-backup
chmod 644 /etc/cron.d/docushare-backup

# Set proper permissions on web files
chown -R www-data:www-data /var/www/html
find /var/www/html -type f -name "*.php" -exec chmod 644 {} \;
find /var/www/html -type d -exec chmod 755 {} \;

# Create a flag file
echo "FLAG{w3lc0m3_t0_v1rtu4l_w0rld}" > /root/root.txt
chmod 400 /root/root.txt

# Apache config
echo "ServerName localhost" >> /etc/apache2/apache2.conf

echo "[+] VM1 Setup Complete!"

# Make index.php the default page
rm -f /var/www/html/index.html
echo "DirectoryIndex index.php index.html" > /etc/apache2/conf-available/php-index.conf
a2enconf php-index 2>/dev/null || true
