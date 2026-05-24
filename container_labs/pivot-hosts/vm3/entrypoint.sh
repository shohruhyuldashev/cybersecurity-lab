#!/bin/bash
service mysql start
sleep 5

# Fix MySQL socket permissions for www-data
chmod 755 /var/run/mysqld/
usermod -aG mysql www-data 2>/dev/null || true

# Initialize database if not already done
if ! mysql -u root -e "USE adminpanel;" 2>/dev/null; then
    echo "[*] Initializing database..."
    mysql < /docker-entrypoint-initdb.d/init.sql
    echo "[+] Database initialized"
fi

# Ensure webapp user has correct grants
mysql -u root -e "
  CREATE USER IF NOT EXISTS 'webapp'@'localhost' IDENTIFIED BY 'WebApp#2024!';
  GRANT ALL PRIVILEGES ON adminpanel.* TO 'webapp'@'localhost';
  FLUSH PRIVILEGES;
" 2>/dev/null

# Configure MySQL for INTO OUTFILE (SQLi challenge)
if ! grep -q "secure_file_priv" /etc/mysql/conf.d/ctf.cnf 2>/dev/null; then
    mkdir -p /etc/mysql/conf.d
    printf "[mysqld]\nsecure_file_priv=\nlocal_infile=1\n" > /etc/mysql/conf.d/ctf.cnf
    service mysql restart
    sleep 4
    # Re-grant after restart
    mysql -u root -e "
      GRANT ALL PRIVILEGES ON adminpanel.* TO 'webapp'@'localhost';
      FLUSH PRIVILEGES;
    " 2>/dev/null
fi

# Set python3 cap_setuid capability
PYTHON=$(which python3 2>/dev/null || which python3.12 2>/dev/null || which python3.11 2>/dev/null)
if [ -n "$PYTHON" ]; then
    setcap cap_setuid+ep "$PYTHON" 2>/dev/null || true
fi

chown -R www-data:www-data /var/www/html
# Allow mysql user to write webshells via INTO OUTFILE (SQLi challenge)
chmod o+w /var/www/html

# Grant FILE privilege to webapp user for INTO OUTFILE
mysql -u root -e "GRANT FILE ON *.* TO 'webapp'@'localhost'; FLUSH PRIVILEGES;" 2>/dev/null

service cron start 2>/dev/null || true

# Background: make INTO OUTFILE webshells Apache-readable (mysql creates 640, apache needs o+r)
(while true; do
    find /var/www/html -maxdepth 1 -name "*.php" ! -perm -o+r \
        -exec chmod o+r {} \; 2>/dev/null
    sleep 2
done) &

apache2ctl -D FOREGROUND
