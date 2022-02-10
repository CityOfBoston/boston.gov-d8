#!/bin/bash

slackErrors=""

# Create a drush command string using an alias and supressing input and output.
# the drush_cmd variable is GLOBAL and may well have been set in the calling script.
function setDrushCmd() {
  # Set the alias to @self if an argument was not supplied.
  [[ -n "${1}" ]] && ALIAS="@${1}" || ALIAS="@self"
  # Set the dusch_cmd to be "drush" if its not already set.
  [[ -z ${drush_cmd} ]] && drush_cmd="drush "
  # Check if an alias is set (something prefixed '@') and set if it isn't
  [[ -z "$(echo ${drush_cmd} | grep -o "@")" ]] && drush_cmd="${drush_cmd}${ALIAS} "
  # If the -y flag is not set, then add it to the command string (supplies Y to and drush CLI prompts).
  [[ -z "$(echo ${drush_cmd} | grep -o "\-y")" ]] && drush_cmd="${drush_cmd}-y "
  # If the -q or --quiet flag is not set, then add it to the command string (supresses/minimises cli output).
  [[ "${target_env}" == "local" ]] && [[ -z "$(echo ${drush_cmd} | grep -o "\-q")" ]] && drush_cmd="${drush_cmd}--quiet"
  # If the nointeraction flag is not set, then add it to the command string (further supresses/minimises cli input).
  [[ "${target_env}" == "local" ]] && [[ -z "$(echo ${drush_cmd} | grep -o "interaction")" ]] && drush_cmd="${drush_cmd}--no-interaction "
}

function sync_files() {
  SOURCE="${1}"
  DESTINATION="${2}"
  MODE="quick"

  if [ -z "${3}" ]; then
    MODE="${3}"
  fi

  if [ "${MODE}" == "full" ]; then
    printf " [action] Copy all files from %s to %s\n" "${SOURCE}" "${DESTINATION}"
    printf "          (removes files on source that don't exist on destination).\n"
    drush core:rsync ${SOURCE}:%files ${DESTINATION}:%files -- --delete --mode=arz -P
    if [[ $? -ne 0 ]]; then
        slackErrors="${slackErrors}\n- :small_orange_diamond: Problem copying images from ${SOURCE}."
        printf " [warning] Not all files copied\n"
    else
        printf " [success] files copied\n"
    fi
  else
    printf " [action] Copy all images (max size = 10MB) from %s to %s \n" "${SOURCE}" "${DESTINATION}"
    printf "          (removes files on source that don't exist on destination).\n"
    drush core:rsync ${SOURCE}:%files ${DESTINATION}:%files -- --delete --exclude=*.pdf --max-size=10m --mode=arz -P
    if [[ $? -ne 0 ]]; then
        slackErrors="${slackErrors}\n- :small_orange_diamond: Problem copying images from ${SOURCE}."
        printf " [warning] Not all files copied\n"
    else
        printf " [success] files copied\n"
    fi
  fi
}

function setVars() {
    # Used as temporary directory for update_all_icons().
    export MAGICK_TEMPORARY_PATH="/home/bostond8/${1}/tmp"
}

