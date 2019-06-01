#!/bin/bash

# Remove old database and restore baseline
mysql --host=172.21.0.4 --port=3306 -uroot --password= -e"DROP SCHEMA IF EXISTS drupal;"
mysql --host=172.21.0.4 --port=3306 -uroot --password= -e"CREATE SCHEMA drupal;"
mysql --host=172.21.0.4 --port=3306 -uroot --password= --database=drupal < /home/david/sources/boston.gov-d8/dump/migration/migration_clean_reset.sql

# Sync current config with the database.
lando drush cim -y
lando drush cim --partial --source=modules/custom/bos_migration/config/install/ -y;
lando drush cr

# Perorm the lowest level safe-dependencies.
lando drush sset "bos_migration.fileOps" "none"
lando drush sset "bos_migration.active" "1"
lando drush mim --tag="bos:initial:1" --force     #OK

# First pass.
lando drush mim --tag="bos:taxonomy:1" --force
lando drush mim --tag="bos:paragraph:1" --force
lando drush mim --group=bos_field_collection --force
lando drush mim --tag="bos:taxonomy:2" --force
lando drush mim --tag="bos:paragraph:2" --force # deps:tax/fc/para1
lando drush mim --tag="bos:paragraph:3" --force # deps:para2
lando drush mim --tag="bos:paragraph:4" --force # Components/sidebar-components
lando drush mim --tag="bos:node:1" --force
lando drush mim --tag="bos:paragraph:5" --force # deps:nodes
lando drush mim --tag="bos:node:2" --force

# Second pass.
drush mim --group=d7_nodes --update --execute-dependencies
# Finish off.
drush mim d7_menu_links,d7_menu,d7_block
drush mim d7_rdf_mapping

#lando drush sdel "bos_migration.active"
#lando drush sset "bos_migration.fileOps" "copy"
