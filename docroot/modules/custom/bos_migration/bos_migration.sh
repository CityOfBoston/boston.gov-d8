#!/bin/bash

function displayTime() {
  elapsed=${1};
  if (( $elapsed > 3600 )); then
      let "hours=elapsed/3600"
      text="hour"
      if (( $hours > 1 )); then text="hours"; fi
      hours="$hours $text, "
  fi
  if (( $elapsed > 60 )); then
      let "minutes=(elapsed%3600)/60"
      text="minute"
      if (( $minutes > 1 )); then text="minutes"; fi
      minutes="$minutes $text and "
  fi
  let "seconds=(elapsed%3600)%60"
  text="second"
  if (( $seconds > 1 )); then text="seconds"; fi
  seconds="$seconds $text."

  echo "${hours} ${minutes} ${seconds}"
}

function doMigrate() {
    NC='\033[0m' # No Color
    RED='\033[0;31m'
    timer=$(date +%s)
    CYCLE=0
    COMMAND="$*"
    GROUP="${1}"
    FEEDBACK="500"

    testseq="feedback"
    if [[ ! ${*} =~ $testseq ]]; then
      COMMAND="${COMMAND} --feedback=$FEEDBACK"
    fi

    while true; do
        printf "[migration-step] ${drush} mim $COMMAND\n" | tee -a ${logfile}

        retval=0
        (${drush} mim $COMMAND >> ${logfile}) || retval=1
        if [[ $retval -eq 0 ]]; then break; fi

        hanging="$(${drush} ms ${GROUP} --fields=id,status --format=tsv | grep Importing | awk '{print $1}')"
        if [ "${hanging}" != "" ]; then
          ${drush} mrs "${hanging}"
        else
          # If there are no migrations still importing, then terminate.
          printf "[migration-warning] Migration reported errors, but no incompleted migrations found in group. \n"
          break
        fi

        CYCLE=$((CYCLE+1))
        if [ $CYCLE -gt 10 ]; then
          printf "[migration-warning] Too many errors in ${GROUP} migration.\n" | tee -a ${logfile}
          break;
        fi
    done

    ${drush} ms "${GROUP}" --fields=id,status,total,imported,unprocessed| tee -a ${logfile}

    if [ $CYCLE -ne 0 ]; then
        printf "[migration-warning] ${RED}Migrate command completed with Errors.${NC}\n"  | tee -a ${logfile}
    fi

    text=$(displayTime $(($(date +%s)-timer)))
    printf "[migration-runtime] ${text}\n\n" | tee -a ${logfile}
}

function doExecPHP() {
    timer=$(date +%s)

    printf "[migration-step] Executing PHP: '%q'\n" "${*}" | tee -a ${logfile}
    if [ -d "/mnt/gfs" ]; then
        ${drush} php-eval "$*"  | tee -a ${logfile}
    else
        lando ssh -c  "/app/vendor/bin/drush php-eval $*"  | tee -a ${logfile}
    fi

    text=$(displayTime $(($(date +%s)-timer)))
    printf "[migration-runtime] ${text}\n\n" | tee -a ${logfile}
}

