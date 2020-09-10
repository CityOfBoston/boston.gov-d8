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
    printf "ref: $(basename $BASH_SOURCE) \n"
    printf "\n${LightPurple}       ================================================================================${NC}\n"
    printout "STEP" "Installing Linux packages in appserver container."
    printf "${LightPurple}       ================================================================================${NC}\n"

    # Copy the 2 scrpts that the database server needs from the scripts folder into the .lando scripts folder.
    # This means the scripts will be loaded into the /helpers folder on all containers.
    # This means we can exclude the /app folder from mounting into the database giving it a performance boost.
    cp "${LANDO_MOUNT}/scripts/local/health.sh" /helpers/health.sh
    cp "${LANDO_MOUNT}/scripts/local/lando-database-customize.sh" /helpers/lando-database-customize.sh

    # Prepare the folder which will hold setup logs.
    if [[ -e  ${setup_logs} ]]; then rm -rf ${setup_logs}/; fi
    mkdir -p ${setup_logs} &&
        chown www-data:www-data ${setup_logs} &&
        chmod 777 ${setup_logs};

    printout "INFO" "During build, container build actions will be logged to files in ${setup_logs}"
    printout "" "     - After build, log files can be accessed from ${LANDO_APP_URL}/sites/default/files/setup/"

    # Installs linux apps and extensions into the appserver container.
    apt-get update &> /dev/null && apt-get install -y --no-install-recommends apt-utils  &> /dev/null
    apt-get install -y --no-install-recommends zip unzip bzip2 libbz2-dev libgd-dev mysql-client openssh-client vim jq cron renameutils rename travis  &>> ${setup_logs}/lando.log
    docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ &>> ${setup_logs}/lando.log

    # Change the permissions on the log file so that non-root user can add to log.
    chmod 777 ${LANDO_MOUNT}/setup/lando.log &>> ${setup_logs}/lando.log

    service apache2 reload &>> ${setup_logs}/lando.log

    printout "SUCCESS" "Docker container 'appserver' is built.\n"
