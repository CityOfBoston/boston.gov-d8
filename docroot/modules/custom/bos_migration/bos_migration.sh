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

    if [ -d "/mnt/gfs" ]; then
      printf "[migration-info] Import ${backup} file into MySQL\n" | tee -a ${logfile}
      ${drush} sql:cli -y --database=default < ${backup}  | tee -a ${logfile}
      landobackup=""
    else
      landobackup=${2}
      printf "[migration-info] Import ${landobackup} file into MySQL\n" | tee -a ${logfile}
      lando ssh -c "/app/vendor/bin/drush sql:cli -y  < ${landobackup}" | tee -a ${logfile}
    fi

    printf "[migration-info] Re-zip backup.\n" | tee -a ${logfile}
    gzip -fq ${backup}

    printf "[migration-info] Sync database wih current code.\n" | tee -a ${logfile}
    # Sync current config with the database.
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

    # Set migration variables.
    printf "[migrate-info] Set migration variables (states).\n" | tee -a ${logfile}
    ${drush} sset "bos_migration.fileOps" "copy" | tee -a ${logfile}
    ${drush} sset "bos_migration.dest_file_exists" "use\ existing" | tee -a ${logfile}
    ${drush} sset "bos_migration.dest_file_exists_ext" "skip" | tee -a ${logfile}
    ${drush} sset "bos_migration.remoteSource" "https://www.boston.gov/" | tee -a ${logfile}
    ${drush} sset "bos_migration.active" "1" | tee -a ${logfile}
    ${drush} cset "pathauto.settings" "update_action" 0 -y | tee -a ${logfile}
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

    # Takes site out of maintenance mode before dumping.
    ${drush} sset "system.maintenance_mode" "0"

    dumpDB ${1} ${landobackup}

    # Puts site into maintenance mode while migration occurs.
    ${drush} sset "system.maintenance_mode" "1"

    return 0
}

function dumpDB() {
    # Dump current DB.
    timer=$(date +%s)
    backup=${1}
    if [ -d "/mnt/gfs" ]; then
      printf "[migration-step] Dump DB ${backup}\n" | tee -a ${logfile}
      ${drush} sql:dump -y --database=default > ${backup}
    else
      landobackup=${2}
      printf "[migration-step] Dump DB ${landobackup}\n" | tee -a ${logfile}
      lando ssh -c  "/app/vendor/bin/drush sql:dump -y > ${landobackup}"
    fi
    gzip -fq ${backup}
    printf "[migration-success] Database (default) dumped to ${backup}.gz.\n" | tee -a ${logfile}
    text=$(displayTime $(($(date +%s)-timer)))
    printf "[migration-runtime] ${text}\n\n" | tee -a ${logfile}
}

function removeEmptyFiles() {
    timer=$(date +%s)

    printf  "[migration-step] Remove the following zero-byte images:\n"| tee -a ${logfile}
    find ${filespath} -type f -size 0b -print  | tee -a ${logfile}
    find ${filespath} -type f -size 0b -delete && printf "[migration-success] Images deleted\n\n" | tee -a ${logfile}
    # ${drush} sql:query -y --database=default "DELETE FROM file_managed where filesize=0;" | tee -a ${logfile}

    text=$(displayTime $(($(date +%s)-timer)))
    printf "[migration-runtime] ${text}\n\n" | tee -a ${logfile}

}

# Rotate log files, keeping the last 5.
function doLogRotate() {
  SCRIPT=${1}
  # Write the logrotate config script.
  rm -f "$SCRIPT"
  echo "nocompress" > $SCRIPT
  echo "${logfile} {" >> $SCRIPT
  echo "  rotate 5" >> $SCRIPT
  echo "  missingok" >> $SCRIPT
  echo "}" >> $SCRIPT
  #  Now run the script.
  logrotate -f  -s ~/status.tmp "$SCRIPT"

  if [ $? -ne 0 ]; then
    printf "[migration-info] Log rotated sucesfully. Previous log found at boston_migration.log.1\n"
  else
    printf "[migration-warning] Previous bos_migration.log was may not have been rotated sucesfully.\n"
  fi
  #  Cleanup
  rm -f "$SCRIPT"
}