function restoreDB() {
    # Remove old database and restore baseline
    timer=$(date +%s)
    printf "[migration-step] Restoring Database ${1}\n" | tee -a ${logfile}

    backup=${1}

    ${drush} sql:drop --database=default -y  | tee -a ${logfile}

    if [ ! -f "${backup}" ];then
        if [ ${backup: -3} == ".gz" ]; then
            printf "[migration-info] ${backup} not found looking for unzipped backup.\n" | tee -a ${logfile}
            backup=$(basename ${backup} .gz)
            backup="${dbpath}/${backup}"
        else
            printf "[migration-warning] ${backup} not found looking for zipped backup.\n" | tee -a ${logfile}
            backup="${backup}.gz"
        fi
    fi

    if [ -f "${backup}" ];then
        if [ ${backup: -3} == ".gz" ]; then
            printf "[migration-info] unzipping ${backup}.\n" | tee -a ${logfile}
            gunzip -fq ${backup}
            backup=$(basename ${backup} .gz)
            backup="${dbpath}/${backup}"
        fi
    else
        printf "[migration-error] a suitable backup file could not be found.\n" | tee -a ${logfile}
        printf "[migration-info] Script aborting.\n\n" | tee -a ${logfile}
        exit 1
    fi

    printf "[migration-info] Import ${backup} file into MySQL\n" | tee -a ${logfile}
    if [ -d "/mnt/gfs" ]; then
        ${drush} sql:cli -y --database=default < ${backup}  | tee -a ${logfile}
    else
        lando ssh -c  "/app/vendor/bin/drush sql:cli -y  < ${backup}" | tee -a ${logfile}
    fi

    printf "[migration-info] Re-zip backup.\n" | tee -a ${logfile}
    gzip -fq ${backup}

    printf "[migration-info] Sync database wih current code.\n" | tee -a ${logfile}
    ## Sync current config with the database.
    ${drush} cim -y  | tee -a ${logfile}
    printf "\n" | tee -a ${logfile}

    # Ensure the needed modules are enabled.
    printf "[migration-info] Enable migration modules.\n" | tee -a ${logfile}
    ${drush} cdel views.view.migrate_taxonomy
    ${drush} cdel views.view.migrate_paragraphs
    ${drush} en migrate,migrate_upgrade,migrate_drupal,migrate_drupal_ui,field_group_migrate,migrate_plus,migrate_tools,bos_migration,config_devel,migrate_utilities -y  | tee -a ${logfile}
    printf "\n" | tee -a ${logfile}

    printf "[migration-info] Load bos_migration (migrate_plus) configs for good measure.\n" | tee -a ${logfile}
    ${drush} cim --partial --source=modules/custom/bos_migration/config/install/ -y  | tee -a ${logfile}
    printf "\n" | tee -a ${logfile}

    # rebuild the migration configs.
    printf "[migrate-info] Check for and run any database and module hook_updates.\n" | tee -a ${logfile}
    ${drush} updb -y  | tee -a ${logfile}
    printf "\n" | tee -a ${logfile}

    printf "[migrate-info] Rebuild permissions on nodes.\n" | tee -a ${logfile}
    doExecPHP "node_access_rebuild();"
    printf "\n" | tee -a ${logfile}

    # Set migration variables.
    printf "[migrate-info] Set migration variables (states).\n" | tee -a ${logfile}
    ${drush} sset "bos_migration.fileOps" "copy" | tee -a ${logfile}
    ${drush} sset "bos_migration.dest_file_exists" "use\ existing" | tee -a ${logfile}
    ${drush} sset "bos_migration.dest_file_exists_ext" "skip" | tee -a ${logfile}
    ${drush} sset "bos_migration.remoteSource" "https://www.boston.gov/" | tee -a ${logfile}
    ${drush} sset "bos_migration.active" "1" | tee -a ${logfile}
    printf "\n" | tee -a ${logfile}

    printf "[migrate-info] Rebuild caches.\n" | tee -a ${logfile}
    ${drush} cr  | tee -a ${logfile}
    printf "\n" | tee -a ${logfile}

    printf "[migrate-info] Printout current migration status.\n" | tee -a ${logfile}
    ${drush} ms  | tee -a ${logfile}
    printf "\n" | tee -a ${logfile}

    printf "[migration-success] Database has been restored and synchronised with current branch.\n" | tee -a ${logfile}
    text=$(displayTime $(($(date +%s)-timer)))
    printf "[migration-runtime] ${text}\n\n" | tee -a ${logfile}

    ## Takes site out of maintenance mode before dumping.
    ${drush} sset "system.maintenance_mode" "0"

    dumpDB ${1}

    ## Puts site into maintenance mode while migration occurs.
    ${drush} sset "system.maintenance_mode" "1"
}

function dumpDB() {
    # Dump current DB.
    timer=$(date +%s)
    backup=${1}
    printf "[migration-step] Dump DB ${backup}\n" | tee -a ${logfile}
    if [ -d "/mnt/gfs" ]; then
        ${drush} sql:dump -y --database=default > ${backup}
    else
        lando ssh -c  "/app/vendor/bin/drush sql:dump -y > ${backup}"
    fi
    gzip -fq ${backup}
    printf "[migration-success] Database (default) dumped to ${backup}.gz.\n" | tee -a ${logfile}
    text=$(displayTime $(($(date +%s)-timer)))
    printf "[migration-runtime] ${text}\n\n" | tee -a ${logfile}
}

