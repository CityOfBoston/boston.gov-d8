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
    . "${TRAVIS_BUILD_DIR}/scripts/deploy/cob_utilities.sh"

    printout "SCRIPT" "starts <$(basename $BASH_SOURCE)>"

    # Create additional working variables.
    timer=$(date +%s)
    yes=1
    target_env="local"
    setup_logs="${TRAVIS_BUILD_DIR}/setup"
    project_sync=$(realpath ${project_docroot}/${build_local_config_sync})

    # Select the correct branchname to use.  If this is a PR then use the branch which is being comitted, if this is
    # a PUSH, then use the branch being pushed to.
    if [[ "${TRAVIS_EVENT_TYPE}" == "pull_request" ]]; then
        branch="${TRAVIS_PULL_REQUEST_BRANCH}"
    elif [[ "${TRAVIS_EVENT_TYPE}" == "push" ]]; then
        branch="${TRAVIS_BRANCH}"
    fi
    TRAVIS_BRANCH_SANITIZED=${TRAVIS_BRANCH/-/}
    TRAVIS_BRANCH_SANITIZED=${TRAVIS_BRANCH_SANITIZED/ /}
    src="build_travis_${TRAVIS_BRANCH_SANITIZED}_type" && build_travis_type="${!src}"
    src="build_travis_${TRAVIS_BRANCH_SANITIZED}_suppress_output" && quiet="${!src}"
    src="build_travis_${TRAVIS_BRANCH_SANITIZED}_database_source" && build_travis_database_source="${!src}"
    src="build_travis_${TRAVIS_BRANCH_SANITIZED}_database_drush_alias" && build_travis_database_drush_alias="${!src}"
    src="build_travis_${TRAVIS_BRANCH_SANITIZED}_config_sync" && build_travis_config_dosync="${!src}"
    project_profile_name="bos_profile"

    printout "DEBUG" "commit message: ${TRAVIS_COMMIT_MESSAGE}"
    isHotfix=0
    if echo ${TRAVIS_COMMIT_MESSAGE} | grep -iqF "hotfix"; then isHotfix=1; fi
    drush_cmd="${TRAVIS_BUILD_DIR}/vendor/bin/drush -r ${TRAVIS_BUILD_DIR}/docroot"
    drupal_cmd="${TRAVIS_BUILD_DIR}/vendor/bin/drupal --root=${TRAVIS_BUILD_DIR}/docroot"

    # RUN THIS BLOCK FOR BOTH GITHUB ==PULL REQUESTS== AND ==MERGES== (PUSHES).
    # Because we always need to:
    #  - gather the files from the commits in the PR (this is already done by travis before this
    #    script executes), and
    #  - add in the drupal core files, required contributed modules and dependent vendor packages, and
    #  - merge in the files from the private repo.

    printout "INFO" "== This is a ${TRAVIS_EVENT_TYPE} =================================================="

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

        printf "\n"
        printf "${Blue}       =========================================================================================\n"
        printout "INFO" "Creating the Release Candidate."
        printf "${Blue}       =========================================================================================\n\n"

        if [[ ${isHotfix} -eq 1 ]]; then
            printout "NOTICE" "=== HOTFIX DETECTED ======================\n"
        fi

        ########################################################
        # STEP 1: DOWNLOADING PHP MODULES AND DEPENDENCIES
        ########################################################

        # Install PHP (and other ...) packages/modules using composer:
        printout "INFO" "Composer is used to download the core Drupal files, along with any dependencies sepcified for"
        printout "INFO" "the website to be built."
        printout "NOTICE" "Executing: > composer install --prefer-dist --no-suggest --no-interaction" "Output suppressed unless errors occur."
        printout "ACTION" "Downloading Drupal and dependencies to Travis container."
        cd ${TRAVIS_BUILD_DIR} &&
            chmod -R 777 ${TRAVIS_BUILD_DIR}/docroot/sites/default &&
            composer clear-cache &&
            composer config -g github-oauth.github.com "$GITHUB_TOKEN" &&
            composer install --no-suggest --prefer-dist --no-interaction -vvv &> ${setup_logs}/composer.log &&
            composer drupal:scaffold &>> ${setup_logs}/composer.log &&
            printout "SUCCESS" "Composer has loaded Drupal core, contrib modules and third-party packages/libraries.\n"
        if [[ $? -ne 0 ]]; then
            printf "\n${RedBG}  ============================================================================== ${NC}"
            printf "\n${RedBG}  =               IMPORTANT: Composer packages not downloaded.                 = ${NC}"
            printf "\n${RedBG}  =                               Build aborted.                               = ${NC}"
            printf "\n${RedBG}  ============================================================================== ${NC}\n"
            printout "ERROR" "Composer failed check output below."
            printout "" "==> Composer log dump:"
            cat  ${setup_logs}/composer.log
            printout "" "=<= Dump ends."
            exit 1
        fi

        ########################################################
        # STEP 2: CLONE PRIVATE REPO (FOR SECRETS)
        ########################################################

        # Clone the private repo and merge files in it with the main repo.
        # The private repo settings are defined in <git.private_repo.xxxx> in .config.yml.
        # 'clone_private_repo' function is contained in cob_build_utilities.sh.
        printout "INFO" "Some confidential settings are required for the website, and these are stored in a private repository."
        printout "INFO" "This private repo needs to be cloned, then merged with files from the current public repo (and all the"
        printout "INFO" "files just downloaded via Composer)."
        clone_private_repo

        printout "SUCCESS" "Release Candidate created." "Process took $(displayTime $(($(date +%s)-timer)))\n"

    fi

    # RUN THIS BLOCK ONLY FOR ==PULL-REQUESTS==, BUT NOT HOTFIXES.
    # => If the commit message contains the text "hotfix" then this step (which takes time and does not produce
    #    anything which will later be deployed) will be skipped.
    #
    # This block verifies the Release Artifact (i.e. the code in the PR) will actually build, and then runs QC style
    # tests of the code for linting and formatting standards.
    #
    # ============================================================================================================
    # NOTE: When modifying this block, take care that no files that will later be deployed are created or altered.
    # ============================================================================================================

    if [[ "${TRAVIS_EVENT_TYPE}" == "pull_request" ]] && [[ ${isHotfix} -eq 0 ]]; then

        timer=$(date +%s)

        # Load the cob_utitlities script which has some config procedures.
        . "${TRAVIS_BUILD_DIR}/scripts/deploy/cob_utilities.sh"

        printf "${Blue}       =========================================================================================\n"
        printout "INFO" "Verifying & testing the Release Candidate."
        printf "${Blue}       =========================================================================================\n\n"

        printout "INFO" "This step will verify the Candidate by checking coding standards, attempting "
        printout "INFO" "to build (install) drupal, then downloading the current content from the Acquia "
        printout "INFO" "dev site, and finally running whatever automated tests are specified. "
        . ${TRAVIS_BUILD_DIR}/scripts/local/validate.sh "all" "${TRAVIS_EVENT_TYPE}"
        if [[ ${?} -ne 0 ]]; then
            exit 1
        fi

        printout "" "==== Installing Drupal ===========\n"
        ########################################################
        # STEP 3: CONFIGURE ACQUIA DRUSH ALIASES (not required)
        ########################################################
        ########################################################
        # STEP 4: COPY SITE CONTENT (LOAD MYSQL DATABASE)
        ########################################################

        # Strategies are defined in <build.local.database.source> in .config.yml and can be 'initialize' or 'sync'.
        if [[ "${build_travis_database_source}" == "initialize" ]]; then

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
            printout "ACTION" "Installing Drupal"
            ${drush_cmd} ${SITE_INSTALL} &> ${setup_logs}/site_install.log

            # If site-install command failed then alert.
            if [[ $? -eq 0 ]]; then
                printout "SUCCESS" "Site is freshly installed with clean database.\n"
            else
                printout "ERROR" "Fail - Site install failure"
                printf "\n${RedBG}  ============================================================================== ${NC}"
                printf "\n${RedBG}  =             IMPORTANT: Drupal build phase did not complete.                = ${NC}"
                printf "\n${RedBG}  =                      Release verification aborted.                         = ${NC}"
                printf "\n${RedBG}  ============================================================================== ${NC}\n"
                printout "" "==> Site Install log dump:"
                cat  ${setup_logs}/site_install.log
                printout "" "=== Dump ends.\n"
                ls -la ${TRAVIS_BUILD_DIR}/docroot/sites/default
                ls -la ${TRAVIS_BUILD_DIR}/docroot/sites/default/settings
                exit 1
            fi

        elif [[ "${build_travis_database_source}" == "sync" ]]; then

            # Grab a copy of the database from the desired(remote) Acquia environent.
            printout "INFO" "SYNC Mode: Will copy remote DB and then import repo configs."

            # Ensure a remote source is defined, default to the develop environment on Acquia.
            if [[ -z ${build_travis_database_drush_alias} ]]; then build_travis_database_drush_alias="@bostond8.dev"; fi

            printout "INFO" "Copying database (and content) from ${build_travis_database_drush_alias} into Travis build."

            # To be sure we eliminate all existing data we first drop the local DB, and then download a backup from the
            # remote server, and restore into the database container.
            ${drush_cmd} sql:drop --database=default -y &> ${setup_logs}/drush_site_install.log &&
                ${drush_cmd} sql:sync ${build_travis_database_drush_alias} @self -y --skip-tables-key=common --structure-tables-key=common &>> ${setup_logs}/drush_site_install.log

            # See how we faired.
            if [[ $? -eq 0 ]]; then
                printout "SUCCESS" "Site has database and content from remote environment.\n"
            else
                printf "\n${RedBG}  ============================================================================== ${NC}"
                printf "\n${RedBG}  =             IMPORTANT: Drupal build phase did not complete.                = ${NC}"
                printf "\n${RedBG}  =                      Release verification aborted.                         = ${NC}"
                printf "\n${RedBG}  ============================================================================== ${NC}\n"
                printout "" "==> Site Install log dump:"
                cat  ${setup_logs}/site_install.log
                printout "" "=== Dump ends."
                exit 1
            fi
        fi

        ########################################################
        # step 5: IMPORTING CONFIGURATION
        ########################################################

        # Import configurations from the project repo into the database.
        # Note: Configuration will be imported from folder defined in build.local.config.sync
        if [[ "${build_travis_config_dosync}" != "false" ]]; then

          printout "INFO" "The database currently loaded needs to be updated with any changed configs that are contained in this branch."
          printout "INFO" "This step will import configuration from sync folder: '${project_sync}' into database"

          # Check the config file site UUID matches the entry in the database .
          verifySiteUUID "${project_sync}" "database"

          # Apply any pending database updates.
          printout "INFO" "New or updated modules may have updates to apply to the database schema.  Apply these now."
          printout "ACTION" "Apply pending database updates etc."
          ${drush_cmd} cache:rebuild &&
            ${drush_cmd} updatedb -y &&
            printout "SUCCESS" "Hook_updates executed.\n" || printout "ERROR" "Issues executing hook_updates.\n"

          printout "ACTION" "Importing configs into the Database."
          drush_cmd="${drush_cmd} -q "
          importConfigs "@self" # &> ${setup_logs}/config_import.log
          drush_cmd="${TRAVIS_BUILD_DIR}/vendor/bin/drush -r ${TRAVIS_BUILD_DIR}/docroot"

          if [[ $? -ne 0 ]]; then
            printf "\n"
            printout "ERROR" "==== Config Import Errors ==========="
            printout "" "          NOTE: Using config_split:"
            printout "" "          Config import log dump (last 150 rows):"
            tail -150 ${setup_logs}/config_import.log
            printout "" "          Dump ends."
            echo -e "\n${RedBG}  ============================================================================== ${NC}"
            echo -e   "${RedBG} |              IMPORTANT:The configuration import failed.                      |${NC}"
            echo -e   "${RedBG} |                      Release verification aborted.                           |${NC}"
            echo -e   "${RedBG}  ============================================================================== ${NC}\n"
            exit 1
          else
            printout "SUCCESS" "Configs imported into the Database."
          fi

        else
          printout "INFO" "The configuration files were not imported into the database as part of the build."
          printout "INFO" "To import configs, set build:travis:${TRAVIS_BRANCH_SANITIZED}:config:sync to 'true' in .config.yml"
        fi

        ########################################################
        # STEP 6: FINALIZE
        ########################################################

        # Run finalization / housekeeping tasks.

        # Cleanup un-needed settings files.
        settings_path="${project_docroot}/sites/${drupal_multisite_name}"
        rm -f "${settings_path}/default.settings.php"
        rm -f "${settings_path}/default.services.yml"
        rm -f "${project_docroot}/sites/example.settings.local.php"
        rm -f "${project_docroot}/sites/example.sites.php"

        # Update Travis console log.
        text=$(displayTime $(($(date +%s)-timer)))
        printout "SUCCESS" "Release Candidate tested." "Install & build process took${text}\n"

    fi

    printout "SCRIPT" "ends <$(basename $BASH_SOURCE)>"


# TRAVIS_BUILD_DIR="/app/travis/build/"
# TRAVIS_EVENT_TYPE="pull_request"
# TRAVIS_BRANCH="drupal_9"
# TRAVIS_PULL_REQUEST_BRANCH="drupal_9"
# TRAVIS_COMMIT_MESSAGE="build"
# mkdir TRAVIS_BUILD_DIR
# . /app/docroot/scripts/deploy/travis_build.sh
