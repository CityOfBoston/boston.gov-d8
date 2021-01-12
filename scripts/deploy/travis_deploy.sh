#!/usr/bin/env bash

#############################################################################################
#  This script will copy the Release Candidate in the Travis environment into the associated
#  Acquia project repository.
#
#  This script should be executed in the `deploy` section of .travis.yml.
#  Variables/settings are read from `deploy` node of the scripts/.config.yml file.
#
#  It requires two arguments. Example call:
#   `scripts/deploy/travis-deploy.sh master`
#
#  Travis envars defined here:
#    https://docs.travis-ci.com/user/environment-variables/#default-environment-variables
#
#############################################################################################

    # The branch to watch.
    source_branch=${1}
    DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )

    # Include the utilities file/libraries.
    # This causes the .lando.yml and .config.yml files to be read in and stored as variables.
    REPO_ROOT="${TRAVIS_BUILD_DIR}"
    . "${TRAVIS_BUILD_DIR}/scripts/cob_build_utilities.sh"
    . "${TRAVIS_BUILD_DIR}/scripts/deploy/cob_utilities.sh"
    TRAVIS_BRANCH_SANITIZED=${TRAVIS_BRANCH/-/}
    TRAVIS_BRANCH_SANITIZED=${TRAVIS_BRANCH_SANITIZED/ /}

    # Define branch-specific variables.
    src="deploy_${TRAVIS_BRANCH_SANITIZED}_dir" && deploy_dir="${!src}"
    src="deploy_${TRAVIS_BRANCH_SANITIZED}_acquia_repo" && deploy_remote="${!src}"
    src="deploy_${TRAVIS_BRANCH_SANITIZED}_deploy_branch" && deploy_branch="${!src}"
    src="deploy_${TRAVIS_BRANCH_SANITIZED}_excludes_file" && deploy_excludes_file="${!src}"
    src="deploy_${TRAVIS_BRANCH_SANITIZED}_includes_file" && deploy_includes_file="${!src}"
    src="deploy_${TRAVIS_BRANCH_SANITIZED}_from_file" && deploy_from_file="${!src}"
    src="deploy_${TRAVIS_BRANCH_SANITIZED}_travis_drush_path" && travis_drush="${!src}"
    src="deploy_${TRAVIS_BRANCH_SANITIZED}_drush_alias" && drush_alias="${!src}"
    src="deploy_${TRAVIS_BRANCH_SANITIZED}_copy_db" && deploy_copy_db="${!src}"
    src="deploy_${TRAVIS_BRANCH_SANITIZED}_drush_db_source" && drush_db_source="${!src}"
    src="deploy_${TRAVIS_BRANCH_SANITIZED}_dry_run" && deploy_dry_run="${!src}"
    isHotfix=0
    if echo ${TRAVIS_COMMIT_MESSAGE} | grep -iqF "hotfix"; then isHotfix=1; fi
    drush_cmd="${TRAVIS_BUILD_DIR}/vendor/bin/drush  -r ${TRAVIS_BUILD_DIR}/docroot"

    printout "SCRIPT" "starts <$(basename $BASH_SOURCE)>"

    # Manage SSH keys for deployment to Acquia.
    openssl aes-256-cbc -K $ACQUIA_KEY -iv $ACQUIA_VECTOR -in $TRAVIS_BUILD_DIR/scripts/deploy/acquia.enc -out $TRAVIS_BUILD_DIR/scripts/deploy/acquia_deploy -d
    chmod 600 $TRAVIS_BUILD_DIR/scripts/deploy/acquia_deploy
    eval "$(ssh-agent -s)"
    ssh-add $TRAVIS_BUILD_DIR/scripts/deploy/acquia_deploy

    # Note that the canonical repository is watched. Commits to forked repositories
    # will not trigger deployment unless DEPLOY_PR is true.
    if [[ "${TRAVIS_PULL_REQUEST}" == "false" ]] || [[ "${DEPLOY_PR}" == "true" ]]; then

        printout "INFO" " -- Deployments will be triggered on the '${source_branch}' branch (or on any tag)."
        printout "INFO" " |  Current branch is '${TRAVIS_BRANCH}'"
        printout "INFO" " |  Travis artifact id is '${TRAVIS_BUILD_ID}'"
        printout "INFO" " |  Checking config for '${TRAVIS_BRANCH_SANITIZED}'"

        # Trigger deployment if $source_branch parameters matches or this is a tag.
        if [[ "${TRAVIS_BRANCH}" == "${source_branch}" ]] || [[ -n ${TRAVIS_TAG} ]]; then

            printout "INFO" "The Release Candidate is accepted.\n"

            printf "${Blue}       ================================================================================${NC}\n"
            printout "STEP" "Construct Deploy Artifact"
            printf "${Blue}       ================================================================================${NC}\n"
            printout "INFO" "We use the 'Release Candidate' in <${TRAVIS_BUILD_DIR}> to construct a 'Deploy Artifact' into ${deploy_dir}.\n"

            if [[ "${deploy_dry_run}" != "false" ]]; then
                printout "WARNING" " *** DRY RUN - This is a test construction of Deploy Artifact - it will NOT be deployed."
            fi

            printout "ACTION" "Creating the deploy directory (${deploy_dir})"
            rm -rf ${deploy_dir} &&  mkdir -p ${deploy_dir}

            printout "ACTION" "Setting permissions on Drupal settings files."
            chmod -R 777 ${TRAVIS_BUILD_DIR}/docroot/sites/default/settings
            if [[ ! -d ${deploy_dir}/docroot/sites/default/settings ]]; then
              mkdir ${deploy_dir}/docroot/sites/default/settings
            fi
            chmod -R 777 ${deploy_dir}/docroot/sites/default/settings

            printout "ACTION" "Initializing a new git repo in deploy directory, and adding remote (to Acquia repo)."
            remote_name=$(echo "${deploy_remote}" | openssl md5 | cut -d' ' -f 2)
            cd ${deploy_dir} &&
                git init &&
                git config gc.pruneExpire 3.days.ago &&
                git remote add ${remote_name} ${deploy_remote}

            printout "ACTION" "Creating and checking-out the <${deploy_branch}> branch in new repo."
            cd ${deploy_dir} &&
                git checkout -b ${deploy_branch}

            printout "ACTION" "Fetching & merging files from remote (Acquia) repo."
            cd ${deploy_dir} &&
                git fetch ${remote_name} &> /dev/null &&
                git merge ${remote_name}/${deploy_branch} &> /dev/null &&
                rm -f .git/gc.log &&
                git prune &> /dev/null

            printout "SUCCESS" "Initialized the Deploy Artifact repo in <${deploy_dir}>.\n"

            # Move files from the Deploy Candidate into the Acquia Repo.
            printout "INFO" "Deployment to Acquia involves taking the Release Candidate (which was created previously) and"
            printout "INFO" "committing selected files into a branch in the Acquia Repo. "
            printout "INFO" "Selecting and copying files from the Release Candidate creats a Deploy Artifact which can be committed/pushed "
            printout "INFO" "to an Acquia Repo.\n"
            printout "ACTION" "Copying Drupal files from Release Candidate to create a Deploy Artifact."
            # Initially, use rsync to copy everything except webapp files.
            # Files/folders to be copied are specified in the files-from file.
            # Excluding those files/folders in the exclude-from file,
            #  - but always including those in the include-from file.
            # first, we need to make sure the webapps folder is excluded.
            tmp_excludes_file=${deploy_dir}/docroot/sites/default/settings/exclude.txt
            cd ${TRAVIS_BUILD_DIR} &&
              cp ${deploy_from_file} ${tmp_excludes_file} &&
              printf "\n${webapps_local_source}/ \n" >> ${tmp_excludes_file}
            # Now copy.
            cd ${TRAVIS_BUILD_DIR} &&
              rsync \
                  -rlDW \
                  --files-from=${deploy_from_file} \
                  --exclude-from=${tmp_excludes_file} \
                  --include-from=${deploy_includes_file} \
                  . ${deploy_dir}/
            # Finally, use rsync to copy the webapp folders which can then have their own inclusion/exclusion rules.
            printout "ACTION" "Copying across webapp js/css files."
            cd ${webapps_local_source} &&
              rsync \
                  -rlDW \
                  --delete-excluded \
                  --files-from=${webapps_rsync_from_file} \
                  --exclude-from=${webapps_rsync_excludes_file} \
                  --include-from=${webapps_rsync_includes_file} \
                  . ${deploy_dir}/${webapps_local_source}
            rm -f ${tmp_excludes_file}

            # Removes any gitignore files in contrib or custom modules.
            printout "ACTION" "Removing un-needed git config files."
            find ${TRAVIS_BUILD_DIR}/docroot/modules/. -type f -name ".gitignore" -delete -print &> /dev/null

            # After moving, ensure the Acquia hooks are/remain executable (b/c they are bash scripts).
            printout "ACTION" "Setting execute permissions on Acquia Hook files."
            chmod -R +x ${deploy_dir}/hooks/

            printout "SUCCESS" "All files copied and the Deploy Artifact is now fully constructed and ready.\n"

            if [[ "${deploy_dry_run}" == "false" ]]; then

                printout "INFO" "As far as this script is concerned, deploying the Deploy Artifact is acheived by pushing it"
                printout "INFO" "to the <${deploy_branch}> branch of the remote Acquia-hosted repo."
                printout "INFO" "Acquia's git server monitors commits to branches and uses 'webhooks' to launch scripts in the"
                printout "INFO" "/hooks folders. Those scripts help customize/complete the deployment onto the actual environments.\n"
                printout "ACTION" "Committing code in deploy_dir to local branch."
                deploy_commitMsg="Deploying '${TRAVIS_COMMIT}' (${TRAVIS_BRANCH}) from github to "
                cd ${deploy_dir} &&
                    git add --all &&
                    git commit -m "${deploy_commitMsg}" --quiet &&
                    printout "SUCCESS" "Code committed to local git branch.\n"

                printout "INFO" "The Deploy Candidate (in <${TRAVIS_BRANCH}> branch) is now ready to deploy to Acquia as <${deploy_branch}>."
                printout "ACTION" "Pushing local branch to Acquia repo."
                cd ${deploy_dir} &&
                    git push ${remote_name} ${deploy_branch} &&
                    printout "SUCCESS" "Branch pushed to Acquia repo.\n"
                printout "NOTE" "Acquia monitors branches attached to environments on its servers.  If this branch (${deploy_branch}) is attached to an"
                printf "       environment, then Acquia pipeline and hooks (scripts) will be automatically initiated shortly and will finish the\n"
                printf "       deployment to the Acquia environment.\n"

                if [[ "${git_public_repo_push}" == "true" ]]; then
                    printout "INFO" "The deployment hand-off to Acquia is complete."
                    printout "INFO" "The Release Candidate will now be sanitized and copied across to the public repo."
                    printout "ACTION" "Select files and copy files into distribution folders at."

                    dist_dir="${git_public_repo_dir}"
                    dist_remote="${git_public_repo_repo}"
                    dist_branch="${}"
                    printout "ACTION" "Creating the distribution directory (${dist_dir})"
                    rm -rf ${dist_dir} &&  mkdir -p ${dist_dir}

                    printout "ACTION" "Select Release Candidate files and copy to the distribution directory."
                    cd ${TRAVIS_BUILD_DIR} &&
                        rsync \
                            -rlDW \
                            --files-from=${public_repo_deploy_from_file} \
                            --exclude-from=${public_repo_deploy_includes_file} \
                            --include-from=${public_repo_deploy_excludes_file} \
                            . ${dist_dir}/
                    printout "SUCCESS" "Content to stnc/distribute to public repo is now fully constructed."

                    printout "ACTION" "Initialize new git repo in distribution directory."
                    dist_name=$(echo "${dist_remote}" | openssl md5 | cut -d' ' -f 2)
                    cd ${dist_dir} &&
                        git init &&
                        git config gc.pruneExpire 3.days.ago &&
                        git remote add ${dist_name} ${dist_remote}

                    printout "ACTION" "Create and checkout the branch ${dist_branch} in new repo."
                    cd ${dist_dir} &&
                        git checkout -b "${dist_branch}"

                    printout "ACTION" "Fetch & merge files from existing public repo."
                    cd ${dist_dir} &&
                        git fetch ${dist_name} &&
                        git merge ${dist_name}/${dist_branch} &&
                        rm -f .git/gc.log &&
                        git prune

                    printout "ACTION" "Push the branch with updated files to the public repo."
                    dist_commitMsg="Pushed files from '${TRAVIS_COMMIT}' (${TRAVIS_BRANCH})."
                    cd ${dist_dir} &&
                        git add --all &&
                        git commit -m "${dist_commitMsg}" --quiet &&
                        git push ${dist_name} ${dist_branch}

                    printout "SUCCESS" "Pushed ${dist_branch} to ${dist_name}\n"

                    printout "SUCCESS" "Prepared the new branch in <${dist_dir}>.\n"
                fi

            else

                printout "INFO" "This script is in dry-run mode.  Nothing is pushed to the Acquia repo.\n"
                printf "==================================================================================\n"
                printout "OUTPUT" "Release Candidate file listing:"
                printf "----------------------------------------------------------------------------------\n"
                printout "INFO" "Repository Root:"
                ls -la ${TRAVIS_BUILD_DIR}
                printout "INFO" "Docroot:"
                ls -la ${TRAVIS_BUILD_DIR}/docroot
                printout "INFO" "Settings:"
                ls -la ${TRAVIS_BUILD_DIR}/docroot/sites/default/settings
                printf "==================================================================================\n\n"

                printf "==================================================================================\n"
                printout "OUTPUT" "Deploy Artifact file listing:"
                printf "----------------------------------------------------------------------------------\n"
                printout "INFO" "Repository Root:"
                ls -la ${deploy_dir}
                printout "INFO" "Docroot:"
                ls -la ${deploy_dir}/docroot
                printout "INFO" "Settings:"
                ls -la ${deploy_dir}/docroot/sites/default/settings
                printf "==================================================================================\n"

            fi

          else
            printout "INFO" "This Deploy Artifact is not tracked and will NOT be deployed."
        fi

      else
        printout "INFO" "Release candidates are not deployed for Pull Requests."
    fi

  printout "SCRIPT" "ends <$(basename $BASH_SOURCE)>"
