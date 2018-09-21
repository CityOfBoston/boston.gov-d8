#!/bin/bash

set -e

# Every time we start up we need to make sure that we can write to this
# directory.
mkdir -p /app/docroot/sites/default/files
chown -R www-data /app/docroot/sites/default/files
chown -R www-data /app/docroot/sites/default/settings.php
chown -R www-data /app/docroot/sites/default/settings.local.php
mkdir -p /app/setup
chown -R www-data /app/setup

# We need to set the permissions here because on AWS this is a bind mount.
chmod 1777 /tmp

# Delegate to the default entrypoint.
docker-php-entrypoint "$@"