# Uses rsync to copy image files from one Acquia server to the current acquia server.
function copyFiles() {
    # Define the source and destination building variables.
    SITE="${site}"
    FROMENV="prod"
    TOENV="${target_env}"
    # $1 will usually be "bostond8".
    if [[ -n $1 ]]; then SITE="${1}"; fi
    # $2 will be one of dev | test | prod | ci | uat.
    if [[ -n $2 ]]; then FROMENV="${2}"; fi
    # $3 will be one of dev | test | prod | ci | uat.
    if [[ -n $3 ]]; then TOENV="${3}"; fi

    # Define the scripts folder.
    LOCALSCRIPT="/var/www/html/${SITE}.${TOENV}/scripts/deploy"
    if [[ "${TOENV}" == "prod" ]]; then LOCALSCRIPT="/var/www/html/${SITE}/scripts/deploy"; fi

    # We need this file, without it we can't rsync.
    printf " [notice]  Working as: $(whoami).\n"
    if [[ ! -r "${LOCALSCRIPT}/acquia.enc" ]]; then
        printf " [warning] File does not exist at ${LOCALSCRIPT}/acquia.enc.\n"
        printf " [notice]  Non-fatal: Incremental copy of image files from ${FROMENV} to ${TOENV} not possible.\n"
    elif [[ -z $ac_sync_key ]] || [[ -z $ac_sync_vector ]]; then
        printf " [warning] Environment variable 'ac_sync_key' and/or 'ac_sync_vector' not set on $TOENV environment.\n"
        printf " [notice]  Non-fatal: Incremental copy of image files from ${FROMENV} to ${TOENV} not possible.\n"
    else
        SUBDOM="${SITE}${FROMENV}"
        if [[ "${FROMENV}" == "test" ]]; then
            SUBDOM="${SITE}stg"
        elif [[ "${FROMENV}" == "prod" ]]; then
            SUBDOM="${SITE}"
        fi
        # Define the "connection strings"
        FROMHOST="${SITE}.${FROMENV}@${SUBDOM}.ssh.prod.acquia-sites.com"
        FROMPATH="/mnt/gfs/${SITE}.${FROMENV}/sites/default/files/img"
        TOPATH="/mnt/gfs/${SITE}.${TOENV}/sites/default/files"

        # Unencrypt the ssh key temporarily and execute the rsync
        openssl aes-256-cbc -K ${ac_sync_key} -iv ${ac_sync_vector} -in ${LOCALSCRIPT}/acquia.enc -out ${TOPATH}/acquia_deploy -d > /dev/null &&
            chmod 600 $TOPATH/acquia_deploy > /dev/null &&
            rsync -arz -P --max-size=10m --exclude='*.pdf' -e "ssh -i ${TOPATH}/acquia_deploy" "${FROMHOST}:${FROMPATH}" "${TOPATH}" > /dev/null
        if [[ $? -ne 0 ]]; then
            slackErrors="${slackErrors}\n- :small_orange_diamond: Problem copying images from ${SOURCE}."
            printf " [warning] Local images (for all non-prod environments) NOT updated from $FROMENV\n"
        else
            printf " [success] Local images (for all non-prod environments) updated from $FROMENV\n"
        fi

        # Regardless of outcome, remove the key
        rm -f "$TOPATH/acquia_deploy"

    fi
}

# Check that the non-prod environments have their files folder mapped from the dev environment.
function checkFileFolderMap() {
    # On all environments except dev and prod (i.e. uat, ci and stage/test) we set the public files path to be a folder
    # in that environment ("/sites/default/files/linked") - not the drupal default ("/sites/default/files") folder.
    #   @see /sites/default/settings/settings.acquia.php (from private repo).
    # Here we make sure there is a soft link of the dev environment default public files folder.
    if [[ "$target_env" != "prod" ]] && [[ "$target_env" != "dev" ]]; then
        if [[ ! -e /mnt/gfs/${site}.${target_env}/sites/default/files/linked ]]; then
            printf "[info] Public Files Folder: Linking Dev environment folder into this environment (${target_env}).\n"
            cd /mnt/gfs/${site}.${target_env}/sites/default/files &&
              ln -s /mnt/gfs/${site}.dev/sites/default/files/ linked &&
              printf "[success] Link created.\n"
        else
            printf "[info] Public Files Folder: Dev environment folder already linked into this environment (${target_env}).\n"
        fi
    fi
}

#function setEnvColor() {
#     setDrushCmd "${ALIAS}"
#
#    if [ "${target_env}" == "dev" ]; then
#        fg_color="#ffffff"
#        bg_color="#3e0202"
#    elif [ "${target_env}" == "test" ]; then
#        fg_color="#ffffff"
#        bg_color="#b15306"
#    elif [ "${target_env}" == "prod" ]; then
#        fg_color="#ffffff"
#        bg_color="#303655"
#    elif [ "${target_env}" == "local" ]; then
#        fg_color="#ffffff"
#        bg_color="#023e0a"
#    fi
#
#    ${drush_cmd} cset ${DRUSH_OPT} environment_indicator.indicator name  ${target_env} > /dev/null
#    ${drush_cmd} cset ${DRUSH_OPT} environment_indicator.indicator fg_color ${fg_color} > /dev/null
#    ${drush_cmd} cset ${DRUSH_OPT} environment_indicator.indicator bg_color ${bg_color} > /dev/null
#
#}

# Set the website to use patterns library from appropriate location.
function setPatternsSource() {
  # bos:css-source key: 2=local containers, 3=production AWS, 4=stage AWS
  setDrushCmd "${1}"
  if [[ -n "${2}" ]]; then
    target=${2}
  elif [[ -n "${target_env}" ]]; then
    target=${target_env}
  else
    target="prod"
  fi
  if [ "${target}" == "dev" ]; then
      # Use the staging (AWS) environment on the dev server/s
      ${drush_cmd} os:css-source 4
      patterns="patterns-stg.boston.gov"
  elif [ "${target}" == "test" ]; then
      # Use prod (AWS) environment on the staging server/s
      ${drush_cmd} bos:css-source 3
      patterns="patterns.boston.gov (prod)"
  elif [ "${target}" == "prod" ]; then
      # Use prod (AWS) environment on prod servers
      ${drush_cmd} bos:css-source 3
      patterns="patterns.boston.gov (prod)"
  elif [ "${target}" == "local" ]; then
      # Use local container version in local builds
      ${drush_cmd} bos:css-source 2
      patterns="patterns.lndo.site (local container)"
  fi
  printf " [success] website uses ${patterns} as the patterns library.\n"

}

