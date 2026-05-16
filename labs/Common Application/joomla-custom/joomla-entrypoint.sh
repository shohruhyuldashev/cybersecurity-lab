#!/bin/bash
set -eo pipefail

echo "[JOOMLA-INIT] Waiting 30s for database..."
sleep 30

# Wait for DB to be ready
until mysql -h "${JOOMLA_DB_HOST%%:*}" -u "$JOOMLA_DB_USER" -p"$JOOMLA_DB_PASSWORD" "$JOOMLA_DB_NAME" -e "SELECT 1" 2>/dev/null; do
    echo "[JOOMLA-INIT] Waiting for database..."
    sleep 5
done

cd /var/www/html

# Check if Joomla needs installation (file missing OR DB empty)
TABLE_COUNT=$(mysql -h "${JOOMLA_DB_HOST%%:*}" -u "$JOOMLA_DB_USER" -p"$JOOMLA_DB_PASSWORD" "$JOOMLA_DB_NAME" -e "SHOW TABLES;" | wc -l || echo 0)
if [ ! -f configuration.php ] || [ "$TABLE_COUNT" -le 1 ]; then
    echo "[JOOMLA-INIT] First time run: Setting up Joomla..."
    
    # 1. Copy installation folder if missing
    if [ ! -d installation ]; then
        cp -r /usr/src/joomla/installation .
        chown -R www-data:www-data installation
    fi

    # 2. Run CLI installer
    cat <<EOF > /tmp/joomla_install.xml
<?xml version="1.0" encoding="UTF-8"?>
<joomla>
    <database>
        <type>mysqli</type>
        <host>${JOOMLA_DB_HOST%%:*}</host>
        <user>${JOOMLA_DB_USER}</user>
        <password>${JOOMLA_DB_PASSWORD}</password>
        <name>${JOOMLA_DB_NAME}</name>
        <prefix>jos_</prefix>
    </database>
    <settings>
        <admin>
            <name>Administrator</name>
            <username>admin</username>
            <password>${JOOMLA_ADMIN_PASSWORD}</password>
            <email>admin@haad.local</email>
        </admin>
        <site>
            <name>HAAD Corp - Intranet Portal</name>
        </site>
    </settings>
</joomla>
EOF

    if php installation/cli/install.php --file=/tmp/joomla_install.xml --force-config; then
        echo "[JOOMLA-INIT] Installation successful!"
        rm -rf installation
    else
        echo "[JOOMLA-INIT] CLI Installation failed, using backup configuration..."
        cp /joomla-custom-config.php /var/www/html/configuration.php || true
        rm -rf installation
    fi
    rm -f /tmp/joomla_install.xml
fi

# Deploy flags
echo "[JOOMLA-INIT] Deploying flags..."
mkdir -p /var/www/html/administrator/manifests/files/
cp /tmp/flags/flag7_joomla.xml /var/www/html/administrator/manifests/files/joomla.xml || true
cp /tmp/flags/flag8_config.txt /var/www/html/flag8_config.txt || true
mkdir -p /root && cp /tmp/flags/flag9_rce.txt /root/joomla_flag.txt || true

echo "[JOOMLA-INIT] Starting Apache..."
exec /entrypoint.sh apache2-foreground