# Manage automatic paths (pathauto) (URL's) in D8.
function doPaths() {
    timer=$(date +%s)

    # Delete all automatically generated node URL aliases (preserving manually created ones).
    ${drush} pathauto:aliases-delete canonical_entities:node  -q
    printf "[migration-info] All automatatically generated node paths migrated from D7 have been deleted.\n" | tee -a ${logfile}

    # Re-generate all node URL aliases.
    ${drush} pathauto:aliases-generate create canonical_entities:node -q
    printf "[migration-info] Regenerated all node content paths using current pathauto rules.\n" | tee -a ${logfile}

    if [ -d "/mnt/gfs" ]; then
      doExecPHP "\Drupal\bos_migration\MigrationFixes::fixUnMappedUrlAliases('${acquia_env}', 'bostond8ddb289903');"
    else
      doExecPHP "\Drupal\bos_migration\MigrationFixes::fixUnMappedUrlAliases(\'drupal\', \'drupal_d7\');"
    fi

    printf "[migration-info] Created path redirects to preserve D7 pathauto paths are have not been regenerated for D8.\n" | tee -a ${logfile}

  text=$(displayTime $(($(date +%s)-timer)))
  printf "[migration-runtime] ${text}\n\n" | tee -a ${logfile}
}

printf "\n[MIGRATION] Executing 'bos_migration.sh %s'.\n\n", "${*}" | tee ${logfile}
printf "[migration-start] Starts %s %s\n\n" $(date +%F\ %T ) | tee -a ${logfile}

acquia_env="${AH_SITE_NAME}"
if [ ! -z $2 ]; then
    acquia_env="${2}"
fi

if [ -d "/mnt/gfs" ]; then
    cd "/var/www/html/${acquia_env}/docroot"
    dbpath="/mnt/gfs/${acquia_env}/backups/on-demand"
    landodbpath=$dbpath
    filespath="/mnt/gfs/${acquia_env}/sites/default/files"
    logfile="${filespath}/bos_migration.log"
    drush="drush"
    doLogRotate "${filespath}/bos_migration.cfg"
    printf "[migration-info] Running in REMOTE mode:\n" | tee -a ${logfile}
else
    export PHP_IDE_CONFIG="serverName=boston.lndo.site" && export XDEBUG_CONFIG="remote_enable=true idekey=PHPSTORM remote_host=10.241.172.216"
    cd  ~/sources/boston.gov-d8/docroot
    dbpath="/home/david/sources/boston.gov-d8/dump/migration"
    landodbpath="/app/dump/migration"
    logfile="./bos_migration.log"
    filespath="/home/david/sources/boston.gov-d8/docroot/sites/default/files"
    drush="lando drush"
    printf "[migration-info] Running in LOCAL DOCKER mode:\n"| tee -a ${logfile}
fi

running=0

totaltimer=$(date +%s)

# Migrate files first.
if [ "$1" == "reset" ]; then
    running=1
    # Remove zero byte images.  These sometimes migrate in because the file copy comes across HTTP.
    removeEmptyFiles

    restoreDB "${dbpath}/migration_clean_reset.sql" "${landodbpath}/migration_clean_reset.sql" || exit 1
    # Ensure the icon manifest is loaded (includes loading files into DB).
    printf "[migration-step] Import icon library manifest\n" | tee -a ${logfile}
    doExecPHP "\Drupal\migrate_utilities\MigUtilTools::updateAssets();"

    doMigrate --tag="bos:initial:0" --force

    dumpDB ${dbpath}/migration_clean_with_files.sql ${landodbpath}/migration_clean_with_files.sql
fi

# Perform the lowest level safe-dependencies.
if [ "$1" == "files" ] || [ $running -eq 1 ]; then
    running=1
    if [ "$1" == "files" ]; then restoreDB "${dbpath}/migration_clean_with_files.sql" "${landodbpath}/migration_clean_with_files.sql" || exit 1; fi
    doMigrate --tag="bos:initial:1" --force
    dumpDB ${dbpath}/migration_clean_with_prereq.sql ${landodbpath}/migration_clean_with_prereq.sql
fi

