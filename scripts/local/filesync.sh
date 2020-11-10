#!/bin/bash

# NOTE: This script is intended to be used inside a Lando managed container.

# This script/command will sync files from prod into container
# FLAGS/OPTIONS USED:
#   -arz = preserve all remote attributes, recurse into subdirs and compress transfer,
#   -P = show progress during transfer,
#   -essh = use ssh shell to connect to remote,
#   --exclude = exclude files matching the pattern (multiple excludes can be defined),
#   --max-size = do not copy files bigger than this value.

printf "Please wait while rsync summarizes the files that will be copied.\n"
rsync -arz \
    -essh \
    --stats \
    --dry-run \
    --exclude='*.pdf' \
    --exclude='*.xl*' \
    --exclude='*.doc*' \
    --max-size=2m \
    bostond8.prod@bostond8.ssh.prod.acquia-sites.com:/mnt/gfs/bostond8/sites/default/files \
    ${LANDO_MOUNT}/docroot/sites/default/files/

printf "This will copy the \"Total transferred file size\" above (potentially a lot of files) \n"
printf "from the remote production environment into this container.\n"
read -p "Are you sure you wish to do this? " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]
then
    exit 1
fi

rsync -arz \
    -essh \
    -P \
    --stats \
    --exclude='*.pdf' \
    --exclude='*.xl*' \
    --exclude='*.doc*' \
    --max-size=2m \
    bostond8.prod@bostond8.ssh.prod.acquia-sites.com:/mnt/gfs/bostond8/sites/default/files \
    ${LANDO_MOUNT}/docroot/sites/default/files/


