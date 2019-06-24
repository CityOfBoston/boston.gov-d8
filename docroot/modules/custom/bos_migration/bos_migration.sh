#!/bin/bash

function doMigrate() {
    NC='\033[0m' # No Color
    RED='\033[0;31m'
    SECONDS=0
    ERRORS=0

    set +e

    while true; do
        printf "\nlando drush mim $*\n" | tee -a bos_migration.log
        lando drush mim $* | tee -a bos_migration.log
        retVal=$?
        echo "ExitCode: ${retVal}"
        if [ $retVal -eq 0 ]; then break; fi
        ((ERRORS++))
    done

    lando drush ms ${1} | tee -a bos_migration.log
    retVal=$?
    echo "ExitCode: ${retVal}"

    if [ $ERRORS -ne 0 ] || [ $retVal -ne 0 ]; then
        printf "${RED}Migrate command completed with Errors.${NC}\n"  | tee -a bos_migration.log
    fi
    let "minutes=(SECONDS%3600)/60"
    let "seconds=(SECONDS%3600)%60"
    printf " -> $minutes minute(s) and $seconds seconds.${NC}\n" | tee -a bos_migration.log
}

set +e

# Remove old database and restore baseline
RESTORE="/app/dump/migration/migration_clean_reset.sql"
RESTORE="/app/dump/migration/migration_clean_with_prereq.sql"
RESTORE="/app/dump/migration/migration_clean_after_taxonomy.sql"
#RESTORE="/app/dump/migration/migration_clean_after_field_collection.sql"

printf "RESTORING DB ${RESTORE}\n" | tee bos_migration.log
lando mysql -e"DROP SCHEMA IF EXISTS drupal;"
lando mysql -e"CREATE SCHEMA drupal;"
lando ssh -s database -c "mysql -uroot --password= --database=drupal < ${RESTORE}"

## Set migration variables.
#lando drush sset "bos_migration.fileOps" "copy"
#lando drush sset "bos_migration.dest_file_exists" "use\ existing"
#lando drush sset "bos_migration.dest_file_exists_ext" "skip"
#lando drush sset "bos_migration.remoteSource" "https://www.boston.gov/"
#lando drush sset "bos_migration.active" "1"
#
## Sync current config with the database.
##lando drush cim -y  | tee -a bos_migration.log
##lando drush cim --partial --source=modules/custom/bos_migration/config/install/ -y  | tee -a bos_migration.log
### rebuild the migration configs.
##lando drush updb -y  | tee -a bos_migration.log
lando drush cr  | tee -a bos_migration.log
lando drush ms  | tee -a bos_migration.log

# Migrate files first.
#doMigrate --tag="bos:initial:0" --force           # 31 mins
## Perform the lowest level safe-dependencies.
#doMigrate --tag="bos:initial:1" --force           # 7 mins
#lando ssh -s database -c "mysqldump --user=root --databases drupal > /app/dump/migration/migration_clean_with_prereq.sql"
# ==> NOTE Backup at this point is "migration_clean_with_prereq.sql"
#
## Taxonomies first.
#
#doMigrate d7_taxonomy_vocabulary -q --force       # 12 secs
#lando ssh -c"/app/vendor/bin/drush php-eval '\Drupal\bos_migration\migrationFixes::fixTaxonomyVocabulary();'"
#doMigrate --tag="bos:taxonomy:1" --force          # 30 secs
#doMigrate --tag="bos:taxonomy:2" --force          # 12 sec
#lando ssh -s database -c "mysqldump --user=root --databases drupal > /app/dump/migration/migration_clean_after_taxonomy.sql"
## ==> NOTE Backup at this point is "migration_clean_after_taxonomy.sql"

doMigrate --tag="bos:paragraph:1" --force         # 21 min
lando ssh -s database -c "mysqldump --user=root --databases drupal > /app/dump/migration/migration_clean_after_paragraphs1.sql"
exit 0
doMigrate --tag="bos:paragraph:2" --force         # 11 mins
lando ssh -s database -c "mysqldump --user=root --databases drupal > /app/dump/migration/migration_clean_after_paragraphs2.sql"
doMigrate --tag="bos:paragraph:3" --force         # 13 mins
lando ssh -s database -c "mysqldump --user=root --databases drupal > /app/dump/migration/migration_clean_after_paragraphs3.sql"
doMigrate --tag="bos:paragraph:4" --force         # 6 secs !
lando ssh -s database -c "mysqldump --user=root --databases drupal > /app/dump/migration/migration_clean_after_paragraphs4.sql"
doMigrate --tag="bos:paragraph:5" --force         # 1.6 min
lando ssh -s database -c "mysqldump --user=root --databases drupal > /app/dump/migration/migration_clean_after_all_paragraphs.sql"


# Do these last b/c creates new paragraphs that might steal existing paragraph entity & revision id's.
doMigrate --group=bos_field_collection --force    # 2.2 min
lando ssh -s database -c "mysqldump --user=root --databases drupal > /app/dump/migration/migration_clean_after_field_collection.sql"

doMigrate --tag="bos:node:1" --force
doMigrate --tag="bos:node:2" --force

# deps:nodes

doMigrate --tag="bos:node_revision:1" --force

doMigrate --tag="bos:node_revision:2" --force

# Redo the internal_link (done in para:1)
doMigrate paragraph__internal_link --force

 Second pass.
doMigrate --group=d7_node --update --execute-dependencies

# Finish off.
doMigrate d7_menu_links,d7_menu,d7_block --force

lando drush sdel "bos_migration.active"
lando drush sset "bos_migration.fileOps" "copy"

exit 0

