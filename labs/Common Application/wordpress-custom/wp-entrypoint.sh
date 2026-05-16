#!/bin/bash
set -eo pipefail

# Start original WordPress entrypoint + Apache in background
docker-entrypoint.sh apache2-foreground &

echo "[WP-INIT] Waiting 30s for DB and WordPress to initialize..."
sleep 30

# Wait for MySQL to be ready
until php -r "new mysqli('${WORDPRESS_DB_HOST%%:*}', '$WORDPRESS_DB_USER', '$WORDPRESS_DB_PASSWORD', '$WORDPRESS_DB_NAME');" 2>/dev/null; do
    echo "[WP-INIT] Waiting for database..."
    sleep 5
done

# Install WordPress if not already installed
if ! wp core is-installed --path=/var/www/html --allow-root 2>/dev/null; then
    echo "[WP-INIT] Running WordPress installation..."
    wp core install \
        --path=/var/www/html \
        --url="http://wordpress.haad.local" \
        --title="HAAD Corp - Employee Portal" \
        --admin_user="admin" \
        --admin_password="W0rdPr3ss_Admin!" \
        --admin_email="admin@haad.local" \
        --allow-root \
        --skip-email
    echo "[WP-INIT] Creating CTF user john..."
    wp user create john john@haad.local \
        --role=editor \
        --user_pass=firebird1 \
        --allow-root 2>/dev/null || true
    echo "[WP-INIT] WordPress setup complete!"
else
    echo "[WP-INIT] WordPress already installed, skipping."
fi

# Keep Apache running
wait
