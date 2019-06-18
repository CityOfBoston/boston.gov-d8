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
lando drush mim --tag="bos:initial:1" --force     # 20 Mins.

# First pass.
lando drush mim --tag="bos:taxonomy:1" --force          # 30 secs
lando drush ms --tag="bos:taxonomy:1"
lando drush mim --tag="bos:paragraph:1" --force         # 21 min
lando drush ms --tag="bos:paragraph:1"
lando drush mim --group=bos_field_collection --force    # 2.2 min
lando drush ms --group=bos_field_collection
lando drush mim --tag="bos:taxonomy:2" --force          # 15 sec
lando drush ms --tag="bos:taxonomy:2"
# deps:tax/fc/para1
lando drush mim --tag="bos:paragraph:2" --force         # 11 1mins then 3 min then another 3 mins
# newsletter 1 error
# social_media_links 1 error.
lando drush ms --tag="bos:paragraph:2"
# deps:para2
lando drush mim --tag="bos:paragraph:3" --force         # 13 mins
lando drush ms --tag="bos:paragraph:3"
# Components/sidebar-components
lando drush mim --tag="bos:paragraph:4" --force         # 6 secs !
lando drush ms --tag="bos:paragraph:4"
lando drush mim --tag="bos:node:1" --force
lando drush ms --tag="bos:node:1"

# deps:nodes
lando drush mim --tag="bos:paragraph:5" --force         # 1.6 min
lando drush ms --tag="bos:paragraph:5"
lando drush mim --tag="bos:node:2" --force
lando drush ms --tag="bos:node:2"

lando drush mim --tag="bos:node_revision:1" --force
lando drush ms --tag="bos:node_revision:1"
lando drush mim --tag="bos:node_revision:2" --force
lando drush ms --tag="bos:node_revision:2"


# Redo the internal_link (done in para:1)
lando drush mim paragraph__internal_link --update
lando drush ms paragraph__internal_link


# Second pass.
lando drush mim --group=d7_node --update --execute-dependencies
# Finish off.
lando drush mim d7_menu_links,d7_menu,d7_block

#lando drush sdel "bos_migration.active"
#lando drush sset "bos_migration.fileOps" "copy"
