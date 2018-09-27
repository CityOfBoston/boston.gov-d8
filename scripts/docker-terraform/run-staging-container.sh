#!/bin/bash

# Command for the drupal container when we deploy to the staging cluster on AWS.
# Similar to the init-docker-container.sh script run locally.
#
# Initializes the container and its database with the latest from Acquia staging
# by running the build:local task.

LightPurple='\033[1;35m'
Yellow='\033[1;33m'
NC='\033[0m' # No Color

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

# Implement the Drupal database according to the methodology indicated by ${BOSTON_DATABASE_MODE}.
echo -ne "${Yellow}Will build DB using ${BOSTON_DATABASE_MODE} model.${NC}\n"
if [ -z ${BOSTON_DATABASE_MODE+x} ]; then
		BOSTON_DATABASE_MODE=auto
		echo -ne "${Yellow}<Re-assigned> Will build DB using ${BOSTON_DATABASE_MODE} model.${NC}\n"
fi
if [ "${BOSTON_DATABASE_MODE}" != "none" ];then
		if [ "${BOSTON_DATABASE_MODE}" == "restore" ];then
				# Get any previously saved database dump.
				./scripts/doit/doit stash-db fetch
				if [ -f /tmp/dump.sql ]; then
					# If there is a previous dump, then use it rather than from a sync from staging.
					echo -ne "${LightPurple}Restore: phing setup:docker:drupal-terraform (dump)${NC}\n"
					phing -f ./scripts/phing/phing-boston.xml -Dproject.build_db_from=dump -Ddbdump.path='/tmp/dump.sql' setup:docker:drupal-terraform
				else
					echo -ne "${LightPurple}Restore: No backup found -> resets mode to sync${NC}\n"
					BOSTON_DATABASE_MODE=sync
				fi
		fi
		if [ "${BOSTON_DATABASE_MODE}" == "build" ];then
				echo -ne "${LightPurple}Build: phing setup:docker:drupal-terraform (build)${NC}\n"
				phing -f /app/build.xml -Dproject.build_db_from=build setup:docker:drupal-terraform
		elif [ "${BOSTON_DATABASE_MODE}" == "sync" ];then
				echo -ne "${LightPurple}Sync: phing setup:docker:drupal-terraform (sync)${NC}\n"
				phing -f /app/build.xml -Dproject.build_db_from=sync setup:docker:drupal-terraform
		elif [ "${BOSTON_DATABASE_MODE}" == "auto" ]; then
				# Get any previously saved database dump.
				echo -ne "${LightPurple}Auto: Retrieve database${NC}\n"
				/app/scripts/doit/doit stash-db fetch
				if [ -f /tmp/dump.sql ]; then
					# If there is a previous dump, then use it rather than from a sync from staging.
					echo -ne "${LightPurple}Auto: Database found.${NC}\n"
					echo -ne "${LightPurple}Auto(restore): phing setup:docker:drupal-terraform (dump)${NC}\n"
					phing -f /app/build.xml -Dproject.build_db_from=dump -Ddbdump.path='/tmp/dump.sql' setup:docker:drupal-terraform
				else
					# The default, which pulls from staging.
					echo -ne "${LightPurple}Auto: No Database found.${NC}\n"
					echo -ne "${LightPurple}Auto(sync): phing setup:docker:drupal-terraform (sync)${NC}\n"
					phing -f /app/build.xml -Dproject.build_db_from=sync setup:docker:drupal-terraform
				fi
		fi
fi

# Drush mapping - wont be there until build is finished ...
ln -sf /app/vendor/drush/drush/drush /usr/local/bin/
chmod a+rx /usr/local/bin/drush

# Drush mapping - wont be there until build is finished ...
ln -sf /app/vendor/phing/phing/bin/phing /usr/local/bin/
chmod a+rx /usr/local/bin/phing

# Necessary so that Apache can write proxied assets to the filesystem.
chown -R www-data /app/docroot/sites/default/files
chown -R www-data /app/docroot/sites/default/files
chown -R www-data /app/docroot/sites/default/settings.php
chmod 777 /app/docroot/sites/default/settings.php
chown -R www-data /app/docroot/sites/default/settings.local.php
chmod 777 /app/docroot/sites/default/settings.local.php

# Necessary to allow ssh activities
chmod 400 /app/.ssh/id_rsa
chmod 400 /home/digital/.ssh/id_rsa

# Now create the ssh tunnel for migrate versions.
if [ -n ${SSS_ON+x} ]; then
	autossh -M 0 -f -N -L ${SSH_LP}:127.0.0.1:${SSH_RP} -i /app/.ssh/id_rsa -4 -o "ServerAliveInterval 60" -o "ServerAliveCountMax 3" ${SSH_RH}
	if [ $? -ne 0 ]; then
		echo "WARNING: The SSH tunnel could not be initialized."
		echo "COMMAND: ssh -i /app/.ssh/id_rsa ${SSH_LP}:127.0.0.1:${SSH_RP} ${SSH_RH}"
	else
		echo "Migrate SSH tunnel has been initialized on port ${SSH_LP} to ${SSH_RH}."
		echo "Tunnel should remain up and connected while container is active."
		echo "Restart container to re-connect the tunnel."
	fi
fi

# Since we’re overriding the default Apache/PHP container’s command, we run this
# ourselves.
apache2-foreground
