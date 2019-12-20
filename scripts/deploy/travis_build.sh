#!/bin/bash

###############################################################
#  These commands will be run from the Travis container as it builds.
#
#  These commands install Drupal, sync down a database from Acquia
#  and update that Database with local & current repo settings.
#
#  PRE-REQUISITES:
#     - Travis container definition in .travis.yml, and
#     - main boston.gov repo is already cloned onto the host machine, and
#     - .lando.yml and .config.yml files are correctly configured.
#
#  Basic workflow:
#     1. Use composer to gather all Drupal core and contributed modules.
#     2. Clone the private repo and merge into the main repo.
#     3. Prepare/update settings.php and other settings files
#     4. Create the Drupal MySQL Database (initialize new or sync existing from remote)
#     5. Import configuration from main repo (already cloned locally)
#     6. Run code validation.

#  Travis envars defined here:
#    https://docs.travis-ci.com/user/environment-variables/#default-environment-variables
#
###############################################################

    # Include the utilities file/libraries.
    # This causes the .lando.yml and .config.yml files to be read in and stored as variables.
    REPO_ROOT="${TRAVIS_BUILD_DIR}"
    . "${TRAVIS_BUILD_DIR}/scripts/cob_build_utilities.sh"

    # Create additional working variables.
    timer=$(date +%s)
    yes=1
    target_env="local"
    setup_logs="${TRAVIS_BUILD_DIR}/setup"
    project_sync=$(realpath ${project_docroot}/${build_local_config_sync})
    set src="build_travis_${TRAVIS_BRANCH}_type" && build_local_type="${!src}"
    set src="build_travis_${TRAVIS_BRANCH}_suppress_output" && quiet="${!src}"
    src="build_travis_${TRAVIS_BRANCH}_database_source" && build_local_database_source="${!src}"
    set src="build_travis_${TRAVIS_BRANCH}_database_drush_alias" && build_local_database_drush_alias="${!src}"
    set src="build_travis_config_${TRAVIS_BRANCH}_sync" && build_local_config_dosync="${!src}"
    isHotfix=0
    if echo ${TRAVIS_COMMIT_MESSAGE} | grep -iqF "hotfix"; then isHotfix=1; fi
    drush_cmd="${TRAVIS_BUILD_DIR}/vendor/bin/drush  -r ${TRAVIS_BUILD_DIR}/docroot -l default"

    # RUN THIS BLOCK FOR BOTH GITHUB ==PULL REQUESTS== AND ==MERGES== (PUSHES).
    # Because we always need to:
    #  - gather the files from the commits in the PR (this is already done by travis before this
    #    script executes), and
    #  - add in the drupal core files, required contributed modules and dependent vendor packages, and
    #  - merge in the files from the private repo.

    if [[ "${TRAVIS_EVENT_TYPE}" == "pull_request" ]] || [[ "${TRAVIS_EVENT_TYPE}" == "push" ]]; then

        if [ ! -e  /usr/local/bin/drupal ]; then
            sudo ln -s ${TRAVIS_BUILD_DIR}/vendor/drupal/console/bin/drupal /usr/local/bin/
        fi

        # Set the Acquia environment variable.
        if [ ${TRAVIS_BRANCH} == "master" ]; then
            export AH_SITE_ENVIRONMENT="prod"
        else
            export AH_SITE_ENVIRONMENT="dev"
        fi

        # Make an account for drupal in MySQL (better than using root a/c).
        mysql -u root -e "GRANT ALL PRIVILEGES ON *.* TO 'drupal'@'localhost' IDENTIFIED BY 'drupal';"

        printout "" "\n========================================================================================="
        printout "INFO" "Creating the Build Candidate."
        printout "" "=========================================================================================\n"

        # Install PHP (and other ...) packages/modules using composer:
        printout "INFO" "Executing: > composer install --prefer-dist --no-suggest --no-interaction" "Output suppressed unless errors occur."
        cd ${TRAVIS_BUILD_DIR} &&
            chmod -R 777 ${TRAVIS_BUILD_DIR}/docroot/sites/default &&
            composer install --no-suggest --prefer-dist --no-interaction &> ${setup_logs}/composer.log &&
            composer drupal:scaffold &>> ${setup_logs}/composer.log &&
            printout "SUCCESS" "Composer has loaded Drupal core, contrib modules and third-party packages/libraries.\n"
        if [[ $? -ne 0 ]]; then
            echo -e "\n${RedBG}  ============================================================================== ${NC}"
            echo -e "\n${RedBG}  =               IMPORTANT: Composer packages not downloaded.                 = ${NC}"
            echo -e "\n${RedBG}  =                               Build aborted                                = ${NC}"
            echo -e "\n${RedBG}  ============================================================================== ${NC}"
            printout "ERROR" ".\n"
            printout "" "==> Composer log dump:"
            cat  ${setup_logs}/composer.log
            printout "" "=<= Dump ends."
            exit 1
        fi

        # Clone the private repo and merge files in it with the main repo.
        # The private repo settings are defined in <git.private_repo.xxxx> in .config.yml.
        # 'clone_private_repo' function is contained in cob_build_utilities.sh.
        clone_private_repo

        # Create/update settings, private settings and local settings files.
        # 'build_settings' function is contained in cob_build_utilities.sh.
        build_settings

        text=$(displayTime $(($(date +%s)-timer)))
        printout "SUCCESS" "Build Candidate created." "Process took${text}\n"

    fi

    # RUN THIS BLOCK ONLY FOR ==PULL-REQUESTS==, BUT NOT HOTFIXES.
    # => If the commit message contains the text "hotfix" then this step (which takes time and does not produce
    #    anything which will later be deployed) will be skipped.
    #
    # This block verifies the Build Artifact (i.e. the code in the PR) will actually build, and then runs QC style
    # tests of the code for linting and formatting standards.
    #
    # ============================================================================================================
    # NOTE: When modifying this block, take care that no files that will later be deployed are created or altered.
    # ============================================================================================================

    if [[ "${TRAVIS_EVENT_TYPE}" == "pull_request" ]] && [[ ${isHotfix} -eq 0 ]]; then

        timer=$(date +%s)

        # Load the cob_utitlities script which has some config procedures.
        . "${TRAVIS_BUILD_DIR}/hooks/common/cob_utilities.sh"

        printout "" "========================================================================================="
        printout "INFO" "Verifying & testing the Build Candidate."
        printout "" "=========================================================================================\n"

        printout "" "==== Installing Drupal ===========\n"

        # Install Drupal.
        # Strategies are defined in <build.local.database.source> in .config.yml and can be 'initialize' or 'sync'.
        if [[ "${build_local_database_source}" == "initialize" ]]; then

            printout "INFO" "INITIALIZE Mode: Will install Drupal using 'drush site-install' and then import repo configs."

            # Define the site-install command.
            SITE_INSTALL=" site-install ${project_profile_name} \
              --db-url=mysql://drupal:drupal@localhost/drupal \
              --site-name=${lando_name} \
              --site-mail=${drupal_account_mail} \
              --account-name=${drupal_account_name} \
              --account-pass=${drupal_account_password} \
              --account-mail=${drupal_account_mail} \
              --sites-subdir=${drupal_multisite_name} \
              -vvv \
              -y"

            # Now run the site-install command.
            ${drush_cmd} ${SITE_INSTALL} &> ${setup_logs}/site_install.log

            # If site-install command failed then alert.
            if [[ $? -eq 0 ]]; then
                printout "SUCCESS" "Site is freshly installed with clean database.\n"
            else
                printout "ERROR" "Fail - Site install failure"
                echo -e "\n${RedBG}  ============================================================================== ${NC}"
                echo -e "\n${RedBG}  =             IMPORTANT: Drupal build phase did not complete.                = ${NC}"
                echo -e "\n${RedBG}  =                      Build verification aborted.                           = ${NC}"
                echo -e "\n${RedBG}  ============================================================================== ${NC}"
                printout "ERROR" ".\n"
                printout "" "==> Site Install log dump:"
                cat  ${setup_logs}/site_install.log
                printout "" "=<= Dump ends."
                exit 1
            fi

            # Each Drupal site has a unique site UUID.
            # If we have exported configs from an existing site, and try to import them into a new (or different) site, then
            # Drupal recognizes this and prevents the entire import.
            # Since the configs saved in the repo are from a different site than the one we have just created, the UUID in
            # the configs wont match the UUID in the database.  To continue, we need to update the UUID of the new site to
            # be the same as that in the </config/default/system.site.yml> file.

            if [[ -s ${LANDO_MOUNT}/config/default/system.site.yml ]]; then
                # Fetch site UUID from the configs in the (newly made) database.
                db_uuid=$(${drush_cmd} cget "system.site" uuid | grep -Eo "\s[0-9a-h\-]*")
                # Fetch the site UUID from the configuration file.
                yml_uuid=$(cat ${LANDO_MOUNT}/config/default/system.site.yml | grep "uuid:" | grep -Eo "\s[0-9a-h\-]*")

                if [[ "${db_uuid}" != "${yml_uuid}" ]]; then
                    # The config UUID is different to the UUID in the database, so we will change the databases UUID to
                    # match the config files UUID and all should be good.
                    ${drush_cmd} cset "system.site" yml_uuid -y
                fi
            fi

        elif [[ "${build_local_database_source}" == "sync" ]]; then

            # Grab a copy of the database from the desired(remote) Acquia environent.
            printout "INFO" "SYNC Mode: Will copy remote DB and then import repo configs."

            # Ensure a remote source is defined, default to the develop environment on Acquia.
            if [[ -z ${build_local_database_drush_alias} ]]; then build_local_database_drush_alias="@bostond8.dev"; fi

            printout "INFO" "Copying database (and content) from ${build_local_database_drush_alias} into Travis build."

            # To be sure we eliminate all existing data we first drop the local DB, and then download a backup from the
            # remote server, and restore into the database container.
            ${drush_cmd} sql:drop --database=default -y &> ${setup_logs}/drush_site_install.log &&
                ${drush_cmd} sql:sync ${build_local_database_drush_alias} @self -y &>> ${setup_logs}/drush_site_install.log

            # See how we faired.
            if [[ $? -eq 0 ]]; then
                printout "SUCCESS" "Site has database and content from remote environment.\n"
            else
                echo -e "\n${RedBG}  ============================================================================== ${NC}"
                echo -e "\n${RedBG}  =             IMPORTANT: Drupal build phase did not complete.                = ${NC}"
                echo -e "\n${RedBG}  =                      Build verification aborted.                           = ${NC}"
                echo -e "\n${RedBG}  ============================================================================== ${NC}"
                printout "ERROR" ".\n"
                printout "" "==> Site Install log dump:"
                cat  ${setup_logs}/site_install.log
                printout "" "=<= Dump ends."
                exit 1
            fi
        fi

            # Import configurations from the project repo into the database.
            # Note: Configuration will be imported from folder defined in build.local.config.sync
            if [[ "${build_local_config_dosync}" != "false" ]]; then

            printout "INFO" "Import configuration from sync folder: '${project_sync}' into database"

            if [[ -s ${project_sync}/system.site.yml ]]; then
                # Fetch site UUID from the configs in the (newly made) database.
                printout "INFO" "Updates site UUID."
                db_uuid=$(${drush_cmd} cget "system.site" uuid | grep -Eo "\s[0-9a-h\-]*")
                # Fetch the site UUID from the configuration file.
                yml_uuid=$(cat ${project_sync}/system.site.yml | grep "uuid:" | grep -Eo "\s[0-9a-h\-]*")
                if [[ "${db_uuid}" != "${yml_uuid}" ]]; then
                    # The config UUID is different to the UUID in the database, so we will change the databases UUID to
                    # match the config files UUID and all should be good.
                    ${drush_cmd} cset "system.site" uuid yml_uuid -y
                fi
            fi

            ${drush_cmd} @self config-import sync -y -vvv &> ${setup_logs}/config_import.log

            if [[ $? -eq 0 ]]; then
                printout "SUCCESS" "Config from the repo has been applied to the database.\n"
            else
                # If we have sync'd a remote database, some of the configs we want to import may not be able to be applied.
                # The work aound is to try a partial configuration import.
                printf "\n"
                printout "WARNING" "==== Config Import Errors ========================="
                printout "" "          Config import log dump (last 25 rows):"
                tail -25 ${setup_logs}/config_import.log
                printout "" "          Dump ends."
                printout "WARNING" "Will retry a partial config import."

                ${drush_cmd} en config -y &>/dev/null
                ${drush_cmd} config-import --partial -y &> ${setup_logs}/config_import.log

                if [[ $? -eq 0 ]]; then
                    printout "SUCCESS" "Config from the repo has been applied to the database.\n"
                else
                    printout "WARNING" "==== Config Import Errors (2nd attempt) ==========="
                    printout "WARNING" "Will retry a partial config import one final time."

                    ${drush_cmd} config-import --partial -y &> ${setup_logs}/config_import.log

                    if [[ $? -eq 0 ]]; then
                        printout "SUCCESS" "Config from the repo has been applied to the database.\n"
                    else
                        # Uh oh!
                        printf "\n"
                        printout "ERROR" "==== Config Import Errors (3rd attempt) ==========="
                        printout "" "          Config import log dump (last 25 rows):"
                        tail -50 ${setup_logs}/config_import.log
                        printout "" "          Dump ends."
                        echo -e "\n${RedBG}  ============================================================================== ${NC}"
                        echo -e   "${RedBG} |              IMPORTANT:The configuration import failed.                      |${NC}"
                        echo -e   "${RedBG} |                      Build verification aborted.                             |${NC}"
                        echo -e   "${RedBG}  ============================================================================== ${NC}\n"
                        exit 1
                    fi
                fi
            fi
        fi

        # Enable and disable modules specific to developers.
        # Function 'devModules' & 'prodModules' are contained in <hooks/common/cob_utilities.sh>
        if [[ "${build_local_type}" != "none" ]]; then
            if [[ "${build_local_type}" == "dev" ]]; then
                printout "INFO" "Enable/disable appropriate development features and functionality."
                devModules "@self"
                printout "SUCCESS" "Development environment set.\n"
            elif [[ "${build_local_type}" == "prod" ]]; then
                printout "INFO" "Enable/disable appropriate production features and functionality."
                prodModules "@self"
                printout "SUCCESS" "Production environment set.\n"
            fi
        fi

        # Run finalization / housekeeping tasks.
        # Apply any pending database updates.
        printout "INFO" "Apply pending database updates etc."
        ${drush_cmd} updb -y
        printout "SUCCESS" "Done.\n"

        # Update Travis console log.
        text=$(displayTime $(($(date +%s)-timer)))
        printout "SUCCESS" "Build Candidate tested." "Process took${text}\n"

    fi

