#!/bin/bash
#
# This script is intended to be run in a terminal on a host machine when the hook script fails on Acquia because of
# a database copy timing out or something similar (database is present but was not updated after copying).
#
# Run this command with the pwd the scripts folder: eg:
#     cd /home/david/sources/boston.gov-d8/scripts/deploy  && ./hook_manual.sh [args]
#
# Args should be copied from the Acquia log, and are the exact same args that were used to launch the hook script which
# failed on whichever Acquia environment. e.g:
#
#      bostond8 test master-deploy master-deploy bostond8@svn-29892.prod.hosting.acquia.com:bostond8.git git
#

site="$1"
target_env="$2"
source_branch="$3"
deployed_tag="$4"
repo_url="$5"
repo_type="$6"
ALIAS="@${site}.${target_env}"

# Add utility functions
. "cob_utilities.sh"

# Uses Lando, so a functioning container must exist.
drush_cmd="lando drush ${ALIAS}"

# Required var for cob_utilities functions to work
REPO_ROOT="/var/www/html/${site}.${target_env}"

printf "\n===============================\n"
printf "%s: Manually running a code update on %s environment of %s environment.\n" "${site}.${target_env}" "${source_branch}" "${target_env}" "${site}"
echo "---------------------------------"
read -p "Are you sure you wish to do this? " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]
then
    exit 1
fi

printf "\n\n [notice] On Acquia, 'test' and 'stage' are synonyms for the STAGE environment.\n\n"
printf " [notice] Synchronise the %s database with updated code.\n"  "${target_env}"

    ########################################################
    # ACTION 2: PLACE SITE IN MAINTENANCE MODE
    ########################################################

    # As a courtesy, put site into maintenance mode.
    if [[ "${target_env}" != "prod" ]]; then
      printf " [action] Flag site is in maintenance mode.\n"
      ${drush_cmd} -y state:set "system.maintenance_mode" "1"
    fi

    ########################################################
    # ACTION 3: COPY DATABASE DOWN FROM PROD ENVIRONMENT
    ########################################################

    ########################################################
    # ACTION 4: APPLY SETTINGS AND CONFIGURATIONS (config_split)
    ########################################################

    # Sync the copied database with the recently updated/deployed code.
    printf " [action] Import configuration changes (using config_split).\n"
    ${drush_cmd} cache:rebuild &&
    ${drush_cmd} config:import -y &&
    ${drush_cmd} config:status --state='Different,Only in sync dir' &&
      printf " [success] Config Imported.\n" || printf "\n [warning] Problem with configuration sync.\n"

    # Apply any pending database updates.
    printf " [action] Apply pending database updates etc.\n"
    ${drush_cmd} cache:rebuild
    ${drush_cmd} -y updatedb &&
      printf " [success] Updates Completed.\n" || printf " [warning] Database updates from contributed modules were not applied.\n"

    # We want the Acquia purger to work on this environment.
    printf " [action] Set the Varnish purger (using acquia_purge).\n"
      ${drush_cmd} p:purger-add --if-not-exists acquia_purge &&
      printf " [info] List purgers.\n" &&
      ${drush_cmd} p:purger-ls &&
      printf " [info] Purger diagnostics.\n" &&
      ${drush_cmd} p:diagnostics --fields=title,recommendation,value,severity  &&
      printf " [success] Purger set.\n" || printf "\n [warning] Problem setting the acquia purger.\n"

    ########################################################
    # ACTION 5: HOUSEKEEPING
    ########################################################

    ########################################################
    # ACTION 6: UNDO MAINTENANCE MODE SETTING
    ########################################################

    # Take site out of maintenance mode.
    printf " [action] Take site out of maintenance mode.\n"
    ${drush_cmd} -y state:set "system.maintenance_mode" "0"