# Taxonomies first.
if [ "$1" == "prereq" ] || [ $running -eq 1 ]; then
    running=1
    if [ "$1" == "prereq" ]; then restoreDB "${dbpath}/migration_clean_with_prereq.sql" "${landodbpath}/migration_clean_with_prereq.sql" || exit 1; fi
    doMigrate d7_taxonomy_vocabulary -q --force
    doExecPHP "\Drupal\bos_migration\MigrationFixes::fixTaxonomyVocabulary();"
    doMigrate --tag="bos:taxonomy:1" --force
    doMigrate --tag="bos:taxonomy:2" --force
    dumpDB ${dbpath}/migration_clean_after_taxonomy.sql ${landodbpath}/migration_clean_after_taxonomy.sql
fi

if [ "$1" == "taxonomy" ] || [ $running -eq 1 ]; then
    running=1
    if [ "$1" == "taxonomy" ]; then restoreDB "${dbpath}/migration_clean_after_taxonomy.sql" "${landodbpath}/migration_clean_after_taxonomy.sql" || exit 1; fi
    doMigrate --tag="bos:paragraph:1" --force
    doMigrate --tag="bos:paragraph:2" --force
    doMigrate --tag="bos:paragraph:3" --force
    doMigrate --tag="bos:paragraph:4" --force
    dumpDB ${dbpath}/migration_clean_after_all_paragraphs.sql ${landodbpath}/migration_clean_after_all_paragraphs.sql
fi

# Do these last b/c creates new paragraphs that might steal existing paragraph entity & revision id's.
if [ "$1" == "paragraphs" ] || [ $running -eq 1 ]; then
    running=1
    if [ "$1" == "paragraphs" ]; then restoreDB "${dbpath}/migration_clean_after_all_paragraphs.sql" "${landodbpath}/migration_clean_after_all_paragraphs.sql" || exit 1; fi
    doMigrate --group=bos_field_collection --force
    dumpDB ${dbpath}/migration_clean_after_field_collection.sql ${landodbpath}/migration_clean_after_field_collection.sql
fi

# Redo paragraphs which required field_collections to be migrated to para's first.
if [ "$1" == "field_collection" ] || [ $running -eq 1 ]; then
    running=1
    if [ "$1" == "field_collection" ]; then restoreDB "${dbpath}/migration_clean_after_field_collection.sql" "${landodbpath}/migration_clean_after_field_collection.sql" || exit 1; fi
    doMigrate --tag="bos:paragraph:10" --force --update
    # Fix the listview component to match new view names and displays.
    dumpDB ${dbpath}/migration_clean_after_para_update_1.sql ${landodbpath}/migration_clean_after_para_update_1.sql
fi

# Migrate nodes in sequence.
if [ "$1" == "update1" ] || [ $running -eq 1 ]; then
    running=1
    if [ "$1" == "update1" ]; then restoreDB "${dbpath}/migration_clean_after_para_update_1.sql" "${landodbpath}/migration_clean_after_para_update_1.sql" || exit 1; fi
    doMigrate --tag="bos:node:1" --force
    doMigrate --tag="bos:node:2" --force
    doMigrate --tag="bos:node:3" --force
    doMigrate --tag="bos:node:4" --force
    dumpDB ${dbpath}/migration_clean_after_nodes.sql ${landodbpath}/migration_clean_after_nodes.sql
fi

# Redo para's which have nodes in fields.
if [ "$1" == "nodes" ] || [ $running -eq 1 ]; then
    running=1
    if [ "$1" == "nodes" ]; then restoreDB "${dbpath}/migration_clean_after_nodes.sql" "${landodbpath}/migration_clean_after_nodes.sql" || exit 1; fi
    doMigrate --tag="bos:paragraph:99" --force --update
    dumpDB ${dbpath}/migration_clean_after_para_update_2.sql ${landodbpath}/migration_clean_after_para_update_2.sql
fi

# Now do the node revisions (nodes and all paras must be done first)
if [ "$1" == "update2" ] || [ $running -eq 1 ]; then
    running=1
    if [ "$1" == "update2" ]; then restoreDB "${dbpath}/migration_clean_after_para_update_2.sql" "${landodbpath}/migration_clean_after_para_update_2.sql" || exit 1; fi
    doMigrate --tag="bos:node_revision:1" --force --feedback=200
    doMigrate --tag="bos:node_revision:2" --force --feedback=200
    doMigrate --tag="bos:node_revision:3" --force --feedback=200
    doMigrate --tag="bos:node_revision:4" --force --feedback=200
    dumpDB ${dbpath}/migration_clean_after_node_revision.sql ${landodbpath}/migration_clean_after_node_revision.sql
