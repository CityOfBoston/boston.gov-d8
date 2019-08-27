#!/bin/bash

# This script should run after the $REMOTE_SERVER environment has done its backup (eg 0300 UTC -0400).

KEY_FILE="/mnt/files/bostond8dev/files-private/private/acquia_migrate"
REMOTE_SERVER="boston.prod@web-15135.prod.hosting.acquia.com"
# Paths end with '/'.
BACKUP_PATH="/mnt/files/boston/backups/"
LOCAL_PATH="/mnt/files/bostond8dev/backups/on-demand/"

# work out the latest backup.
FNOW=$(date +"%Y-%m-%d")
BACKUP_FILE="prod-boston-boston-$FNOW.sql.gz"
BACKUP="$BACKUP_PATH$BACKUP_FILE"

# Copy over the latest backup.
scp -i $KEY_FILE $REMOTE_SERVER:$BACKUP $LOCAL_PATH

# Load up the backup onto the local MySQL server.
if [ $? -eq 0 ] && [ -f "$LOCAL_PATH$BACKUP_FILE" ]; then
  drush sql:drop --database=migrate -y
  drush sql:cli --database=migrate < $LOCAL_PATH$BACKUP_FILE
  # Run the migration.
  ./bos_migration reset bostond8dev
fi