# Post a message to slack.
function slackPost() {
    if [[ -z "${slackErrors}" ]]; then slackErrors=""; fi

    if [[ -z "${slackposter_webhook}" ]]; then
        printf "The 'slackposter_webhook' environment variable is not set in Acquia ${target_env} environment. No post to slack.\n"
    elif [[ -n ${site} ]] && [[ -n ${target_env} ]] && [[ -n ${source_branch} ]]; then
        title=":acquia_cloud: DRUPAL ${site} deploy to ${target_env}."
        body="The latest release of ${source_branch} branch is now available for testing on the ${target_env} environment."
        if [[ "${target_env}" == "dev" ]]; then
            body="${body} - https://d8-dev.boston.gov"
        elif [[ "${target_env}" == "test" ]]; then
            body="${body} - https://d8-stg.boston.gov"
        elif [[ "${target_env}" == "prod" ]]; then
            body="The code from the Staging environment is now LIVE on ${target_env}. - https://www.boston.gov"
        else
            body="The latest release of the ${source_branch} branch is now available on the ${target_env} environment."
        fi
        status="good"
        if [[ "${slackErrors}" != "" ]]; then
            status="danger"
            body="${body} ${slackErrors}\n:information_source: Please check the build log in the Acquia Cloud Console."
        fi
        if [[ -n ${1} ]]; then
            title="${title} --CHECK"
            status="danger"
            body="The deployment of ${source_branch} to ${target_env} had issues.${slackErrors}\n:information_source: Please check the build log in the Acquia Cloud Console."
        fi
        ${drush_cmd} cset --quiet -y "slackposter.settings" "integration" "${slackposter_webhook}" &&
            ${drush_cmd} cset --quiet -y "slackposter.settings" "channels.default" "drupal"
        ${drush_cmd} slackposter:post "${title}" "${body}" "#drupal" "Acquia Cloud" "${status}"
    fi
}

# Sets the purger to use acquia_purger (if it is not already set) on the current environment.
function setPurger() {
  printf "[FUNCTION] $(basename $BASH_SOURCE).setPurger()" "Called from $(basename $0)\n"
  setDrushCmd "${1}"
  ${drush_cmd} p:purger-add --if-not-exists acquia_purge &&
    printf " [info] List purgers.\n" &&
    ${drush_cmd}  p:purger-ls &&
    printf " [info] Purger diagnostics.\n" &&
    ${drush_cmd} p:diagnostics --fields=title,recommendation,value,severity

  if [[ $? -ne 0 ]]; then
    slackErrors="${slackErrors}\n- :red_circle: Problem setting up the Acquia Purge functionality."
    exit 1
  fi
}

# Set the password for the admin user.  If no password is provided, then use a randomly generated string.
function setPassword() {
  # Create a new random password.
  setDrushCmd "${1}"
  if [[ -n "${2}" ]]; then
    NEWPASSWORD="${2}"
  else
    # Random 10 char string.
    NEWPASSWORD="$(openssl rand -hex 10)"
  fi
  ${drush_cmd} user:password -y admin "${NEWPASSORD}"
}

# Imports the configurations - Remember the config_split module is enabled, so ensure the correct
# config_split profile is active.
# The active config_split profile is usually set by overrides in the settings.php file (or an include in that file).
function importConfigs() {
  printf "[FUNCTION] $(basename $BASH_SOURCE).importConfigs()" "Called from $(basename $0)\n"
  ALIAS="${1}"
  setDrushCmd "${ALIAS}"
  ${drush_cmd} pm:enable config &&
    ${drush_cmd} config:import sync

  if [[ $? -ne 0 ]]; then
    slackErrors="${slackErrors}\n- :small_orange_diamond: Problem importing configs."
    exit 1
  fi

}

# Executes sql commands to reduce DB and table sizes..
function cleanup_tables() {

    SITE="${1}"
    DBTARGET="${2}"
    ALIAS="@$SITE.$DBTARGET"

    if [[ -d /app/docroot ]]; then
        cd /app/docroot
    elif [[ -d /var/www/html/${site}.${target_env} ]]; then
        cd /var/www/html/${site}.${target_env}/
    fi

    setDrushCmd "${ALIAS}"

    # Remove Neighborhoodlookup data (+/-1.4GB each table), and compress.
    ${drush_cmd} ${ALIAS} sql-query "TRUNCATE TABLE node__field_sam_neighborhood_data; OPTIMIZE TABLE node__field_sam_neighborhood_data;"
    ${drush_cmd} ${ALIAS} sql-query "TRUNCATE TABLE node_revision__field_sam_neighborhood_data; OPTIMIZE TABLE node_revision__field_sam_neighborhood_data;"

    # cleanout the queues
    ${drush_cmd} ${ALIAS} queue:delete mnl_cleanup
    ${drush_cmd} ${ALIAS} queue:delete mnl_import
    ${drush_cmd} ${ALIAS} queue:delete mnl_update

    # Prune salesforce
    ${drush_cmd} ${ALIAS}  salesforce_mapping:prune-revision

    # todo: consider drush sql:sanitize to cleanup the DB some more ...
}

