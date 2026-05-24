#!/bin/bash
set -e

# Fix MySQL config dir
mkdir -p /etc/mysql/conf.d
cat > /etc/mysql/conf.d/ctf.cnf << 'MYCNF'
[mysqld]
secure_file_priv=
local_infile=1
MYCNF

# Create flag file
mkdir -p /root
echo "FLAG{sql_1nj3ct10n_m4st3r}" > /root/root.txt
chmod 400 /root/root.txt

# Apache config
echo "ServerName localhost" >> /etc/apache2/apache2.conf
chown -R www-data:www-data /var/www/html

echo "[+] VM3 Setup Complete (DB init happens at runtime)"
