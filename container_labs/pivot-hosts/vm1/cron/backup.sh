#!/bin/bash
#!/bin/bash
# DocuShare Backup Script
# Runs every minute via cron as root
# PrivEsc: www-data is in 'filebackup' group which has write access to this file

BACKUP_DIR="/var/backups/docushare"
WEB_DIR="/var/www/html/uploads"
DATE=$(date +%Y%m%d_%H%M%S)

# Backup uploaded files
if [ -d "$WEB_DIR" ]; then
    cp -r "$WEB_DIR" "$BACKUP_DIR/uploads_$DATE" 2>/dev/null
fi

# Backup database
if [ -f "/var/db/users.db" ]; then
    cp /var/db/users.db "$BACKUP_DIR/db_$DATE.db" 2>/dev/null
fi

# Cleanup old backups (keep last 5)
ls -t "$BACKUP_DIR" | tail -n +6 | xargs -I {} rm -rf "$BACKUP_DIR/{}" 2>/dev/null

exit 0
