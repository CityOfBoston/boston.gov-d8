#!/bin/bash -e

# This script makes the initial install for a new Boston dev environment
# PREREQUISITES:
#   1. Installed lando, docker and git on host,
#   2. Cloned boston repo.

DRUSH_ALIAS_FOR_DB="@boston.test"

# Start the container and run any lando scripts.
# relies on the .lando.yml file and drupal8 recipe.
lando start

# run the container install script (as root)
lando ssh -u=root -c "scripts/lando/install_root.sh"

# Install drupal and modules and libraries
lando composer install

# run the container install script
lando ssh -c "scripts/lando/install.sh"
# copy down the acquia staging database
# use switches to minimise the DB size.
#lando drush sql-drop -y
#lando drush sql-sync ${DRUSH_ALIAS_FOR_DB} @self --create-db --structure-tables-key=lightweight -y
#lando drush cr

# features revert (?)

# Run any hooks.
# lando drush updb