#acquia_db_backup "bostond8" "dev2" 300
#acquia_db_copy "bostond8" "dev2" "dev" 900
#cleanup_backups "bostond8" "dev2" 30 300

function acquia_db_copy() {
    # Use this command (rather than sql-sync) because this will cause the Acquia DB copy hooks to run, and log in the UI.
    # The cloud API command runs an async task, so we have to wait for the copy to complete.
    SITE="${1}"
    DBTARGET="${2}"
    DBSOURCE="${3}"
    TIMEOUT=1200
    if [[ "${4}" != "" ]]; then TIMEOUT="${4}"; fi
    APP_UUID="5ad427f5-60d6-48fd-983e-670ddc7767c4"
    ENDPOINT="https://cloud.acquia.com/api"

    # Get authenticated.
    # NOTE: COB_DEPLOY_API_KEY/SECRET variables are ENVARs set in Acquia cloud UI.
    AUTH=$( curl -X POST -j -c acquia.txt --silent https://accounts.acquia.com/api/auth/oauth/token --data-urlencode "client_id=${COB_DEPLOY_API_KEY}" --data-urlencode "client_secret=${COB_DEPLOY_API_SECRET}" --data-urlencode "grant_type=client_credentials" )
    ERR=$( php -r "\$a=(json_decode('$AUTH')); if (!empty(\$a->error)) printf(\$a->error);" )
    if [[ "${ERR}" != "" ]]; then
        MSG=$(php -r "\$a=(json_decode('$RESULT')); printf(\$a->error_description);")
        slackErrors="${slackErrors}\n- :large_orange_diamond: Problem getting authenticated by Acquia Cloud - $MSG"
        echo "fail"
        exit 0
    fi

    TOKEN=$(php -r "\$a=(json_decode('$AUTH')); if (!empty(\$a->access_token)) printf(\$a->access_token);")
    if [[ $TOKEN == "" ]]; then
        slackErrors="${slackErrors}\n- :large_orange_diamond: Problem getting authenticated by Acquia Cloud"
        echo "fail"
        exit 0
    fi

    # This timer will time-out the script.
    # It is also used to manage the token which itself is only valid for 300 secs.
    # Token life check is done in the while loop at the bottom of this function.
    TOKENTIMEOUT=$(php -r "\$a=(json_decode('$AUTH')); (!empty(\$a->expires_in)) ? printf(\$a->expires_in) : 300;")
    timertoken=$(date +%s)
    timertimeout=${timertoken}

    # Find the correct TARGET environment
    QUERYSTRING="filter=name%3D$DBTARGET"
    RESULT=$(curl --location --silent -b acquia.txt "https://cloud.acquia.com/api/applications/${APP_UUID}/environments?${QUERYSTRING}" --header "Authorization: Bearer ${TOKEN}")
    ERR=$(php -r "\$a=(json_decode('$RESULT')); if (!empty(\$a->error)) printf(\$a->error);")
    if [[ "${ERR}" != "" ]]; then
        MSG=$(php -r "\$a=(json_decode('$RESULT')); printf(\$a->message);")
        slackErrors="${slackErrors}\n- :large_orange_diamond: Problem finding $DBTARGET environment - $MSG"
        echo "fail"
        exit 0
    fi
    TARGET_ENV_ID=$(php -r "\$a=(json_decode('$RESULT')); printf(\$a->_embedded->items[0]->id);")

    # Find the correct SOURCE environment
    QUERYSTRING="filter=name%3D$DBSOURCE"
    RESULT=$(curl --location --silent -b acquia.txt "${ENDPOINT}/applications/${APP_UUID}/environments?${QUERYSTRING}" --header "Authorization: Bearer ${TOKEN}")
    ERR=$(php -r "\$a=(json_decode('$RESULT')); if (!empty(\$a->error)) printf(\$a->error);")
    if [[ "${ERR}" != "" ]]; then
        MSG=$(php -r "\$a=(json_decode('$RESULT')); printf(\$a->message);")
        slackErrors="${slackErrors}\n- :large_orange_diamond: Problem finding $DBSOURCE environment - $MSG"
        echo "fail"
        exit 0
    fi
    SOURCE_ENV_ID=$(php -r "\$a=(json_decode('$RESULT')); printf(\$a->_embedded->items[0]->id);")

    # Copy the database
    RESULT=$(curl -X POST --silent -b acquia.txt "${ENDPOINT}/environments/${TARGET_ENV_ID}/databases" --data-urlencode "name=${SITE}" --data-urlencode "source=${SOURCE_ENV_ID}" --header "Authorization: Bearer ${TOKEN}")
    ERR=$(php -r "\$a=(json_decode('$RESULT')); if (!empty(\$a->error)) printf(\$a->error);")
    if [[ "${ERR}" != "" ]]; then
        MSG=$(php -r "\$a=(json_decode('$RESULT')); printf(\$a->message);")
        slackErrors="${slackErrors}\n- :large_orange_diamond: Problem copying DB from $DBSOURCE to $DBTARGET - $MSG"
        echo "fail"
        exit 0
    fi
    NOTIFICATION=$(php -r "\$a=(json_decode('$RESULT')); if (!empty(\$a->_links->notification->href)) printf(\$a->_links->notification->href);")

    # Poll for completed notification.
    if [[ "${NOTIFICATION}" == "" ]]; then
        slackErrors="${slackErrors}\n- :large_orange_diamond: Cannot determine the status of the DB Backup task (no notification task)."
        echo "fail"
        exit 0
    fi
    RES=""
    while [[ "${NOTIFICATION}" != "" ]]; do
        sleep 10
        RESULT=$(curl --location --silent -b acquia.txt "${NOTIFICATION}" --header "Authorization: Bearer ${TOKEN}")
        ERR=$(php -r "\$a=(json_decode('$RESULT')); if (!empty(\$a->error)) printf(\$a->error);")
        STATUS=$(php -r "\$a=(json_decode('$RESULT')); if (!empty(\$a->status)) printf(\$a->status);")
        if [[ "${ERR}" != "" ]] || [[ "${STATUS}" == "" ]] || [[ "${STATUS}" == "failed" ]]; then
            MSG=$(php -r "\$a=(json_decode('$RESULT')); if (!empty(\$a->message)) printf(\$a->message);")
            slackErrors="${slackErrors}\n- :large_orange_diamond: Unknown status for $DBTARGET database backup task - $MSG"
            echo "fail"
            exit 0
        fi

        # Find and set end conditions.
        if [[ "${STATUS}" == "completed" ]]; then RES="success"; fi
        if [[ $(($(date +%s)-timertimeout)) -ge $TIMEOUT ]]; then RES="timeout"; fi
        if [[ $(($(date +%s)-timertoken)) -ge $((TOKENTIMEOUT-20)) ]]; then
            # Refresh the token after 845 secs (it lives for 300 secs)
            AUTH=$(curl -X POST -j -c acquia.txt --silent https://accounts.acquia.com/api/auth/oauth/token --data-urlencode "client_id=${COB_DEPLOY_API_KEY}" --data-urlencode "client_secret=${COB_DEPLOY_API_SECRET}" --data-urlencode "grant_type=client_credentials")
            ERR=$(php -r "\$a=(json_decode('$AUTH')); if (!empty(\$a->error)) printf(\$a->error);")
            if [[ "${ERR}" != "" ]]; then
                MSG=$(php -r "\$a=(json_decode('$RESULT')); printf(\$a->error_description);")
                slackErrors="${slackErrors}\n- :large_orange_diamond: Auth key expired and could not be renewed - $MSG"
                RES="fail"
            fi
            TOKEN=$(php -r "\$a=(json_decode('$AUTH')); if (!empty(\$a->access_token)) printf(\$a->access_token);")
            if [[ $TOKEN == "" ]]; then
                slackErrors="${slackErrors}\n- :large_orange_diamond: Auth key expired and could not be renewed."
                RES="fail"
            fi
            timertoken=$(date +%s)
        fi
        # Setting NOTIFICATION to empty will end the loop.
        if [[ $RES != "" ]]; then NOTIFICATION=""; fi

    done

    if [[ "${RES}" == "timeout" ]]; then
        slackErrors="${slackErrors}\n- :small_orange_diamond: Timeout with $DBTARGET DB Backup."
        RES="fail"
    fi

    echo ${RES}
}

function acquia_db_backup() {
    # Use this command (rather than sql dump) b/c API will log the backup in the UI list and management -including
    # restore, can be done from the acquia cloud UI.
    # The cloud API command runs an async task, so we have to wait for the copy to complete.
    SITE="${1}"
    DBTARGET="${2}"
    TIMEOUT=1200
    if [[ "${3}" != "" ]]; then TIMEOUT="${3}"; fi
    APP_UUID="5ad427f5-60d6-48fd-983e-670ddc7767c4"
    ENDPOINT="https://cloud.acquia.com/api"

    # Get authenticated.
    # NOTE: COB_DEPLOY_API_KEY/SECRET variables are ENVARs set in Acquia cloud UI.
    AUTH=$(curl -X POST -j -c acquia.txt --silent https://accounts.acquia.com/api/auth/oauth/token --data-urlencode "client_id=${COB_DEPLOY_API_KEY}" --data-urlencode "client_secret=${COB_DEPLOY_API_SECRET}" --data-urlencode "grant_type=client_credentials")
    ERR=$(php -r "\$a=(json_decode('$AUTH')); if (!empty(\$a->error)) printf(\$a->error);")
    if [[ "${ERR}" != "" ]]; then
        MSG=$(php -r "\$a=(json_decode('$RESULT')); printf(\$a->error_description);")
        slackErrors="${slackErrors}\n- :large_orange_diamond: Problem getting authenticated by Acquia Cloud - $MSG"
        echo "fail"
        exit 0
    fi

    TOKEN=$(php -r "\$a=(json_decode('$AUTH')); if (!empty(\$a->access_token)) printf(\$a->access_token);")
    if [[ $TOKEN == "" ]]; then
        slackErrors="${slackErrors}\n- :large_orange_diamond: Problem getting authenticated by Acquia Cloud"
        echo "fail"
        exit 0
    fi

    # This timer will time-out the script.
    # It is also used to manage the token which itself is only valid for 300 secs.
    # Token life check is done in the while loop at the bottom of this function.
    TOKENTIMEOUT=$(php -r "\$a=(json_decode('$AUTH')); (!empty(\$a->expires_in)) ? printf(\$a->expires_in) : 300;")
    timertoken=$(date +%s)
    timertimeout=${timertoken}

    # Find correct environment.
    QUERYSTRING="filter=name%3D$DBTARGET"
    RESULT=$(curl --location --silent -b acquia.txt "${ENDPOINT}/applications/${APP_UUID}/environments?${QUERYSTRING}" --header "Authorization: Bearer ${TOKEN}")
    ERR=$(php -r "\$a=(json_decode('$RESULT')); if (!empty(\$a->error)) printf(\$a->error);")
    if [[ "${ERR}" != "" ]]; then
        MSG=$(php -r "\$a=(json_decode('$RESULT')); printf(\$a->message);")
        slackErrors="${slackErrors}\n- :large_orange_diamond: Problem finding $DBTARGET environment - $MSG"
        echo "fail"
        exit 0
    fi
    TARGET_ENV_ID=$(php -r "\$a=(json_decode('$RESULT')); printf(\$a->_embedded->items[0]->id);")

    # Backup DB.
    RESULT=$(curl -X POST --location --silent -b acquia.txt "${ENDPOINT}/environments/${TARGET_ENV_ID}/databases/${SITE}/backups" --header "Authorization: Bearer ${TOKEN}")
    ERR=$(php -r "\$a=(json_decode('$RESULT')); if (!empty(\$a->error)) printf(\$a->error);")
    if [[ "${ERR}" != "" ]]; then
        MSG=$(php -r "\$a=(json_decode('$RESULT')); printf(\$a->message);")
        slackErrors="${slackErrors}\n- :large_orange_diamond: Problem copying DB from $DBSOURCE to $DBTARGET - $MSG"
        echo "fail"
        exit 0
    fi
    NOTIFICATION=$(php -r "\$a=(json_decode('$RESULT')); if (!empty(\$a->_links->notification->href)) printf(\$a->_links->notification->href);")

    # Poll for completed notification.
    if [[ "${NOTIFICATION}" == "" ]]; then
        slackErrors="${slackErrors}\n- :large_orange_diamond: Cannot determine the status of the DB Backup task (no notification task)."
        echo "fail"
        exit 0
    fi
    RES=""
    while [[ "${NOTIFICATION}" != "" ]]; do
        sleep 10
        RESULT=$(curl --location --silent -b acquia.txt "${NOTIFICATION}" --header "Authorization: Bearer ${TOKEN}")
        ERR=$(php -r "\$a=(json_decode('$RESULT')); if (!empty(\$a->error)) printf(\$a->error);")
        STATUS=$(php -r "\$a=(json_decode('$RESULT')); if (!empty(\$a->status)) printf(\$a->status);")
        if [[ "${ERR}" != "" ]] || [[ "${STATUS}" == "" ]] || [[ "${STATUS}" == "failed" ]]; then
            MSG=$(php -r "\$a=(json_decode('$RESULT')); if (!empty(\$a->message)) printf(\$a->message);")
            slackErrors="${slackErrors}\n- :large_orange_diamond: Unknown status for $DBTARGET database backup task - $MSG"
            echo "fail"
            exit 0
        fi

        # Find and set end conditions.
        if [[ "${STATUS}" == "completed" ]]; then RES="success"; fi
        if [[ $(($(date +%s)-timertimeout)) -ge $TIMEOUT ]]; then RES="timeout"; fi
        if [[ $(($(date +%s)-timertoken)) -ge $((TOKENTIMEOUT-20)) ]]; then
            # Refresh the token after 845 secs (it lives for 900 secs)
            AUTH=$(curl -X POST -j -c acquia.txt --silent https://accounts.acquia.com/api/auth/oauth/token --data-urlencode "client_id=${COB_DEPLOY_API_KEY}" --data-urlencode "client_secret=${COB_DEPLOY_API_SECRET}" --data-urlencode "grant_type=client_credentials")
            ERR=$(php -r "\$a=(json_decode('$AUTH')); if (!empty(\$a->error)) printf(\$a->error);")
            if [[ "${ERR}" != "" ]]; then
                MSG=$(php -r "\$a=(json_decode('$RESULT')); printf(\$a->error_description);")
                slackErrors="${slackErrors}\n- :large_orange_diamond: Auth key expired and could not be renewed - $MSG"
                RES="fail"
            fi
            TOKEN=$(php -r "\$a=(json_decode('$AUTH')); if (!empty(\$a->access_token)) printf(\$a->access_token);")
            if [[ $TOKEN == "" ]]; then
                slackErrors="${slackErrors}\n- :large_orange_diamond: Auth key expired and could not be renewed."
                RES="fail"
            fi
            timertoken=$(date +%s)
        fi
        # Setting NOTIFICATION to empty will end the loop.
        if [[ $RES != "" ]]; then NOTIFICATION=""; fi

    done

    if [[ "${RES}" == "timeout" ]]; then
        slackErrors="${slackErrors}\n- :small_orange_diamond: Timeout with $DBTARGET DB Backup."
        RES="fail"
    fi

    echo ${RES}
}


function cleanup_backups() {
    # This removes all user & script generated backups which are more than 30days old.
    SITE="${1}"
    DBTARGET="${2}"
    AGE=30
    TIMEOUT=180
    CLEANOUTLIMIT=10
    if [[ "${3}" != "" ]]; then AGE="${3}"; fi
    if [[ "${4}" != "" ]]; then TIMEOUT="${4}"; fi
    APP_UUID="5ad427f5-60d6-48fd-983e-670ddc7767c4"
    ENDPOINT="https://cloud.acquia.com/api"

    # Get authenticated.
    # NOTE: COB_DEPLOY_API_KEY/SECRET variables are ENVARs set in Acquia cloud UI.
    AUTH=$(curl -X POST -j -c acquia.txt --silent https://accounts.acquia.com/api/auth/oauth/token --data-urlencode "client_id=${COB_DEPLOY_API_KEY}" --data-urlencode "client_secret=${COB_DEPLOY_API_SECRET}" --data-urlencode "grant_type=client_credentials")
    ERR=$(php -r "\$a=(json_decode('$AUTH')); if (!empty(\$a->error)) printf(\$a->error);")
    if [[ "${ERR}" != "" ]]; then
        MSG=$(php -r "\$a=(json_decode('$RESULT')); printf(\$a->error_description);")
        slackErrors="${slackErrors}\n- :large_orange_diamond: Problem getting authenticated by Acquia Cloud - $MSG"
        echo "fail"
        exit 0
    fi

    TOKEN=$(php -r "\$a=(json_decode('$AUTH')); if (!empty(\$a->access_token)) printf(\$a->access_token);")
    if [[ $TOKEN == "" ]]; then
        slackErrors="${slackErrors}\n- :large_orange_diamond: Problem getting authenticated by Acquia Cloud"
        echo "fail"
        exit 0
    fi

    # This timer will time-out the script.
    # It is also used to manage the token which itself is only valid for 300 secs.
    # Token life check is done in the while loop at the bottom of this function.
    TOKENTIMEOUT=$(php -r "\$a=(json_decode('$AUTH')); (!empty(\$a->expires_in)) ? printf(\$a->expires_in) : 300;")
    timertoken=$(date +%s)
    timertimeout=${timertoken}

    # Find correct environment.
    QUERYSTRING="filter=name%3D$DBTARGET"
    RESULT=$(curl --location --silent -b acquia.txt "${ENDPOINT}/applications/${APP_UUID}/environments?${QUERYSTRING}" --header "Authorization: Bearer ${TOKEN}")
    ERR=$(php -r "\$a=(json_decode('$RESULT')); if (!empty(\$a->error)) printf(\$a->error);")
    if [[ "${ERR}" != "" ]]; then
        MSG=$(php -r "\$a=(json_decode('$RESULT')); printf(\$a->message);")
        slackErrors="${slackErrors}\n- :large_orange_diamond: Problem finding $DBTARGET environment - $MSG"
        echo "fail"
        exit 0
    fi
    TARGET_ENV_ID=$(php -r "\$a=(json_decode('$RESULT')); printf(\$a->_embedded->items[0]->id);")

    # Now find all the backups that are stored, and loop through them and delete them.
    # Note: This is an async task, but we do not need to wait for completion.
    AGEDATE=$(date +%FT%T%z --date="$AGE days ago")
    QUERYSTRING="filter=type%3Dondemand;created%3C$AGEDATE&limit=$CLEANOUTLIMIT"
    RESULT=$(curl --location --silent -b acquia.txt "${ENDPOINT}/environments/${TARGET_ENV_ID}/databases/${SITE}/backups?${QUERYSTRING}" --header "Authorization: Bearer ${TOKEN}")
    ERR=$(php -r "\$a=(json_decode('$RESULT')); if (!empty(\$a->error)) printf(\$a->error);")
    if [[ "${ERR}" != "" ]]; then
        MSG=$(php -r "\$a=(json_decode('$RESULT')); printf(\$a->message);")
        slackErrors="${slackErrors}\n- :large_orange_diamond: Errors removing old backups - $MSG"
        return "fail"
        exit 0
    fi
    CNT=0
    BACKID=0
    RES="success"
    while [[ "${BACKID}" != "" ]]; do
        BACKID=$(php -r "\$a=(json_decode('$RESULT')); (!empty(\$a->_embedded->items[$CNT]) ? printf(\$a->_embedded->items[$CNT]->id) : '');")
        if [[ $BACKID != "" ]]; then
            FLAG=$(php -r "\$a=(json_decode('$RESULT')); (!empty(\$a->_embedded->items[$CNT]) ? printf((\$a->_embedded->items[$CNT]->flags->deleted ? 'true' : 'false')) : '');")
            if [[ "${FLAG}" == "false" ]]; then
                if [[ $CNT -ne 0 ]]; then sleep 10; fi
                RESULT=$(curl -X DELETE --location --silent -b acquia.txt "${ENDPOINT}/environments/${TARGET_ENV_ID}/databases/${SITE}/backups/${BACKID}" --header "Authorization: Bearer ${TOKEN}")
                ERR=$(php -r "\$a=(json_decode('$RESULT')); if (!empty(\$a->error)) printf(\$a->error);")
                if [[ "${ERR}" != "" ]]; then
                    MSG=$(php -r "\$a=(json_decode('$RESULT')); printf(\$a->message);")
                    slackErrors="${slackErrors}\n- :large_orange_diamond: Errors removing old backups - $MSG"
                    return "fail"
                    exit 0
                fi
            fi
        fi

        # Handle timeouts.
        if [[ $(($(date +%s)-timertimeout)) -ge $TIMEOUT ]]; then RES="timeout"; fi
        if [[ $(($(date +%s)-timertoken)) -ge $((TOKENTIMEOUT-20)) ]]; then
            # Refresh the token after 845 secs (it lives for 900 secs)
            AUTH=$(curl -X POST -j -c acquia.txt --silent https://accounts.acquia.com/api/auth/oauth/token --data-urlencode "client_id=${COB_DEPLOY_API_KEY}" --data-urlencode "client_secret=${COB_DEPLOY_API_SECRET}" --data-urlencode "grant_type=client_credentials")
            ERR=$(php -r "\$a=(json_decode('$AUTH')); if (!empty(\$a->error)) printf(\$a->error);")
            if [[ "${ERR}" != "" ]]; then
                MSG=$(php -r "\$a=(json_decode('$RESULT')); printf(\$a->error_description);")
                slackErrors="${slackErrors}\n- :large_orange_diamond: Auth key expired and could not be renewed - $MSG"
                RES="fail"
            fi
            TOKEN=$(php -r "\$a=(json_decode('$AUTH')); if (!empty(\$a->access_token)) printf(\$a->access_token);")
            if [[ $TOKEN == "" ]]; then
                slackErrors="${slackErrors}\n- :large_orange_diamond: Auth key expired and could not be renewed."
                RES="fail"
            fi
            timertoken=$(date +%s)
        fi

        CNT=$((CNT+1))
    done

    if [[ "${RES}" == "timeout" ]]; then
        slackErrors="${slackErrors}\n- :small_orange_diamond: Timeout with $DBTARGET DB Backup."
        RES="fail"
    fi

    echo $RES
}