fi

# This is to resume when the node_revsisions fail mid-way.
if [ "$1" == "revision_resume" ]; then
  printf "\n[migration-info] Continues from previous migration %s %s\n" $(date +%F\ %T ) | tee -a ${logfile}
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

  doMigrate --tag="bos:node_revision:1" --force --feedback=200
  doMigrate --tag="bos:node_revision:2" --force --feedback=200
  doMigrate --tag="bos:node_revision:3" --force --feedback=200
  doMigrate --tag="bos:node_revision:4" --force --feedback=200
  dumpDB ${dbpath}/migration_clean_after_node_revision.sql ${landodbpath}/migration_clean_after_node_revision.sql
fi

# Finish off.
if [ "$1" == "node_revision" ] || [ $running -eq 1 ]; then
    running=1
    if [ "$1" == "node_revision" ]; then restoreDB "${dbpath}/migration_clean_after_node_revision.sql" "${landodbpath}/migration_clean_after_node_revision.sql" || exit 1; fi
    doMigrate d7_menu_links,d7_menu --force
    doMigrate d7_taxonomy_term:contact --force --update
    dumpDB ${dbpath}/migration_clean_after_menus.sql ${landodbpath}/migration_clean_after_menus.sql
fi

if [ "$1" == "final" ]; then
    running=1
    restoreDB "${dbpath}/migration_clean_after_menus.sql" "${landodbpath}/migration_clean_after_menus.sql"
fi

if [ $running -eq 0 ]; then
    printf "[migration-error] Bad script parameter\nOptions are:\n  reset, files, rereq, taxonomy, paragraphs, field_collection, update1, nodes, update2, node_revision, menus, final" | tee -a ${logfile}
    exit 1
fi

# Check all migrations completed.
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

# Map D7 view displays to D8 displays.
doExecPHP "\Drupal\bos_migration\MigrationFixes::fixListViewField();"

# Update Media entity dates to match file entity dates.
doExecPHP "\Drupal\bos_migration\MigrationFixes::syncMediaDates();"

# Reset status_items.
doExecPHP "\Drupal\migrate_utilities\MigUtilTools::deleteContent(['node' => 'status_item', 'paragraph' => 'message_for_the_day']);"
doExecPHP "\Drupal\migrate_utilities\MigUtilTools::loadSetup('node_status_item');"

printf "[migration-step] Show final migration status.\n" | tee -a ${logfile}
${drush} ms  | tee -a ${logfile}

# Reset dev-only modules.
printf "[migration-step] Reset modules.\n" | tee -a ${logfile}
#${drush} pmu migrate,migrate_upgrade,migrate_drupal,migrate_drupal_ui,field_group_migrate,migrate_plus,migrate_tools,bos_migration,config_devel,migrate_utilities -y  | tee -a ${logfile}
${drush} en acquia_purge,acquia_connector

# Takes site out of maintenance mode when migration is done.
printf "[migration-step] Rebuild auto-path urls.\n" | tee -a ${logfile}
doPaths

# Takes site out of maintenance mode when migration is done.
printf "[migration-step] Finish off migration: reset caches and maintenance mode.\n" | tee -a ${logfile}
${drush} sset "system.maintenance_mode" "0"
${drush} sdel "bos_migration.active"
${drush} sset "bos_migration.fileOps" "copy"
${drush} cset "pathauto.settings" "update_action" 2 -y | tee -a ${logfile}
${drush} cset "system.logging" "error_level" "hide" -y | tee -a ${logfile}
${drush} cr  | tee -a ${logfile}

dumpDB ${dbpath}/migration_FINAL.sql ${landodbpath}/migration_FINAL.sql

text=$(displayTime $(($(date +%s)-totaltimer)))
printf "[migration-runtime] === OVERALL RUNTIME: ${text} ===\n\n" | tee -a ${logfile}

printf "[MIGRATION] Script 'bos_migration.sh' ends.\n" | tee -a ${logfile}
