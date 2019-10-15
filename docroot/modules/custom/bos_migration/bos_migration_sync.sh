#!/bin/bash

# This script should run after the $REMOTE_SERVER environment has done its backup (eg 0300 UTC -0400).

KEY_FILE="/mnt/files/bostond8dev/files-private/private/acquia_migrate"
REMOTE_SERVER="boston.prod@web-15135.prod.hosting.acquia.com"
# Paths end with '/'.
BACKUP_PATH="/mnt/files/boston/backups/"
LOCAL_PATH="/mnt/files/bostond8dev/backups/on-demand/"

# Define the latest backup.
FNOW=$(date +"%Y-%m-%d")

# Copy over the latest d7 prod backup.
printf "\n== START MIGRATION SYNC ==\n\n"
count=0
while true; do
  BACKUP_FILE_ZIP="prod-boston-boston-$FNOW.sql.gz"
  REMOTE_BACKUP="$BACKUP_PATH$BACKUP_FILE_ZIP"

  printf " [info] Will copy current prod drupal 7 DB (${REMOTE_BACKUP}) from ${REMOTE_SERVER}\n"
  scp -i $KEY_FILE $REMOTE_SERVER:$REMOTE_BACKUP $LOCAL_PATH

  if [ -f "$LOCAL_PATH$BACKUP_FILE_ZIP" ]; then
    printf " [info] Copied.\n"
    break
  fi
  printf " [info] ${REMOTE_BACKUP} NOT FOUND...\n"
  count=$((count+24))
  FNOW=$(date -d "$count hours ago" +"%Y-%m-%d")
done

# Load up the backup onto the local MySQL server.
BACKUP_FILE="prod-boston-boston-$FNOW.sql"
LOCAL_BACKUP_ZIP="$LOCAL_PATH$BACKUP_FILE_ZIP"
if [ $? -eq 0 ] && [ -f "$LOCAL_BACKUP_ZIP" ]; then
  printf " [success] D7 backup copied to ${LOCAL_PATH}\n"
  LOCAL_BACKUP="$LOCAL_PATH$BACKUP_FILE"
  # Remove existing DB.
  printf " [info] Remove current D7 database on (local) bostond8 MySQL server.\n"
  drush sql:drop --database=migrate -y
  printf " [success] (local) D7 database dropped.\n"
  # Unzip backup.
  gunzip -fq $LOCAL_BACKUP_ZIP
  # Restore the prod backup.
  printf " [info] Load ${LOCAL_PATH} alongside current Drupal8 DB on local MySQL.\n"
  drush sql:cli --database=migrate < $LOCAL_BACKUP
  printf " [success] Drupal 7 database installed.\n"
  # Cleanup backup file.
  rm -f $LOCAL_BACKUP

  # Run the migration.
  printf " [info] Launch normal migration.\n"
  ./bos_migration.sh reset bostond8dev
else
  printf " [error] DB NOT COPIED.\n"
fi
printf "\n== END MIGRATION SYNC ==\n"

