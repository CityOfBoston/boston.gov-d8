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

function removeEmptyFiles() {
    printf  "[migration-step] Remove the following zero-byte images:\n"| tee -a ${logfile}
    find ${filesdir} -type f -size 0b -print  | tee -a ${logfile}
    find ${filesdir} -type f -size 0b -delete && printf "[migration-success] Images deleted\n\n" | tee -a ${logfile}
}

acquia_env="${AH_SITE_NAME}"
if [ ! -z $2 ]; then
    acquia_env="${1}"
fi

printf "[migration-start] === UPDATE ===\n" $(date +%F\ %T ) | tee ${logfile}
printf "[migration-start] Update starts %s %s\n\n" $(date +%F\ %T ) | tee ${logfile}

if [ -d "/mnt/gfs" ]; then
    cd "/var/www/html/${acquia_env}/docroot"
    filesdir="/mnt/gfs/${acquia_env}/sites/default/files"
    logfile="${filesdir}/bos_migration.log"
    drush="drush"
    printf "[migration-info] Running update in REMOTE mode:\n"| tee ${logfile}
else
    cd  ~/sources/boston.gov-d8/docroot
    filesdir="~/sources/boston.gov-d8/docroot/sites/default/files"
    logfile="./bos_migration.log"
    drush="lando drush"
    printf "[migration-info] Running update in LOCAL DOCKER mode:\n"| tee ${logfile}
fi

printf "[migrate-info] Set migration variables (states).\n" | tee -a ${logfile}
${drush} sset "bos_migration.fileOps" "copy" | tee -a ${logfile}
${drush} sset "bos_migration.dest_file_exists" "use\ existing" | tee -a ${logfile}
${drush} sset "bos_migration.dest_file_exists_ext" "skip" | tee -a ${logfile}
${drush} sset "bos_migration.remoteSource" "https://www.boston.gov/" | tee -a ${logfile}
${drush} sset "bos_migration.active" "1" | tee -a ${logfile}
${drush} sset "system.maintenance_mode" "1"
printf "\n" | tee -a ${logfile}

printf "[migrate-info] Rebuild caches.\n" | tee -a ${logfile}
${drush} cr  | tee -a ${logfile}
printf "\n" | tee -a ${logfile}

printf "[migrate-info] Printout current migration status.\n" | tee -a ${logfile}
${drush} ms  | tee -a ${logfile}
printf "\n" | tee -a ${logfile}

totaltimer=$(date +%s)
printf "[migration-step] Migrate new files.\n" | tee -a ${logfile}
doMigrate --tag="bos:initial:0" --force
## Perform the lowest level safe-dependencies.
printf "[migration-step] Migrate new users.\n" | tee -a ${logfile}
doMigrate --tag="bos:initial:1" --force
printf "[migration-step] Migrate new taxonomy.\n" | tee -a ${logfile}
doMigrate --tag="bos:taxonomy:1" --force
doMigrate --tag="bos:taxonomy:2" --force
printf "[migration-step] Migrate new paragraphs.\n" | tee -a ${logfile}
doMigrate --tag="bos:paragraph:1" --force --update
doMigrate --tag="bos:paragraph:2" --force --update
doMigrate --tag="bos:paragraph:3" --force --update
doMigrate --tag="bos:paragraph:4" --force --update
doMigrate --group=bos_field_collection --force
doMigrate --tag="bos:paragraph:10" --force --update
printf "[migration-step] Migrate new nodes.\n" | tee -a ${logfile}
doMigrate --tag="bos:node:1" --force
doMigrate --tag="bos:node:2" --force
doMigrate --tag="bos:node:3" --force
doMigrate --tag="bos:node:4" --force
doMigrate --tag="bos:paragraph:99" --force --update
printf "[migration-step] Migrate new node revisions.\n" | tee -a ${logfile}
doMigrate --tag="bos:node_revision:1" --force --feedback=350
doMigrate --tag="bos:node_revision:2" --force --feedback=350
doMigrate --tag="bos:node_revision:3" --force --feedback=350
doMigrate --tag="bos:node_revision:4" --force --feedback=350
printf "[migration-step] Update contact.\n" | tee -a ${logfile}
doMigrate d7_taxonomy_term:contact --force --update

removeEmptyFiles

printf "[migration-step] Check status of migration.\n" | tee -a ${logfile}

## Ensure everything is updated.
doExecPHP "\Drupal\bos_migration\MigrationFixes::fixFilenames();"
doExecPHP "\Drupal\bos_migration\MigrationFixes::updateSvgPaths();"
doExecPHP "\Drupal\bos_migration\MigrationFixes::createMediaFromFiles();"
doExecPHP "\Drupal\bos_migration\MigrationFixes::fixRevisions();"
doExecPHP "\Drupal\bos_migration\MigrationFixes::fixPublished();"
doExecPHP "\Drupal\bos_migration\MigrationFixes::fixListViewField();"
doExecPHP "node_access_rebuild();"

printf "[migration-step] Show final migration status.\n" | tee -a ${logfile}
${drush} ms  | tee -a ${logfile}

printf "[migration-step] Finish off migration: reset caches and maintenance mode.\n" | tee -a ${logfile}
${drush} sset "system.maintenance_mode" "0"
${drush} sdel "bos_migration.active"
${drush} sset "bos_migration.fileOps" "copy"
${drush} cr  | tee -a ${logfile}

text=$(displayTime $(($(date +%s)-totaltimer)))
printf "[migration-runtime] === OVERALL RUNTIME: ${text} ===\n\n" | tee -a ${logfile}

printf "[migration-info] MIGRATION ENDS.\n" | tee -a ${logfile}