function removeEmptyFiles() {
    printf  "[migration-step] Remove the following zero-byte images:\n"| tee -a ${logfile}
    find /mnt/gfs/${acquia_env}/sites/default/files -type f -size 0b -print  | tee -a ${logfile}
    find /mnt/gfs/${acquia_env}/sites/default/files -type f -size 0b -delete && printf "[migration-success] Images deleted\n\n" | tee -a ${logfile}
    # ${drush} sql:query -y --database=default "DELETE FROM file_managed where filesize=0;" | tee -a ${logfile}
}

# Rotate log files, keeping the last 5.
function doLogRotate() {
  SCRIPT=${1}
  # Write the logrotate config script.
  rm -f {$SCRIPT}
  echo "nocompress" > {$SCRIPT}
  echo "${logfile} {" > {$SCRIPT}
  echo "  rotate 5" > {$SCRIPT}
  echo "  missingok" > {$SCRIPT}
  echo "}" > {$SCRIPT}
  #  Now run the script.
  logrotate -d {$SCRIPT}
  #  Cleanup
  rm -f {$SCRIPT}
}

}
acquia_env="${AH_SITE_NAME}"
if [ ! -z $2 ]; then
    acquia_env="${2}"
fi



if [ -d "/mnt/gfs" ]; then
    cd "/var/www/html/${acquia_env}/docroot"
    dbpath="/mnt/gfs/${acquia_env}/backups/on-demand"
    logfile="/mnt/gfs/${acquia_env}/sites/default/files/bos_migration.log"
    drush="drush"
    doLogRotate "/mnt/gfs/${acquia_env}/sites/default/files/bos_migration.cfg"
    printf "[migration-info] Running in REMOTE mode:\n"| tee ${logfile}
else
#    dbpath=" ~/sources/boston.gov-d8/dump/migration"
    cd  ~/sources/boston.gov-d8/docroot
    dbpath=" /app/dump/migration"
    logfile="./bos_migration.log"
    drush="lando drush"
    printf "[migration-info] Running in LOCAL DOCKER mode:\n"| tee ${logfile}
fi

printf "[migration-start] Starts %s %s\n\n" $(date +%F\ %T ) | tee ${logfile}

running=0

totaltimer=$(date +%s)
## Migrate files first.
if [ "$1" == "reset" ]; then
    running=1
    ## Remove zero byte images.  These sometimes migrate in because the file copy comes across HTTP.
    removeEmptyFiles
    ##
    restoreDB "${dbpath}/migration_clean_reset.sql" || exit 1
    doMigrate --tag="bos:initial:0" --force                 # 31 mins
    doExecPHP "\Drupal\bos_migration\MigrationFixes::fixFilenames();"
    doExecPHP "\Drupal\bos_migration\MigrationFixes::updateSvgPaths();"
    doExecPHP "\Drupal\bos_migration\MigrationFixes::createMediaFromFiles();"
    doExecPHP "\Drupal\bos_migration\MigrationFixes::createMediaFromFiles();"
    doExecPHP "\Drupal\bos_migration\MigrationFixes::createMediaFromFiles();"
    dumpDB ${dbpath}/migration_clean_with_files.sql
fi

## Perform the lowest level safe-dependencies.
if [ "$1" == "files" ] || [ $running -eq 1 ]; then
    running=1
    if [ "$1" == "files" ]; then restoreDB "${dbpath}/migration_clean_with_files.sql" || exit 1; fi
    doMigrate --tag="bos:initial:1" --force                 # 7 mins
    dumpDB ${dbpath}/migration_clean_with_prereq.sql
fi

# Taxonomies first.
if [ "$1" == "prereq" ] || [ $running -eq 1 ]; then
    running=1
    if [ "$1" == "prereq" ]; then restoreDB "${dbpath}/migration_clean_with_prereq.sql" || exit 1; fi
    doMigrate d7_taxonomy_vocabulary -q --force             # 6 secs
    doExecPHP "\Drupal\bos_migration\MigrationFixes::fixTaxonomyVocabulary();"
    doMigrate --tag="bos:taxonomy:1" --force                # 30 secs
    doMigrate --tag="bos:taxonomy:2" --force                # 12 sec
    dumpDB ${dbpath}/migration_clean_after_taxonomy.sql
fi

if [ "$1" == "taxonomy" ] || [ $running -eq 1 ]; then
    running=1
    if [ "$1" == "taxonomy" ]; then restoreDB "${dbpath}/migration_clean_after_taxonomy.sql" || exit 1; fi
    doMigrate --tag="bos:paragraph:1" --force               # 27 mins
    doMigrate --tag="bos:paragraph:2" --force               # 17 mins
    doMigrate --tag="bos:paragraph:3" --force               # 14 mins
    doMigrate --tag="bos:paragraph:4" --force               # 1 min 15 secs
    dumpDB ${dbpath}/migration_clean_after_all_paragraphs.sql
