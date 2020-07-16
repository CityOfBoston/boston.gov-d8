#!/bin/bash

slackErrors=""

acquia_db_copy() {
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

acquia_db_backup() {
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

cleanup_tables() {
    # This function removes a series of tables from a database.
    SITE="${1}"
    DBTARGET="${2}"
    ALIAS="@$SITE.$DBTARGET"

    if [[ -d /app/docroot ]]; then
        cd /app/docroot
    elif [[ -d /var/www/html/${site}.${target_env} ]]; then
        cd /var/www/html/${site}.${target_env}/
    fi
    if [[ -e "${drush_cmd}" ]]; then drush_cmd="drush"; fi

    # Remove Neighborhoodlookup data (+/-1.4GB each table), and compress.
    ${drush_cmd} ${ALIAS} sql-query "TRUNCATE TABLE node__field_sam_neighborhood_data; OPTIMIZE TABLE node__field_sam_neighborhood_data;"
    ${drush_cmd} ${ALIAS} sql-query "TRUNCATE TABLE node_revision__field_sam_neighborhood_data; ; OPTIMIZE TABLE node_revision__field_sam_neighborhood_data;"
}

cleanup_backups() {
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

sync_db() {
    ALIAS=${1}

    if [ "${target_env}" == "local" ]; then
        cd /app/docroot
    else
        cd /var/www/html/${site}.${target_env}/
    fi

    if [[ -e "${drush_cmd}" ]]; then drush_cmd="drush"; fi

    ${drush_cmd} ${ALIAS} cc drush

    # This should cause drupal to find new modules prior to trying to import their configs.
    ${drush_cmd} ${ALIAS} cr

    # Import configuration, and run any db updates.
    printf " [action] Update database (%s) on %s with configuration from updated code in %s.\n" "${site}" "${target_env}" "${source_branch}"

    ${drush_cmd} ${ALIAS} cim -y
    if [[ $? -ne 0 ]]; then
        slackErrors="${slackErrors}\n- :large_orange_diamond: Problem with configuration sync."
    fi

    ${drush_cmd} ${ALIAS} updb -y
    if [[ $? -ne 0 ]]; then
        slackErrors="${slackErrors}\n- :large_orange_diamond: Problem with executing DB updates."
    fi

    if [ "${target_env}" != "local" ]; then

        printf " [action] Ensure ${target_env} site is not in maintenance mode.\n"
        ${drush_cmd} ${ALIAS} sset system.maintenance_mode 0

        printf " [action] Reset password for the admin account to random string.\n"
        # Create a new random password.
        NEWPASSWORD="$(openssl rand -hex 10)"
        ${drush_cmd} ${ALIAS} user:password -y admin "${NEWPASSORD}"

    fi

    # Set the website to use patterns library from heroku staging.
    if [ "${target_env}" == "dev" ]; then
        ${drush_cmd} ${ALIAS} bcss 3
    elif [ "${target_env}" == "test" ]; then
        ${drush_cmd} ${ALIAS} bcss 3
    elif [ "${target_env}" == "prod" ]; then
        ${drush_cmd} ${ALIAS} bcss 3
    elif [ "${target_env}" == "local" ]; then
        ${drush_cmd} ${ALIAS} bcss 2
    else
        ${drush_cmd} ${ALIAS} bcss 3
    fi

}

sync_files() {
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
setVars() {
    # Used as temporary directory for update_all_icons().
    export MAGICK_TEMPORARY_PATH="/home/bostond8/${1}/tmp"
}

# Uses rsync to copy image files from one Acquia server to the current acquia server.
copyFiles() {
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
checkFileFolderMap() {
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

setEnvColor() {
    if [[ -e ${drush_cmd} ]]; then drush_cmd="drush"; fi
    if [[ -e ${1} ]]; then drush_cmd="${drush_cmd} ${1}"; fi

    DRUSH_OPT="-y"
    if [ "${target_env}" == "local" ]; then
        DRUSH_OPT="-y --quiet --no-interaction"
    fi

    if [ "${target_env}" == "dev" ]; then
        fg_color="#ffffff"
        bg_color="#3e0202"
    elif [ "${target_env}" == "test" ]; then
        fg_color="#ffffff"
        bg_color="#b15306"
    elif [ "${target_env}" == "prod" ]; then
        fg_color="#ffffff"
        bg_color="#303655"
    elif [ "${target_env}" == "local" ]; then
        fg_color="#ffffff"
        bg_color="#023e0a"
    fi

    ${drush_cmd} cset ${DRUSH_OPT} environment_indicator.indicator name  ${target_env} > /dev/null
    ${drush_cmd} cset ${DRUSH_OPT} environment_indicator.indicator fg_color ${fg_color} > /dev/null
    ${drush_cmd} cset ${DRUSH_OPT} environment_indicator.indicator bg_color ${bg_color} > /dev/null

}

# Post a message to slack.
slackPost() {
    if [[ -z "${slackErrors}" ]]; then slackErrors=""; fi

    if [[ -z ${slackposter_webhook} ]]; then
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
        if [[ -e ${1} ]]; then
            title="${title} --CHECK"
            status="danger"
            body="The deployment of ${source_branch} to ${target_env} had issues.${slackErrors}\n:information_source: Please check the build log in the Acquia Cloud Console."
        fi
        ${drush_cmd} cset --quiet -y "slackposter.settings" "integration" "${slackposter_webhook}" &&
            ${drush_cmd} cset --quiet -y "slackposter.settings" "channels.default" "drupal"
        ${drush_cmd} slackposter:post "${title}" "${body}" "#drupal" "Acquia Cloud" "${status}"
    fi
}

devModules() {
    printf " [action] Enable DEVELOPMENT-ONLY modules.\n"

    ALIAS="${1}"

    if [[ -e ${drush_cmd} ]]; then drush_cmd="drush"; fi
    if [[ -e ${1} ]]; then drush_cmd="${drush_cmd} ${ALIAS}"; fi
    DRUSH_OPT="-y"
    if [ "${target_env}" == "local" ]; then
        DRUSH_OPT="-y --quiet --no-interaction"
    fi

    # Enable key development modules.
    ${drush_cmd} cdel views.view.migrate_taxonomy &> /dev/null
    ${drush_cmd} cdel views.view.migrate_paragraphs &> /dev/null
    ${drush_cmd} en -y devel,dblog,automated_cron,syslog,twig_xdebug,config_devel,masquerade,migrate,migrate_tools > /dev/null
    if [[ $? -ne 0 ]]; then
        slackErrors="${slackErrors}\n- :small_orange_diamond: Problem enabling required DEV modules in DRUPAL."
    fi

    printf " [action] Enable and set stage_file_proxy.\n"
    ${drush_cmd} en -y stage_file_proxy > /dev/null &&
        ${drush_cmd} cset "stage_file_proxy.settings" "origin" "https://d8-dev.boston.gov" ${DRUSH_OPT} > /dev/null

    # Enable the acquia connector and provide a unique name for monitoring.
    if [[ "${target_env}" == "dev" ]]; then
        site_machine_name="5ad427f5_60d6_48fd_983e_670ddc7767c4__bostond8dev__5de6a9c7a0448"
    elif [[ "${target_env}" == "test" ]]; then
        site_machine_name="__bostond8stg__5de683afc0656"
    elif [[ "${target_env}" == "prod" ]]; then
        site_machine_name="__bostond8__5de699d495e70"
    elif [[ "${target_env}" == "ci" ]] || [[ "${target_env}" == "uat" ]] || [[ "${target_env}" == "dev2" ]] || [[ "${target_env}" == 'dev3' ]] || [[ "${target_env}" == "local" ]]; then
        site_machine_name="none"
    fi
    if [[ "${site_machine_name}" != "none" ]]; then
        printf " [action] Enable and set acquia connector.\n"
        ${drush_cmd} en -y acquia_connector,acquia_purge > /dev/null &&
            ${drush_cmd} ${DRUSH_OPT} config-set "acquia_connector.settings" "subscription_data.active" "true" > /dev/null &&
            ${drush_cmd} ${DRUSH_OPT} config-set "acquia_connector.settings" "subscription_data.subscription_name"  ${site} > /dev/null &&
            ${drush_cmd} ${DRUSH_OPT} config-set "acquia_connector.settings" "subscription_name"  ${site} > /dev/null &&
            ${drush_cmd} ${DRUSH_OPT} config-set "acquia_connector.settings" "spi.site_name"  ${site}.${target_env} > /dev/null &&
            ${drush_cmd} ${DRUSH_OPT} config-set "acquia_connector.settings" "spi.site_machine_name"  "${site_machine_name}" > /dev/null &&
            ${drush_cmd} ${DRUSH_OPT} config-set "acquia_connector.settings" "spi.use_cron"  "0" > /dev/null &&
            ${drush_cmd} p:purger-add --if-not-exists acquia_purge &&
            printf "List purgers.\n" &&
            ${drush_cmd}  p:purger-ls &&
            printf "Purger diagnostics.\n" &&
            ${drush_cmd} p:diagnostics --fields=title,recommendation,value,severity

        if [[ $? -ne 0 ]]; then
            slackErrors="${slackErrors}\n- :red_circle: Problem setting up the Acquia Purge functionality."
        fi

    else
        printf " [action] Disable Acquia connector and purge.\n"
        ${drush_cmd} pmu acquia_connector,acquia_purge ${DRUSH_OPT} > /dev/null
    fi

    # Disable prod-only modules.
    printf " [action] Disable prod-only and unwanted modules.\n"
    ${drush_cmd} pmu autologout,config_devel,migrate_utilities,migrate_upgrade,migrate_drupal,migrate_drupal_ui,field_group_migrate,migrate_plus,bos_migration ${DRUSH_OPT} > /dev/null &&
        ${drush_cmd} en -y config_devel > /dev/null
    if [[ $? -ne 0 ]]; then
        slackErrors="${slackErrors}\n- :small_orange_diamond: Problem disabling unwanted modules in DRUPAL."
    fi


    if [[ "${target_env}" == "local" ]]; then
        ${drush_cmd} pmu simplesamlphp_auth captcha recaptcha_v3 ${DRUSH_OPT} > /dev/null
        printf " [notice] simplesamlphp_auth module is disabled for local builds.\n"
        printf "          If you need to configure this module you will first need to enable it and then \n"
        printf "          run 'lando drupal cis /app/config/default/simplesamlphp_auth.settings.yml' to import its configurations.\n"
    fi

    # Set the environment toolbar colors.
    setEnvColor ${ALIAS}

#    printf "Invalidate everything in Varnish.\n"
#    ${drush_cmd} p:invalidate everything -y
#    ${drush_cmd} p:queue-work --finish --no-interaction

    # Run cron now.
    # ${drush_cmd} cron

    # Write back the config settings to config/default now so that changes (mainly to enabled modules) are recorded.
    # This is done:
    # 1. In case someone subsequently runs a drush cim and re-imports which would reset the module overrides
    #    just made, or
    # 2. Because a config diff run will show (at least) a difference in modules enabled (and possibly their config
    #    settings).
    # ${drush_cmd} cex -y
}
prodModules() {
    printf "\n [action] Enable PRODUCTION-ONLY modules.\n"

    if [[ -e ${drush_cmd} ]]; then drush_cmd="drush"; fi
    if [[ -e ${1} ]]; then drush_cmd="${drush_cmd} ${1}"; fi
    DRUSH_OPT="-y"
    if [ "${target_env}" == "local" ]; then
        DRUSH_OPT="-y --quiet --no-interaction"
    fi

    # Enable key production modules.
    ${drush_cmd} en -y syslog, dynamic_page_cache, autologout > /dev/null
    if [[ $? -ne 0 ]]; then
        slackErrors="${slackErrors}\n- :red_circle: Problem enabling PRODUCTION-specific modules."
    fi

    # Install the purger
    printf "Enable and set acquia connector.\n"
    ${drush_cmd} en -y acquia_connector,acquia_purge,purge_queuer_coretags,purge_processor_lateruntime,purge_processor_cron purge_ui,masquerade > /dev/null
    # Enable the acquia connector and provide a unique name for monitoring.
    if [ "${target_env}" == "dev" ]; then
        site_machine_name="5ad427f5_60d6_48fd_983e_670ddc7767c4__bostond8dev__5de6a9c7a0448"
    elif [ "${target_env}" == "test" ]; then
        site_machine_name="__bostond8stg__5de683afc0656"
    elif [ "${target_env}" == "prod" ]; then
        site_machine_name="__bostond8__5de699d495e70"
    elif [ "${target_env}" == "local" ]; then
        site_machine_name="none"
    fi
    if [ "${site_machine_name}" != "none" ]; then
        printf "Enable and set acquia connector.\n" &&
            ${drush_cmd} en -y acquia_connector,acquia_purge > /dev/null &&
            ${drush_cmd} ${DRUSH_OPT} config-set "acquia_connector.settings" "subscription_data.active" "true" > /dev/null &&
            ${drush_cmd} ${DRUSH_OPT} config-set "acquia_connector.settings" "subscription_data.subscription_name" ${site} > /dev/null &&
            ${drush_cmd} ${DRUSH_OPT} config-set "acquia_connector.settings" "subscription_name" ${site} > /dev/null &&
            ${drush_cmd} ${DRUSH_OPT} config-set "acquia_connector.settings" "spi.site_name" ${site}.${target_env} > /dev/null &&
            ${drush_cmd} ${DRUSH_OPT} config-set "acquia_connector.settings" "spi.site_machine_name" "${site_machine_name}" > /dev/null &&
            ${drush_cmd} ${DRUSH_OPT} config-set "acquia_connector.settings" "spi.use_cron" "0" > /dev/null &&
            ${drush_cmd} p:purger-add --if-not-exists acquia_purge &&
            printf "List purgers.\n" &&
            ${drush_cmd}  p:purger-ls &&
            printf "Purger diagnostics.\n" &&
            ${drush_cmd} p:diagnostics --fields=title,recommendation,value,severity &&
        if [[ $? -ne 0 ]]; then
            slackErrors="${slackErrors}\n- :red_circle: Problem setting up the Acquia Purge functionality."
        fi
    fi

    # ensure we have aggregation
    ${drush_cmd} ${DRUSH_OPT} config-set system.performance css.preprocess 1 > /dev/null &&
        ${drush_cmd} ${DRUSH_OPT} config-set system.performance js.preprocess 1 > /dev/null
    if [[ $? -ne 0 ]]; then
        slackErrors="${slackErrors}\n- :red_circle: Problem enabling css and js aggregation."
    fi


    # Disable dev-only modules.
    ${drush_cmd} pmu devel,config_devel,dblog,stage_file_proxy,automated_cron,twig_xdebug ${DRUSH_OPT} > /dev/null &&
        ${drush_cmd} pmu bos_migration,migrate_utilities,migrate_drupal,migrate_drupal_ui ${DRUSH_OPT} > /dev/null
    if [[ $? -ne 0 ]]; then
        slackErrors="${slackErrors}\n- :red_circle: Problem siabling unwanted modules in PRODUCTION."
    fi

    # Set the environment toolbar colors.
    setEnvColor ${ALIAS}

#    printf "Invalidate everything in Varnish.\n"
#    ${drush_cmd} p:invalidate everything -y
#    ${drush_cmd} p:queue-work --finish --no-interaction

    # Run cron now.
    # ${drush_cmd} cron

    # Write back the config settings to config/default now so that changes (mainly to enabled modules) are recorded.
    # This is done:
    # 1. In case someone subsequently runs a drush cim and re-imports which would reset the module overrides
    #    just made, or
    # 2. Because a config diff run will show (at least) a difference in modules enabled (and possibly their config
    #    settings).
    # ${drush_cmd} cex -y
}

#acquia_db_backup "bostond8" "dev2" 300
#acquia_db_copy "bostond8" "dev2" "dev" 900
#cleanup_backups "bostond8" "dev2" 30 300
