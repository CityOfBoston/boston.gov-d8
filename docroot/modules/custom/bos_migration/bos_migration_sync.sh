#!/bin/bash

# This script should run after the $REMOTE_SERVER environment has done its backup (eg 0300 UTC -0400).

KEY_FILE="/mnt/files/bostond8dev/files-private/private/acquia_migrate"
REMOTE_SERVER="boston.prod@web-15135.prod.hosting.acquia.com"
# Paths end with '/'.
BACKUP_PATH="/mnt/files/boston/backups/"
LOCAL_PATH="/mnt/files/bostond8dev/backups/on-demand/"

# Define the latest backup.
FNOW=$(date +"%Y-%m-%d")
BACKUP_FILE_ZIP="prod-boston-boston-$FNOW.sql.gz"
BACKUP_FILE="prod-boston-boston-$FNOW.sql"
REMOTE_BACKUP="$BACKUP_PATH$BACKUP_FILE_ZIP"

# Copy over the latest d7 prod backup.
scp -i $KEY_FILE $REMOTE_SERVER:REMOTE_BACKUP $LOCAL_PATH

# Load up the backup onto the local MySQL server.
if [ $? -eq 0 ] && [ -f "$LOCAL_PATH$BACKUP_FILE" ]; then
  LOCAL_BACKUP_ZIP="$LOCAL_PATH$BACKUP_FILE_ZIP"
  LOCAL_BACKUP="$LOCAL_PATH$BACKUP_FILE"
  # Remove existing DB.
  drush sql:drop --database=migrate -y
  # Unzip backup.
  gunzip $LOCAL_BACKUP_ZIP
  # Restore the prod backup.
  drush sql:cli --database=migrate < $LOCAL_BACKUP
  # Cleanup backup file.
  rm -f $LOCAL_BACKUP

  # Run the migration.
  ./bos_migration reset bostond8dev
fi