fi

## Do these last b/c creates new paragraphs that might steal existing paragraph entity & revision id's.
if [ "$1" == "paragraphs" ] || [ $running -eq 1 ]; then
    running=1
    if [ "$1" == "paragraphs" ]; then restoreDB "${dbpath}/migration_clean_after_all_paragraphs.sql" || exit 1; fi
    doMigrate --group=bos_field_collection --force          # 4 mins
    dumpDB ${dbpath}/migration_clean_after_field_collection.sql
fi

# Redo paragraphs which required field_collections to be migrated to para's first.
if [ "$1" == "field_collection" ] || [ $running -eq 1 ]; then
    running=1
    if [ "$1" == "field_collection" ]; then restoreDB "${dbpath}/migration_clean_after_field_collection.sql" || exit 1; fi
    doMigrate --tag="bos:paragraph:10" --force --update      # 3 min 15 secs
    # Fix the listview component to match new view names and displays.
    dumpDB ${dbpath}/migration_clean_after_para_update_1.sql
fi

# Migrate nodes in sequence.
if [ "$1" == "update1" ] || [ $running -eq 1 ]; then
    running=1
    if [ "$1" == "update1" ]; then restoreDB "${dbpath}/migration_clean_after_para_update_1.sql" || exit 1; fi
    doMigrate --tag="bos:node:1" --force
    doMigrate --tag="bos:node:2" --force                    # 14 mins
    doMigrate --tag="bos:node:3" --force                    # 52 mins
    doMigrate --tag="bos:node:4" --force                    # 9 secs
    dumpDB ${dbpath}/migration_clean_after_nodes.sql
fi

# Redo para's which have nodes in fields.
if [ "$1" == "nodes" ] || [ $running -eq 1 ]; then
    running=1
    if [ "$1" == "nodes" ]; then restoreDB "${dbpath}/migration_clean_after_nodes.sql" || exit 1; fi
    doMigrate --tag="bos:paragraph:99" --force --update     # 5 mins
    dumpDB ${dbpath}/migration_clean_after_para_update_2.sql
fi

# Now do the node revisions (nodes and all paras must be done first)
if [ "$1" == "update2" ] || [ $running -eq 1 ]; then
    running=1
    if [ "$1" == "update2" ]; then restoreDB "${dbpath}/migration_clean_after_para_update_2.sql" || exit 1; fi
    doMigrate --tag="bos:node_revision:1" --force --feedback=350           # 2h 42 mins
    doMigrate --tag="bos:node_revision:2" --force --feedback=350          # 8h 50 mins
    doMigrate --tag="bos:node_revision:3" --force --feedback=350          # 1hr 43 mins
    doMigrate --tag="bos:node_revision:4" --force --feedback=350          # 30 sec
    dumpDB ${dbpath}/migration_clean_after_node_revision.sql
fi

# This is to resume when the node_revsisions fail mid-way.
if [ "$1" == "revision_resume" ]; then
  printf "\n[migration-info] Continues from previous migration %s %s\n" $(date +%F\ %T ) | tee ${logfile}
  running=1
  ${drush} sset "bos_migration.fileOps" "copy" | tee -a ${logfile}
  ${drush} sset "bos_migration.dest_file_exists" "use\ existing" | tee -a ${logfile}
  ${drush} sset "bos_migration.dest_file_exists_ext" "skip" | tee -a ${logfile}
  ${drush} sset "bos_migration.remoteSource" "https://www.boston.gov/" | tee -a ${logfile}
  ${drush} sset "bos_migration.active" "1" | tee -a ${logfile}
  ${drush} cdel views.view.migrate_taxonomy
  ${drush} cdel views.view.migrate_paragraphs
  ${drush} en migrate,migrate_upgrade,migrate_drupal,migrate_drupal_ui,field_group_migrate,migrate_plus,migrate_tools,bos_migration,config_devel,migrate_utilities -y  | tee -a ${logfile}
  ${drush} cim --partial --source=modules/custom/bos_migration/config/install/ -y  | tee -a ${logfile}
  ${drush} cr  | tee -a ${logfile}

  doMigrate --tag="bos:node_revision:1" --force --feedback=350           # 2h 42 mins
  doMigrate --tag="bos:node_revision:2" --force --feedback=350           # 8h 50 mins
  doMigrate --tag="bos:node_revision:3" --force --feedback=350           # 1hr 43 mins
  doMigrate --tag="bos:node_revision:4" --force --feedback=350           # 30 sec
  dumpDB ${dbpath}/migration_clean_after_node_revision.sql
