#!/bin/bash

function doMigrate() {
    SECONDS=0
    ERRORS=0
    while true; do
        printf "\nlando drush mim $*\n" | tee -a bos_migration.log
        lando drush mim $* | tee -a bos_migration.log
        if [ $? -eq 0 ]; then break; fi
        $ERRORS++
    done
    lando drush ms ${1} | tee -a bos_migration.log
    let "minutes=(SECONDS%3600)/60"
    let "seconds=(SECONDS%3600)%60"
    if [ $ERRORS -ne 0 ] || [ $? -ne 0 ]; then
        RED='\033[0;31m'
        printf "${RED}Migrate command completed with Errors.\n"  | tee -a bos_migration.log
    fi
    NC='\033[0m' # No Color
    printf " -> $minutes minute(s) and $seconds seconds.${NC}\n" | tee -a bos_migration.log
}

# Remove old database and restore baseline
mysql --host=172.21.0.2 --port=3306 -uroot --password= -e"DROP SCHEMA IF EXISTS drupal;"
mysql --host=172.21.0.2 --port=3306 -uroot --password= -e"CREATE SCHEMA drupal;"
mysql --host=172.21.0.2 --port=3306 -uroot --password= --database=drupal < /home/david/sources/boston.gov-d8/dump/migration/migration_clean_reset.sql

# Set migration variables.
lando drush sset "bos_migration.fileOps" "copy"
lando drush sset "bos_migration.dest_file_exists" "use\ existing"
lando drush sset "bos_migration.dest_file_exists_ext" "skip"
lando drush sset "bos_migration.remoteSource" "https://www.boston.gov/"
lando drush sset "bos_migration.active" "1"

# Sync current config with the database.
lando drush cim -y  | tee -a bos_migration.log
lando drush cim --partial --source=modules/custom/bos_migration/config/install/ -y  | tee -a bos_migration.log
# rebuild the migration configs.
lando drush updb -y  | tee -a bos_migration.log
lando drush cr  | tee -a bos_migration.log

# Perform the lowest level safe-dependencies.
doMigrate --tag="bos:initial:1" --force     # 20 Mins.
exit 0
# First pass.
doMigrate --tag="bos:taxonomy:1" --force          # 30 secs
doMigrate --tag="bos:paragraph:1" --force         # 21 min
doMigrate --group=bos_field_collection --force    # 2.2 min
doMigrate --tag="bos:taxonomy:2" --force          # 15 sec

# deps:tax/fc/para1
doMigrate --tag="bos:paragraph:2" --force         # 11 1mins then 3 min then another 3 mins
# newsletter 1 error
# social_media_links 1 error.

# deps:para2
doMigrate --tag="bos:paragraph:3" --force # 13 mins

 Components/sidebar-components
doMigrate --tag="bos:paragraph:4" --force         # 6 secs !

doMigrate --tag="bos:node:1" --force

doMigrate --tag="bos:node:2" --force

# deps:nodes
doMigrate --tag="bos:paragraph:5" --force         # 1.6 min

doMigrate --tag="bos:node_revision:1" --force

doMigrate --tag="bos:node_revision:2" --force

# Redo the internal_link (done in para:1)
doMigrate paragraph__internal_link --force
lando drush ms paragraph__internal_link

 Second pass.
doMigrate --group=d7_node --update --execute-dependencies

# Finish off.
doMigrate d7_menu_links,d7_menu,d7_block --force

lando drush sdel "bos_migration.active"
lando drush sset "bos_migration.fileOps" "copy"

exit 0

