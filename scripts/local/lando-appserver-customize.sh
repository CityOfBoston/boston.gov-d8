#!/bin/bash
  ###############################################################
  #  These commands need to be run as root/admin user from lando.yml.
  #
  #  Essentially these commands are installing packages we require
  #  in the local docker appserver (PHP-Drupal) container.
  ###############################################################

    # Include the utilities file/library.
    # This causes the .lando.yml and .config.yml files to be read in and stored as variables.
    REPO_ROOT="${LANDO_MOUNT}"
    . "${LANDO_MOUNT}/scripts/cob_build_utilities.sh"

    # Create script variables
    target_env="local"
    setup_logs="${LANDO_MOUNT}/setup"
    LANDO_APP_URL="https://${LANDO_APP_NAME}.${LANDO_DOMAIN}"

    printf "\n"
    printf "ref: $(basename "$0")\n"
    printf "\n${LightPurple}       ================================================================================${NC}\n"
    printout "STEP" "Installing Linux packages in appserver container."
    printf "${LightPurple}       ================================================================================${NC}\n"

    # Prepare the folder which will hold setup logs.
    if [[ -e  ${setup_logs} ]]; then rm -rf ${setup_logs}/; fi
    mkdir -p ${setup_logs} &&
        chown www-data:www-data ${setup_logs} &&
        chmod 777 ${setup_logs};

    printout "INFO" "During build, container build actions will be logged to files in ${setup_logs}"
    printout "" "     - After build, log files can be accessed from ${LANDO_APP_URL}/sites/default/files/setup/"

    # Installs linux apps and extensions into the appserver container.
    apt-get update &> /dev/null && apt-get install -y --no-install-recommends apt-utils  &> /dev/null
    apt-get install -y --no-install-recommends zip unzip bzip2 libbz2-dev libgd-dev mysql-client openssh-client vim jq cron renameutils rename &>> ${setup_logs}/lando.log
    docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ &>> ${setup_logs}/lando.log

    # Change the permissions on the log file so that non-root user can add to log.
    chmod 777 ${LANDO_MOUNT}/setup/lando.log &>> ${setup_logs}/lando.log

    # Install a custom php config file.
    cp ${LANDO_MOUNT}/scripts/local/boston-dev-php.ini /usr/local/etc/php/conf.d/ &>> ${setup_logs}/lando.log

    #- ip route | awk 'NR==1 {printf $3}' | xargs echo "xdebug.remote_host=" >>/usr/local/etc/php/conf.d/boston-dev-php.ini
    service apache2 reload &>> ${setup_logs}/lando.log

    printout "SUCCESS" "Docker container 'appserver' is built.\n"