fi

## Finish off.
if [ "$1" == "node_revision" ] || [ $running -eq 1 ]; then
    running=1
    if [ "$1" == "node_revision" ]; then restoreDB "${dbpath}/migration_clean_after_node_revision.sql" || exit 1; fi
    doMigrate d7_menu_links,d7_menu --force
    doMigrate d7_taxonomy_term:contact --force --update
    dumpDB ${dbpath}/migration_clean_after_menus.sql
fi

if [ "$1" == "final" ]; then
    running=1
    restoreDB "${dbpath}/migration_clean_after_menus.sql"
fi

if [ $running -eq 0 ]; then
    printf "[migration-error] Bad script parameter\nOptions are:\n  reset, files, rereq, taxonomy, paragraphs, field_collection, update1, nodes, update2, node_revision, menus, final" | tee -a ${logfile}
    exit 1
fi

# Just run an update on all entities to be sure everything is in sync.
printf "\n[migration-step] Update Entities.\n" | tee -a ${logfile}
#doMigrate --group=bos_paragraphs --update --feedback=1000
#doMigrate --group=d7_node --update --feedback=1000

## Check all migrations completed.
printf "[migration-step] Check status of migration.\n" | tee -a ${logfile}
ERRORS=0
while true; do
    hanging="$(drush ms --fields=id,status --format=tsv | grep Importing | awk '{print $1}')"
    if [ -z "${hanging}" ] || [ "${hanging}" == "" ]; then break; fi
    ERRORS=$((ERRORS+1))
    if [ $ERRORS -gt 5 ]; then
      printf "[migration-warning] Too many errors.\n" | tee -a ${logfile}
      break;
    fi

    IFS=' ' read -r -a array <<< "${hanging}"
    for element in "${array[@]}"; do
      ${drush} mrs "${element}"
      printf "[migration-info] Will attempt to re-run partial import found for ID ${element}.\n" | tee -a ${logfile}
      doMigrate ${element} --force --feedback=500
    done
done

## Ensure everything is updated.
if [ "{$1}" != "reset" ]; then
    doExecPHP "\Drupal\bos_migration\MigrationFixes::fixFilenames();"
fi
doExecPHP "\Drupal\bos_migration\MigrationFixes::fixRevisions();"
doExecPHP "\Drupal\bos_migration\MigrationFixes::fixPublished();"
doExecPHP "\Drupal\bos_migration\MigrationFixes::fixListViewField();"
doExecPHP "\Drupal\bos_migration\MigrationFixes::fixMap();"
doExecPHP "\Drupal\bos_migration\MigrationFixes::migrateMessages();"
# re-run this to update icons broght through in WYSIWYG (rich-text) content.
doExecPHP "\Drupal\bos_migration\MigrationFixes::updateSvgPaths();"

# Reset status_items.
doExecPHP "\Drupal\migrate_utilities\MigUtilTools::deleteContent(['node' => 'status_item']);"
doExecPHP "\Drupal\migrate_utilities\MigUtilTools::loadSetup('node_status_item');"

doMigrate d7_menu_links,d7_menu --force
doExecPHP "node_access_rebuild();"

printf "[migration-step] Show final migration status.\n" | tee -a ${logfile}
${drush} ms  | tee -a ${logfile}

# Takes site out of maintenance mode when migration is done.
printf "[migration-step] Re-import configuration.\n" | tee -a ${logfile}
${drush} cim -y  | tee -a ${logfile}

printf "[migration-step] Finish off migration: reset caches and maintenance mode.\n" | tee -a ${logfile}
${drush} sset "system.maintenance_mode" "0"
${drush} sdel "bos_migration.active"
${drush} sset "bos_migration.fileOps" "copy"
${drush} cr  | tee -a ${logfile}

dumpDB ${dbpath}/migration_FINAL.sql

text=$(displayTime $(($(date +%s)-totaltimer)))
printf "[migration-runtime] === OVERALL RUNTIME: ${text} ===\n\n" | tee -a ${logfile}

printf "[migration-info] MIGRATION ENDS.\n" | tee -a ${logfile}
