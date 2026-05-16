#!/bin/bash
set -eo pipefail

echo "[DRUPAL-INIT] Waiting 30s for database..."
sleep 30

# Wait for Postgres
until PGPASSWORD="$POSTGRES_PASSWORD" psql -h "$POSTGRES_HOST" -U "$POSTGRES_USER" -d "$POSTGRES_DB" -c "SELECT 1" >/dev/null 2>&1; do
    echo "[DRUPAL-INIT] Waiting for PostgreSQL..."
    sleep 5
done

cd /var/www/html

# Check if Drupal is already installed
if [ ! -f sites/default/settings.php ] || ! drush status | grep -q "Database.*Connected"; then
    echo "[DRUPAL-INIT] Running Drupal site-install..."
    
    # Ensure correct permissions
    mkdir -p sites/default/files
    chown -R www-data:www-data /var/www/html
    chmod -R 775 sites/default/files

    drush site-install standard \
        --db-url="pgsql://$POSTGRES_USER:$POSTGRES_PASSWORD@$POSTGRES_HOST/$POSTGRES_DB" \
        --site-name="HAAD Tech Blog" \
        --account-name=admin \
        --account-pass="Drup4l_Admin!" \
        --account-mail="admin@haad.local" \
        -y
        
    # Final permission enforcement
    chown -R www-data:www-data sites/default/files
    chmod -R 775 sites/default/files
    
    echo "[DRUPAL-INIT] Drupal setup complete!"
else
    echo "[DRUPAL-INIT] Drupal already installed, skipping."
fi

# Add the flags
echo "[DRUPAL-INIT] Deploying flags..."
cp /tmp/flags/flag10_changelog.txt /var/www/html/CHANGELOG.txt || true
mkdir -p /home/drupal && cp /tmp/flags/flag11_rce.txt /home/drupal/flag.txt || true

echo "[DRUPAL-INIT] Starting Apache..."
exec apache2-foreground
