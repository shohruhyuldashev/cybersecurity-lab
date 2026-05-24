#!/bin/bash
set -e

# Create justin user with home directory
useradd -m -s /bin/bash justin

# Set up database dir
mkdir -p /var/db
touch /var/db/api.db
chmod 666 /var/db/api.db

# admin_tool: read-only for www-data, no SUID — only used to find the password
chown root:root /home/justin/admin_check
chmod 755 /home/justin/admin_check
rm -f /home/justin/admin_check.c

# Set root password = same as hardcoded in admin_tool
echo 'root:R00t@Internal#2024!' | chpasswd

# Create flags
echo "FLAG{1d0r_t0_4dm1n_p0wn3d}" > /root/root.txt
echo "FLAG{p1v0t_succ3ss}" > /home/justin/user.txt
chmod 400 /root/root.txt
chmod 644 /home/justin/user.txt
chown justin:justin /home/justin/user.txt

echo "[+] VM2 setup complete"
