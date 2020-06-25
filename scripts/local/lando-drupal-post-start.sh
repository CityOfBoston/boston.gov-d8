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

    printf "\n"
    printf "ref: $(basename "$0")\n"
    printout "LANDO" "Project Event - post-start\n"
    printf "================================================================================\n"
    printout "SUCCESS" "Docker containers are now started."
    printf "================================================================================\n\n"

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
        printout "INFO" "Host is Linux"
        xdebug="${LANDO_MOUNT}/xdebug_linux.ini"
        # Update xdebug file with correct remote_host.
        sed -i "s/host\.docker\.internal/${LANDO_HOST_IP}/g" ${xdebug} && sed -i "s/_host=[0-9\.]*/_host=$LANDO_HOST_IP/g" ${xdebug}
    elif [[ "$OS" == "OSX" ]] || [[ "$OS" == "darwin" ]]; then
        printout "INFO" "Host is MacOSX"
        xdebug="${LANDO_MOUNT}/xdebug_mac.ini"
    fi

    if [[ -n ${xdebug} ]]; then
        if [[ -e /usr/local/etc/php/conf.d/php_cob.ini ]]; then
            rm /usr/local/etc/php/conf.d/php_cob.ini
        fi
        ln -s ${xdebug} /usr/local/etc/php/conf.d/php_cob.ini
        chmod 600 ${xdebug}
    fi

    # Link the local-dev php.ini file.
    # The file below is where developers should add their individual php ini customizations.  The file is not tracked
    # by git, so changes will potentially be lost when the app is rebuilt.
    if [[ -e /usr/local/etc/php/conf.d/boston-dev-php.ini ]]; then
        rm /usr/local/etc/php/conf.d/boston-dev-php.ini
    fi
    ln -s ${LANDO_MOUNT}/scripts/local/boston-dev-php.ini /usr/local/etc/php/conf.d/
    chmod 777 ${LANDO_MOUNT}/scripts/local/boston-dev-php.ini
