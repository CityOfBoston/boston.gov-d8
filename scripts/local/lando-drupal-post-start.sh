#!/bin/bash

###############################################################
#  These commands need to be run as normal user from lando.yml.
#
#  NOTE: THIS SCRIPT SHOULD BE RUN INSIDE THE CONTAINER.
#
#  These commands install Drupal, sync down a database from Acquia
#  and update that Database with local & current repo settings.
#
#  PRE-REQUISITES:
#     - docker container for appserver is created and started, and
#     - main boston.gov repo is already cloned onto the host machine, and
#     - .lando.yml and .config.yml files are correctly configured.
#
#  Basic workflow:
#     1. Use composer to gather all Drupal core and contributed modules.
#     2. Clone the private repo and merge into the main repo.
#     3. Prepare/update settings.php and other settings files
#     4. Create the Drupal MySQL Database (initialize new or sync existing from remote)
#     5. Import configuration from main repo (already cloned locally)
#     6. Ensure the Drupal site is configured properly for develop activities
#     7. Run any finalization tasks to complete.
###############################################################

    # Include the utilities file/libraries.
    # This causes the .lando.yml and .config.yml files to be read in and stored as variables.
    REPO_ROOT="${LANDO_MOUNT}"
    . "${LANDO_MOUNT}/scripts/cob_build_utilities.sh"
    . "${LANDO_MOUNT}/scripts/deploy/cob_utilities.sh"

    printout "SCRIPT" "starts <$(basename $BASH_SOURCE)>"
    printf "\n"
    printf "${Green}${InvertOn}  ================================================================================\n"
    printout "SUCCESS" "${InvertOn}Docker containers are now started.                                            |"
    printf "${Green}${InvertOn}  ================================================================================\n\n"

    # We are now at the end of the build and/or start process.

    # Printout anything that has been cached into setup/uli.log.
    if [[ -e ${REPO_ROOT}/setup/uli.log ]]; then
        cat ${REPO_ROOT}/setup/uli.log && rm -f ${REPO_ROOT}/setup/uli.log;
    else
        . ${REPO_ROOT}/scripts/doit/branding.sh;
    fi

    # Embed the custom xdebug file as a php ini file.
    # There are 2 customized ini's one per environment (mac and linux) -they should not be changed locally by the
    # user but can be modified in the private repo to improve debug experience for all users.
    # The files are initially copied out of the private repo (in the step above). Then the appropriate file is
    # soft-linked (in step below) to link it from the app folder (i.e. mounted from the host) into the folder that
    # php sweeps for ini files during php bootstraps.
    # NOTE: you should restart the container (e.g. using portainer) to implement changes.
    # NOTE: Changes made in the PHP ini files provided by Lando will be lost/reset when Lando container is restarted.

    # Find the host OS.
    OS=${LANDO_HOST_OS}
    if [[ -z ${OS} ]]; then
        # WARNING: This will return the OS of the container (i.e. LINUX).
        OS=$(operating_system)
    fi

    if [[ "$OS" == "LINUX" ]] || [[ "$OS" == "linux" ]]; then
        printout "INFO" "The Host PC is Linux"
        xdebug="${LANDO_MOUNT}/xdebug_linux.ini"
        # Update xdebug file with correct remote_host.
        sed -i "s/host\.docker\.internal/${LANDO_HOST_IP}/g" ${xdebug} && sed -i "s/_host=[0-9\.]*/_host=$LANDO_HOST_IP/g" ${xdebug}
    elif [[ "$OS" == "OSX" ]] || [[ "$OS" == "darwin" ]]; then
        printout "INFO" "The Host PC is MacOSX"
        xdebug="${LANDO_MOUNT}/xdebug_mac.ini"
    else
        printout "INFO" "Brace yourself, you look to be running Windows"
    fi

    # Add a php ini file to set customized PHP configurations within the container.
    # For example, memory allocation, timeouts, error handling etc.
    if [[ -n ${xdebug} ]]; then
        if [[ -e /usr/local/etc/php/conf.d/php_cob.ini ]]; then
            rm /usr/local/etc/php/conf.d/php_cob.ini
        fi
        ln -s ${xdebug} /usr/local/etc/php/conf.d/php_cob.ini
        chmod 600 ${xdebug}
    fi

    # Link the local-dev php.ini file.
    # The file below is where developers should add their individual php ini customizations.
    # For example IPAddresses, memory allocations etc
    # The file is not tracked by git, so manual changes will potentially be lost when the
    # app is rebuilt.
    printout "ACTION" "Setup XDebug in Drupal container."
    if [[ -e /usr/local/etc/php/conf.d/boston-dev-php.ini ]]; then
        rm /usr/local/etc/php/conf.d/boston-dev-php.ini
    fi
    ln -s ${LANDO_MOUNT}/scripts/local/boston-dev-php.ini /usr/local/etc/php/conf.d/
    chmod 777 ${LANDO_MOUNT}/scripts/local/boston-dev-php.ini

    # Restart apache to get those files loaded.
    printout "ACTION" "Restart Apache service in Drupal container."
    service apache2 stop && service apache2 start

    # Always provide this block
    printf "${Bold}\n"
    printf "===============================================================================================\n"
    printf "DEVELOPMENT:\n"
    path="${lando_config_webroot/$REPO_ROOT/}"
    printf " 1. Drupal custom module source files can be editted in host folder ${path}/modules/custom\n"
    path="${patterns_local_repo_local_dir/$REPO_ROOT/}"
    printf " 2. Patterns source files can be editted in host folder ${path}\n"
    printf " 3. WebApp source files can be editted in host folder ${webapps_local_local_dir}/\n"
    printf "LOCAL TESTING:\n"
    printf " 1. Drupal website can be viewed at https://boston.lndo.site\n"
    printf " 2. Fleet website (Patterns) can be viewed at https://patterns.lndo.site:3030\n"
    printf " 3. Local patterns CDN at http://patterns.lndo.site:3030\n"
    printf " 3. WebApps can be viewed at https://node.lndo.site/[appname]/index.html\n"
    printf "===============================================================================================\n\n${NC}"

    printout "SCRIPT" "ends <$(basename $BASH_SOURCE)>"
    printf "\n"
