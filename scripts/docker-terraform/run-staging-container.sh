#!/bin/bash

# Command for the drupal container when we deploy to the staging cluster on AWS.
# Similar to the init-docker-container.sh script run locally.
#
# Initializes the container and its database with the latest from Acquia staging
# by running the build:local task.

set -e

if [ -z "$AWS_S3_CONFIG_URL" ]; then
  echo >&2 'error: missing AWS_S3_CONFIG_URL environment variable'
else
  # Pulls things down from our staging config S3 bucket.
  #
  # We include --no-follow-symlinks because there is a broken symlink in the
  # docroot (simplesaml) that causes aws to exit with status code 2, which
  # causes the script to exit. Since we don’t need symlinks for the sync, this
  # is fine.
  aws s3 sync --no-follow-symlinks $AWS_S3_CONFIG_URL .

  # We need this info to run drush commands in the Acquia cloud (for example, to
  # pull down the staging DB). This puts it in places where the tools will find
  # it.
  tar -C /root -xf ./acquia-cloud.drush-aliases.tar.gz
fi

# Implement the Drupa database according to the methodology indicated by ${BOSTON_DATABASE_MODE}.
if [ -z "${BOSTON_DATABASE_MODE}" ]; then
    BOSTON_DATABASE_MODE=auto
fi
if [ "${BOSTON_DATABASE_MODE}" = "restore" ];then
    # Get any previously saved database dump.
    ./scripts/doit/doit stash-db fetch
    if [ -f /tmp/dump.sql ]; then
      # If there is a previous dump, then use it rather than from a sync from staging.
      phing -f ./scripts/phing/phing-boston.xml -Dproject.build_db_from=dump -Ddbdump.path='/tmp/dump.sql' setup:docker:drupal-terraform
    else
      BOSTON_DATABASE_MODE=sync
    fi
fi
if [ "${BOSTON_DATABASE_MODE}" = "build" ];then
      phing -f ./scripts/phing/phing-boston.xml -Dproject.build_db_from=build setup:docker:drupal-terraform
elif [ "${BOSTON_DATABASE_MODE}" = "sync" ];then
      phing -f ./scripts/phing/phing-boston.xml -Dproject.build_db_from=sync setup:docker:drupal-terraform
elif [ "${BOSTON_DATABASE_MODE}" = "auto" ]; then
    # Get any previously saved database dump.
    ./scripts/doit/doit stash-db fetch
    if [ -f /tmp/dump.sql ]; then
      # If there is a previous dump, then use it rather than from a sync from staging.
      phing -f ./scripts/phing/phing-boston.xml -Dproject.build_db_from=dump -Ddbdump.path='/tmp/dump.sql' setup:docker:drupal-terraform
    else
      # The default, which pulls from staging.
      phing -f ./scripts/phing/phing-boston.xml -Dproject.build_db_from=sync setup:docker:drupal-terraform
    fi
fi

# Necessary so that Apache can write proxied assets to the filesystem.
chown -R www-data /app/docroot/sites/default/files

# Necessary to allow ssh activities
chmod 400 /app/.ssh/id_rsa

# Since we’re overriding the default Apache/PHP container’s command, we run this
# ourselves.
apache2-foreground
