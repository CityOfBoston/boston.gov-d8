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

    printout "SCRIPT" "starts <$(basename $BASH_SOURCE)>"
    printf "\n"
    printf "${Blue}       ================================================================================${NC}\n"
    printout "STEP" "Prepare PHP env in appserver: Installing required Linux packages in container."
    printf "${Blue}       ================================================================================${NC}\n"

    # Prepare the folder which will hold setup logs.
    if [[ -e  ${setup_logs} ]]; then rm -rf ${setup_logs}/; fi
    mkdir -p ${setup_logs} &&
        chown www-data:www-data ${setup_logs} &&
        chmod 777 ${setup_logs} &&
        printout "INFO" "During build, process will be logged to files in ${setup_logs}" &&
        printout "" "     - After build, log files can be accessed from ${LANDO_APP_URL}/sites/default/files/setup/"

    printout "ACTION" "Installing linux utilities/apps/packages not present in default container."
    # Installs linux apps and extensions into the appserver container.
    (apt-get update &> /dev/null && apt-get install -y --no-install-recommends apt-utils  &> /dev/null &&
      apt-get install -y --no-install-recommends zip unzip bzip2 libbz2-dev libgd-dev mysql-client openssh-client vim jq cron renameutils rename travis  &>> ${setup_logs}/lando.log &&
      docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ &>> ${setup_logs}/lando.log &&
      printout "SUCCESS" "All Packages installed.\n") || (printout "ERROR" "Problem installing Linux Packages.\n" && return 1)

    # Copy the 2 scrpts that the database server needs from the scripts folder into the .lando scripts folder.
    # This means the scripts will be loaded into the /helpers folder on all containers.
    # This means we can exclude the /app folder from mounting into the database giving it a performance boost.
    printout "ACTION" "Installing container-health check scripts."
    (cp "${LANDO_MOUNT}/scripts/local/health.sh" /helpers/health.sh &&
      cp "${LANDO_MOUNT}/scripts/local/lando-database-customize.sh" /helpers/lando-database-customize.sh &&
      printout "SUCCESS" "Scripts installed.\n") || printout "WARNING" "Container Health check scripts not installed.\n"

    # Create a clean folder into which the Patterns repo can be cloned.
    # First delete the existing folder : for some reason the patterns folder canot be deleted from within the
    # node container (possibly because files have been altered in other containers,.
    printout "ACTION" "Removing Patterns repo (it will be re-cloned later)."
    if [[ -d ${patterns_local_repo_local_dir} ]]; then
      # Try to remove the folder.  This may be difficult.
      rm -rf ${patterns_local_repo_local_dir}
      # If the folder still exists, try to rename it
      if [[ -d ${patterns_local_repo_local_dir} ]]; then
        if [[ -d ${patterns_local_repo_local_dir}_old ]]; then rm -rf ${patterns_local_repo_local_dir}_old; fi
        mv -f ${patterns_local_repo_local_dir} ${patterns_local_repo_local_dir}_old
      fi
    fi
    # So now try to make the folder again (empty).  If this fails then we know we have not created an new empty folder.
    (mkdir ${patterns_local_repo_local_dir} &&
      printout "SUCCESS" "Patterns repo removed.\n") || printout "WARN" "Patterns repo was not removed.  Will try again later.\n"

    # Change the permissions on the log file so that non-root user can add to log.
    chmod 777 ${LANDO_MOUNT}/setup/lando.log &>> ${setup_logs}/lando.log

    printout "ACTION" "Restarting Appserver's Apache webserver."
    (service apache2 reload &>> ${setup_logs}/lando.log &&
      printout "SUCCESS" "Apache restarted.\n") || printout "WARNING" "Apache restarted failed.\n"

    printout "SCRIPT" "ends <$(basename $BASH_SOURCE)>"
  printf "\n"
