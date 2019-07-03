#!/bin/bash

function doMigrate() {
    NC='\033[0m' # No Color
    RED='\033[0;31m'
    SECONDS=0
    ERRORS=0

    while true; do
        printf "\nlando drush mim $*\n" | tee -a bos_migration.log
        if [ true ]; then
            lando drush mim $* --feedback=1000| tee -a bos_migration.log
        fi
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

    printf " -> Run time: " | tee -a bos_migration.log
    if (( $SECONDS > 3600 )); then
        let "hours=SECONDS/3600"
        text="hour"
        if (( $hours > 1 )); then text="hours"; fi
        printf "$hours $text, " | tee -a bos_migration.log
    fi
    if (( $SECONDS > 60 )); then
        let "minutes=(SECONDS%3600)/60"
        text="minute"
        if (( $minutes > 1 )); then text="minutes"; fi
        printf "$minutes $text and " | tee -a bos_migration.log
    fi
    let "seconds=(SECONDS%3600)%60"
    text="second"
    if (( $seconds > 1 )); then text="seconds"; fi
    printf "$seconds $text.${NC}\n" | tee -a bos_migration.log
}

# Remove old database and restore baseline
RESTORE="/app/dump/migration/migration_clean_reset.sql"
#RESTORE="/app/dump/migration/migration_clean_with_prereq.sql"
#RESTORE="/app/dump/migration/migration_clean_after_taxonomy.sql"
#RESTORE="/app/dump/migration/migration_clean_after_all_paragraphs.sql"
#RESTORE="/app/dump/migration/migration_clean_after_field_collection.sql"
#RESTORE="/app/dump/migration/migration_clean_after_para_update_1.sql"
#RESTORE="/app/dump/migration/migration_clean_after_nodes.sql"
#RESTORE="/app/dump/migration/migration_clean_after_para_update_2.sql"
#RESTORE="/app/dump/migration/migration_clean_after_node_revision1.sql"
#RESTORE="/app/dump/migration/migration_clean_after_node_revision.sql"
#RESTORE="/app/dump/migration/migration_FINAL.sql"

printf "RESTORING DB ${RESTORE}\n" | tee bos_migration.log
lando mysql -e"DROP SCHEMA IF EXISTS drupal;"
lando mysql -e"CREATE SCHEMA drupal;"
lando ssh -s database -c "mysql -uroot --password= --database=drupal < ${RESTORE}"

# Set migration variables.
lando drush sset "bos_migration.fileOps" "copy"
lando drush sset "bos_migration.dest_file_exists" "use\ existing"
lando drush sset "bos_migration.dest_file_exists_ext" "skip"
lando drush sset "bos_migration.remoteSource" "https://www.boston.gov/"
lando drush sset "bos_migration.active" "1"

## Sync current config with the database.
lando drush cim -y  | tee -a bos_migration.log
lando drush cim --partial --source=modules/custom/bos_migration/config/install/ -y  | tee -a bos_migration.log
# rebuild the migration configs.
lando drush updb -y  | tee -a bos_migration.log
lando drush entup -y  | tee -a bos_migration.log
lando ssh -c"/app/vendor/bin/drush php:eval 'node_access_rebuild();'"
lando drush cr  | tee -a bos_migration.log
lando drush ms  | tee -a bos_migration.log

## Migrate files first.
doMigrate --tag="bos:initial:0" --force                 # 31 mins

## Perform the lowest level safe-dependencies.
doMigrate --tag="bos:initial:1" --force                 # 7 mins
lando ssh -s database -c "mysqldump --user=root --databases drupal > /app/dump/migration/migration_clean_with_prereq.sql"

## Taxonomies first.
doMigrate d7_taxonomy_vocabulary -q --force             # 6 secs
lando ssh -c"/app/vendor/bin/drush php-eval '\Drupal\bos_migration\MigrationFixes::fixTaxonomyVocabulary();'"
doMigrate --tag="bos:taxonomy:1" --force                # 30 secs
doMigrate --tag="bos:taxonomy:2" --force                # 12 sec
lando ssh -s database -c "mysqldump --user=root --databases drupal > /app/dump/migration/migration_clean_after_taxonomy.sql"

doMigrate --tag="bos:paragraph:1" --force               # 27 mins
doMigrate --tag="bos:paragraph:2" --force               # 17 mins
doMigrate --tag="bos:paragraph:3" --force               # 14 mins
doMigrate --tag="bos:paragraph:4" --force               # 1 min 15 secs
lando ssh -s database -c "mysqldump --user=root --databases drupal > /app/dump/migration/migration_clean_after_all_paragraphs.sql"

## Do these last b/c creates new paragraphs that might steal existing paragraph entity & revision id's.
doMigrate --group=bos_field_collection --force          # 4 mins
lando ssh -s database -c "mysqldump --user=root --databases drupal > /app/dump/migration/migration_clean_after_field_collection.sql"

# Redo paragraphs which required field_collections to be migrated to para's first.
doMigrate --tag="bos:paragraph:10" --force --update      # 3 min 15 secs
# Fix the listview component to match new view names and displays.
lando ssh -c"/app/vendor/bin/drush php-eval '\Drupal\bos_migration\MigrationFixes::fixListViewField();'"
lando ssh -s database -c "mysqldump --user=root --databases drupal > /app/dump/migration/migration_clean_after_para_update_1.sql"

# Migrate nodes in sequence.
doMigrate --tag="bos:node:1" --force
doMigrate --tag="bos:node:2" --force                    # 14 mins
doMigrate --tag="bos:node:3" --force                    # 52 mins
doMigrate --tag="bos:node:4" --force                    # 9 secs
lando ssh -s database -c "mysqldump --user=root --databases drupal > /app/dump/migration/migration_clean_after_nodes.sql"

# Redo para's which have nodes in fields.
doMigrate --tag="bos:paragraph:99" --force --update     # 5 mins
lando ssh -s database -c "mysqldump --user=root --databases drupal > /app/dump/migration/migration_clean_after_para_update_2.sql"

# Now do the node revisions (nodes and all paras must be done first)
doMigrate --tag="bos:node_revision:1" --force           # 2h 42 mins
doMigrate --tag="bos:node_revision:2" --force           # 8h 50 mins
doMigrate --tag="bos:node_revision:3" --force           # 1hr 43 mins
doMigrate --tag="bos:node_revision:4" --force           # 30 sec
lando ssh -s database -c "mysqldump --user=root --databases drupal > /app/dump/migration/migration_clean_after_node_revision.sql"

## Finish off.
doMigrate d7_menu_links,d7_menu --force

## Ensure everything is updated.
lando drush entup -y  | tee -a bos_migration.log
lando ssh -c"/app/vendor/bin/drush php:eval 'node_access_rebuild();'"
lando ssh -s database -c "mysqldump --user=root --databases drupal > /app/dump/migration/migration_FINAL.sql"

lando drush sdel "bos_migration.active"
lando drush sset "bos_migration.fileOps" "copy"
