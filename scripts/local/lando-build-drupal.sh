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

    # Create additional working variables.
    target_env="local"
    project_sync=${project_docroot}/${build_local_config_sync}
    LANDO_APP_URL="https://${LANDO_APP_NAME}.${LANDO_DOMAIN}"

    timer=$(date +%s)
    quiet=0
    yes=0
    # Check for options/flags passed in.
    while getopts ":yq" opt; do
        case $opt in
            y) yes="1";;
            q) yes="1" && quiet="1";;
        esac
    done

    printf "\n"
    printout "SCRIPT" "starts <$(basename $BASH_SOURCE)>"
    printf "\n"
    printf "${Blue}       ================================================================================${NC}\n"
    printout "STEP" "DRUPAL Installing Drupal framework and dependencies."
    printf "${Blue}       ================================================================================${NC}\n"

    # Manage the setup logs folder, and create a link to the folder that can be accessed from a browser.
    # The folder has been created and permissions set in lando-container-customize.sh
    rm -f ${project_docroot}/sites/default/files/setup &&
        ln -s ${setup_logs} ${project_docroot}/sites/default/files/setup

    # Capture the build info into a file to be printed at end of build process.
    . ${LANDO_MOUNT}/scripts/doit/branding.sh > ${setup_logs}/uli.log

    # Check if drupal is already installed.  If it is flash up a warning.
    if [ -z ${project_docroot}/core/lib/Drupal.php ]; then
        printout "WARNING" "Drupal is already installed."
        printout "" "" "- Local site's un-exported configurations (e.g. by drush cex) will be deleted."
        printout "" "" "- Files in sites/default/files will not be changed (i.e. will not be updated or deleted)."
        printout "" "" "- 'Composer install' will still run and may update/overwrite versions of existing contributed modules."
        printout "" "" "- Any custom module code changes that are not committed to git will be retained."
        printout "" "" "- The git working branch will not be changed."
        if [[ "${yes}" != "1" ]]; then
            while true; do
                read -p "Do you wish to continue?" yn
                case $yn in
                    [Yy]* ) break;;
                    [Nn]* ) exit;;
                    * ) echo "Please answer y or n.";;
                esac
            done
        fi
    fi

    # Install PHP (and other ...) packages/modules using composer:
    #
    # 'composer install' will install using lock file if present.
    #     + If the composer.lock file is present then composer will download the exact versions of packages.modules
    #       defined in the lock file.
    #     + If the lock file is not present, then composer will download the most recent version of packages according
    #       to the version rules in the composer.json file.
    #
    #  At any time after the install, a developer can check for and update all packages to their most recent version
    #  (complying with the version rules in composer.json) by executing:
    #       'composer update' (or lando composer update).
    #  This will download and update the physical package, update composer.json and also update the composer.lock file
    #  with the actual version downloaded.
    #
    #  At any time after the install, developers can update a single file by executing:
    #       'composer require <package>:<rule>' (or lando composer ... from the host)
    #  which will update both the compose.json and composer.lock file with the new version.
    #
    #  Best-practice requires deploy-related builds to use composer install with an updated composer.lock.  This means
    #  that developers should ensure the composer.lock file is kept up to date in the repository, particularly if it
    #  is known that packages have been added or upgraded.

    printout "INFO" "This step downloads and installs the Drupal core files, plus any contributed modules we have specified."
    printout "INFO" "Drupal uses Composer (PHP Package Manager) to install PHP packages and dependencies."
    printout "INFO" "Composer also downloads and installs PHP and JS files required by contributed modules."
    printout "INFO" "The complete Drupal folder structure is created in ${project_docroot} (around our previously cloned repo files)."
    printout "INFO" " - see ${setup_logs}/composer.log for output." "(or ${LANDO_APP_URL}/sites/default/files/setup/composer.log)"
    echo "Executes: > composer install --prefer-dist --no-suggest --no-interaction" > ${setup_logs}/composer.log
    (cd ${LANDO_MOUNT} &&
        composer install --no-suggest --prefer-dist --no-interaction &>> ${setup_logs}/composer.log &&
        composer drupal:scaffold &>> ${setup_logs}/composer.log &&
        echo "DONE." >> ${setup_logs}/composer.log &&
        printout "SUCCESS" "Composer has loaded Drupal core, contrib modules and third-party packages/libraries.\n") ||
          printout "ERROR" "Composer failed.\n"

    printf "${Blue}       ================================================================================${NC}\n"
    printout "STEP" "DRUPAL: Add custom settings for City of Boston (boston.gov) website."
    printf "${Blue}       ================================================================================${NC}\n"
    printout "INFO" "Secret config information is stored in a private repo."
    printout "INFO" "Files from the private repo are now merged into the Drupal folders."
    printout "INFO" "see ${setup_logs}/drush_site_install.log for output." "(or ${LANDO_APP_URL}/sites/default/files/setup/drush_site_install.log)"

    # Clone the private repo and merge files in it with the main repo.
    # The private repo settings are defined in <git.private_repo.xxxx> in .config.yml.
    # 'clone_private_repo' function is contained in lando_utilities.sh.
    printout "ACTION" "Cloning and then merging files from the private repo."
    (clone_private_repo &> ${setup_logs}/drush_site_install.log &&
      printout "SUCCESS" "Repo merged.\n") || printout "ERROR" "Private Reop was not merged.\n"

    # Update the drush.yml file.
    printout "INFO" "CoB use a CLI called 'drush' to administer the website from scripts or interacively via a console."
    printout "INFO" "Drush can administer both local and remote Acquia sites."
    printout "INFO" "Actions performed against remote (Aquia) sites use an Alias which defines SSH credentials."
    printout "ACTION" "Creating or updating drush configuration and aliases."
    drush_file=${LANDO_MOUNT}/drush/drush.yml
    drush_cob=${LANDO_MOUNT}/drush/cob.drush.yml
    (rm -rf ${drush_file} &&
      printf "# Docs at https://github.com/drush-ops/drush/blob/master/examples/example.drush.yml\n\n" > ${drush_file} &&
      printf "options:\n  uri: '${LANDO_APP_URL}'\n  root: '${project_docroot}'\n\n" >> ${drush_file} &&
      cat ${drush_cob} >> ${drush_file} &&
      printout "SUCCESS" "Drush aliases updated.\n") || printout "ERROR" "Drush file a ${drush_file} not created.\n"

    # Create/update settings, private settings and local settings files.
    # 'build_settings' function is contained in lando_utilities.sh.
    printout "INFO" "Drupal uses a number of settings files to define global settings for the website."
    printout "INFO" "This includes run-time settings, database connectivity, core and optional file locations."
    build_settings || printout "ERROR" "Settings file not created - website may not load.\n"

    # Install Content.
    # For local builds, there are 3 DB build strategies:
    #   initialize: This uses drush site-install to create a new Drupal site using the core and contributed modules
    #               previously downloaded by composer.  This method initially creates a fresh site with no custom
    #               modules and then loads the configuration from the repo (usually in docroot/../config/default).
    #               The end result is a fresh site with all the features and functionality, but no content.
    #               This is the slowest method than sync baecause there is a lot of config installing and module
    #               enabling (of both contributed and custom modules) required.
    #   restore:    This mode locates a local backup and restores that into the database container.
    #               This is the quickest build route, but creates a local build with different content to the Acquia
    #               sites, and raises the potential for config-import issues during install.  If you encounter
    #               persistent configuration update issues, then manually fix them and take a new backup or temporarily
    #               switch this option to 'sync' (or 'initialize' to create a website with no content) run a build,
    #               and then take a local backup and try again.
    #   sync:       This mode connects to a remote Drupal site and downloads and restores its database.  That database
    #               already contains both settings and content.  The final step in this process is to import/load the
    #               configuration for the main repo.
    #               The end result is a copy of the remote site and its content updated with the features and
    #               functionality from the repo.
    #               Beacuse configuration import just applies differences between config in the database and files,
    #               there is far less enabling/disabling of modules, and this method is generally quicker than
    #               Initialize.
    #
    # Strategies are defined in <build.local.database.source> in .config.yml and can be 'initialize' or 'sync'.

    printf "${Blue}       ================================================================================${NC}\n"
    printout "STEP" "DRUPAL: Install and update City of Boston content (into database)."
    printf "${Blue}       ================================================================================${NC}\n"
    printout "INFO" "Drupal is a Content Management System with content stored in a relational database."
    printout "INFO" "CoB use a MySQL database to store both Drupal site configurations and boston.gov content."
    printout "INFO" "With this local build, the MySQL database is hosted in the 'database' docker container."
    printout "INFO" "Depending on the build settings, the local DB can either be created or copied from Acquia.\n"

    # If we are restoring from a backup, make sure its correctly defined now, so we can default to sync if needed.
    if [[ "${build_local_database_source}" == "restore" ]]; then
      if [[ -z ${build_local_database_backup_location} ]]; then
        printout "WARNING" "RESTORE mode was requested in the config file, but no backup was provided. Sync mode will be used."
        build_local_database_source="sync"
      elif [[ ! -e  ${build_local_database_backup_location} ]]; then
        printout "WARNING" "RESTORE mode was requested in the config file, but the specified backup file (${build_local_database_backup_location}) is missing. Sync mode will be used."
        build_local_database_source="sync"
      fi
    fi

    if [[ "${build_local_database_source}" == "initialize" ]]; then

        printout "INFO" "This build is using INITIALIZE Mode and Will create a new DB using 'drush site-install' and then import repo configs."
        printout "INFO" " ... with ${lando_services_database_type=mysql} database '${lando_services_database_creds_database}' on '${lando_services_database_host}:${lando_services_database_portforward}' in container '${LANDO_APP_PROJECT}_database_1'"
        printout "INFO" " -> This will take some time ..."

        # Define the site-install command.
        SITE_INSTALL=" site-install ${project_profile_name} \
          --db-url=${lando_services_database_creds_database}://${lando_services_database_creds_user}:${lando_services_database_creds_password}@${build_local_database_host}:${build_local_database_port}/${lando_services_database_creds_database} \
          --site-name=${lando_name} \
          --site-mail=${drupal_account_mail} \
          --account-name=${drupal_account_name} \
          --account-pass=${drupal_account_password} \
          --account-mail=${drupal_account_mail} \
          --sites-subdir=${drupal_multisite_name} \
          -vvv \
          -y"

        # Now run the site-install command.
        printout "ACTION" "Installing Drupal with an initial database containing no content."
        echo "Executing: ${SITE_INSTALL}" >> ${setup_logs}/drush_site_install.log

        (${drush_cmd} ${SITE_INSTALL} >> ${setup_logs}/drush_site_install.log &&
          printout "SUCCESS" "Site is freshly installed with clean database.\n") ||
            (printout "ERROR" "Fail - Site install failure" "Check ${setup_logs}/drush_site_install.log for issues." &&
              exit 1)

        # Each Drupal site has a unique site UUID.
        # If we have exported configs from an existing site, and try to import them into a new (or different) site, then
        # Drupal recognizes this and prevents the entire import.
        # Since the configs saved in the repo are from a different site than the one we have just created, the UUID in
        # the configs wont match the UUID in the database.  To continue, we need to update the UUID of the new site to
        # be the same as that in the </config/default/system.site.yml> file.

        if [[ -s ${LANDO_MOUNT}/config/default/system.site.yml ]]; then
            # Fetch site UUID from the configs in the (newly made) database.
            db_uuid=$(${drush_cmd} cget "system.site" "uuid" | grep -Eo "\s[0-9a-h\-]*")
            # Fetch the site UUID from the configuration file.
            yml_uuid=$(cat ${LANDO_MOUNT}/config/default/system.site.yml | grep "uuid:" | grep -Eo "\s[0-9a-h\-]*")

            if [[ "${db_uuid}" != "${yml_uuid}" ]]; then
                # The config UUID is different to the UUID in the database, so we will change the databases UUID to
                # match the config files UUID and all should be good.
                ${drush_cmd} cset "system.site" "uuid" "${yml_uuid}" -y &> /dev/null
                if [[ $? -eq 0 ]]; then
                    printout "INFO" "UUID in DB is updated to ${yml_uuid}."
                fi
            fi
        fi

    elif [[ "${build_local_database_source}" == "restore" ]]; then

        printout "INFO" "This build is using RESTORE Mode and will restore the DB from ${build_local_database_backup_location}."
        printout "INFO" " -> This will take some time ..."

        printout "ACTION" "Restoring database."
        (${drush_cmd} -y sql:drop --database=default > ${setup_logs}/drush_site_install.log &&
          ${drush_cmd} -y sql:cli --database=default < ${build_local_database_backup_location} &&
          printout "SUCCESS" "Database has been restored.\n") || (printout "ERROR" "Database restore failed.\n" && exit 1)

    elif [[ "${build_local_database_source}" == "sync" ]]; then

        # Grab a copy of the database from the desired(remote) Acquia environent.
        #
        # The database which is sync'd is defined in <build.local.database.drush_alias> in .config.yml
        #
        # Considerations:  When sync'ing from:
        #   @bostond8.dev = (content from Acquia develop environment).
        #                   Fastest, config is closest to the config in the repo.  Content of DB is a bit out of date.
        #   @bostond8.test = (content from Acquia stage environment).
        #                    Slower, b/c config is different from the repo so cim changes more.  Content is more up-to-date.
        #   @bostond8.prod = (content from Acquia prod environment).
        #                    Slower, b/c config is different from the repo so cim is slower.  Content up-to-date.
        #                    Adds load to production server b/c backup and rsync processes originate on prod server.

        # Ensure a remote source is defined - if not, default to the develop environment on Acquia.
        if [[ -z ${build_local_database_drush_alias} ]]; then build_local_database_drush_alias="@bostond8.dev"; fi

        printout "INFO" "This build is using SYNC Mode and will copy a remote DB into the locak database docker container."
        printout "INFO" "Remote database going to be downloaded from ${build_local_database_drush_alias}."
        printout "INFO" " -> This will take some time ..."

        printout "ACTION" "Copying database and content."
        # To be sure we eliminate all existing data we first drop the local DB, and then download a backup from the
        # remote server, and restore into the database container.
        (${drush_cmd} -y sql:drop --database=default > ${setup_logs}/drush_site_install.log &&
            ${drush_cmd} -y sql:sync --skip-tables-key=common --structure-tables-key=common ${build_local_database_drush_alias} @self >> ${setup_logs}/drush_site_install.log &&
            printout "SUCCESS" "Site is installed with database and content from remote environment.\n") || (printout "ERROR" "Fail - Database sync" "Check ${setup_logs}/drush_site_install.log for issues.\n" && exit 1)
    elif [[ "${build_local_database_source}" == "none" ]]; then
        printout "INFO" "This build is using NONE Mode. Existing DB is unchanged."
        printout "SUCCESS" "Did nothing.\n"
    fi

    # Import configurations from the project repo into the database.
    printout "INFO" "Drupal websites are comprised of entities which make up components that appear on webpages."
    printout "INFO" "Each module, entity and component requires configuration information which is initially provided in (yaml) files."
    printout "INFO" "These files are imported into the database, adding to or overwriting default information."
    if [[ "${build_local_database_source}" == "sync" ]]; then
        printout "INFO" "This build is updating an existing database."
        printout "INFO" " -> Depending on how different the DB source is to the config files, this may also take some time ..."
    elif [[ "${build_local_database_source}" == "initialize" ]]; then
        printout "INFO" "This build has a new and essentialy empty database."
        printout "INFO" " -> this import will take some time ..."
    fi
    printout "INFO" "Follow along at ${setup_logs}/config_import.log or ${LANDO_APP_URL}/sites/default/files/setup/config_import.log\n"

    printout "ACTION" "Importing configuration."
    ${drush_cmd} config-import sync -y &> ${setup_logs}/config_import.log
    if [[ $? -eq 0 ]]; then
        printout "SUCCESS" "Config from the repo has been applied to the database.\n"
    else
        # If we have sync'd a remote database, some of the configs we want to import may not be able to be applied.
        # The work aound is to try a partial configuration import.
        printout "" "\n"
        printout "WARNING" "==== Config Import Errors ========================="
        printout "WARNING" "Showing last 25 log messages from config_import"
        tail -25 ${setup_logs}/config_import.log
        printout "" "       ---------------------------------------------------\n"
        printout "WARNING" "Will retry a partial config import."
        echo "=> Retry partial cim." >> ${setup_logs}/config_import.log

        ${drush_cmd} config-import sync --partial -y &>> ${setup_logs}/config_import.log

        if [[ $? -eq 0 ]]; then
            printout "SUCCESS" "Config from the repo has been applied to the database.\n"
        else
            printout "WARNING" "==== Config Import Errors (2nd attempt) ==========="
            printout "WARNING" "Will retry a partial config import again."
            echo "Retry partial cim (#2)." >> ${setup_logs}/config_import.log
            ${drush_cmd} config-import sync --partial -y &>> ${setup_logs}/config_import.log

            if [[ $? -eq 0 ]]; then
                printout "SUCCESS" "Config from the repo has been applied to the database.\n"
            else
                # Uh oh!
                printout "" "\n"
                printout "ERROR" "==== Config Import Errors (3rd attempt) ==========="
                printout "ERROR" "Showing last 50 log messages from config_import"
                tail -50 ${setup_logs}/config_import.log
                printout "ERROR" "Config Import Fail." "\n - Check ${setup_logs}/config_import.log for full printout of attempted process."
                printout "" "" " -Will continue continue build."
                # Capture the error and save for later display
                echo -e "\n${RedBG}  ============================================================================== ${NC}"  >> ${setup_logs}/uli.log
                echo -e   "${RedBG} |              IMPORTANT:The configuration import failed.                      |${NC}"  >> ${setup_logs}/uli.log
                echo -e   "${RedBG} |    Please check /app/setup/config_import.log and fix before continuing.      |${NC}"  >> ${setup_logs}/uli.log
                echo -e   "${RedBG}  ============================================================================== ${NC}\n"  >> ${setup_logs}/uli.log
            fi
        fi
    fi

    # Enable and disable modules specific to developers.
    # Whichever build method employed, the modules in <config/default/core.extensions.yml> will have been enabled.
    # However, there is no guarantee that those modules are entirely approproate for developers.  So this step allows us
    # to specifically enable the modules needed by developers.
    # Function 'devModules' is contained in /scripts/deploy/cob_utilities.sh
    printout "INFO" "Some Drupal modules/functionality are only required on production sites, and others on local/dev sites."
    printout "ACTION" "Enabling appropriate development features and functionality."
    devModules "@self"
    # Set the local build to use a local patterns (if the node container has fleet running in it).
    if [[ "${patterns_local_build}" != "true" ]] && [[ "${patterns_local_build}" != "True" ]] && [[ "${patterns_local_build}" != "TRUE" ]]; then
        drush bcss 2
        printout "INFO" "Patterns css and js will be served from the local node container."
    fi
    printout "SUCCESS" "Development environment set.\n"

    # Run finalization / housekeeping tasks.

    # Apply any pending database updates.
    printout "ACTION" "Apply pending database updates etc."
    ${drush_cmd} updb -y >> ${setup_logs}/config_import.log
    printout "SUCCESS" "Updates Completed.\n"

    # Rebuild user access on nodes.
#    printout "ACTION" "Rebuild user access on nodes."
#    ${drush_cmd} eval "node_access_rebuild();" >> ${setup_logs}/config_import.log
#    printout "SUCCESS" "Updates run.\n"

    # Capture the build info into a file to be printed at end of build process.
    printout "INFO" "The production website master ${drupal_account_name} account is a randomized string."
    printout "ACTION" "Changing the local ${drupal_account_name} password to '${drupal_account_password}'."
    printf "The ${drupal_account_name} account password is reset to: ${drupal_account_password}.\n" >> ${setup_logs}/uli.log
    (${drush_cmd} user:password ${drupal_account_name} "${drupal_account_password}" &> /dev/null &&
      ${drush_cmd} user-login --name=${drupal_account_name} >> ${setup_logs}/uli.log &&
      printout "SUCCESS" "Password changed.\n") || printout "WARNING" "Password was not changed.\n"

    text=$(displayTime $(($(date +%s)-timer)))
    printout "INFO" "Drupal build finished." "Drupal install & build took ${text}"

    printout "SCRIPT" "ends <$(basename $BASH_SOURCE)>"
    printf "\n"
