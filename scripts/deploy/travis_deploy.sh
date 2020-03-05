#!/usr/bin/env bash

#############################################################################################
#  This script will copy the build artifact in the Travis environment into the associated
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
    . "${TRAVIS_BUILD_DIR}/hooks/common/cob_utilities.sh"
    TRAVIS_BRANCH_SANITIZED=${TRAVIS_BRANCH/-/}
    TRAVIS_BRANCH_SANITIZED=${TRAVIS_BRANCH_SANITIZED/ /}

    # Define branch-specific variables.
    src="deploy_${TRAVIS_BRANCH_SANITIZED}_dir" && deploy_dir="${!src}"
    src="deploy_${TRAVIS_BRANCH_SANITIZED}_acquia_repo" && deploy_remote="${!src}"
    src="deploy_${TRAVIS_BRANCH_SANITIZED}_deploy_branch" && deploy_branch="${!src}"
    src="deploy_${TRAVIS_BRANCH_SANITIZED}_excludes_file" && deploy_excludes_file="${!src}"
    src="deploy_${TRAVIS_BRANCH_SANITIZED}_includes_file" && deploy_includes_file="${!src}"
    src="deploy_${TRAVIS_BRANCH_SANITIZED}_sanitize_file" && deploy_sanitize_file="${!src}"
    src="deploy_${TRAVIS_BRANCH_SANITIZED}_travis_drush_path" && travis_drush="${!src}"
    src="deploy_${TRAVIS_BRANCH_SANITIZED}_drush_alias" && drush_alias="${!src}"
    src="deploy_${TRAVIS_BRANCH_SANITIZED}_copy_db" && deploy_copy_db="${!src}"
    src="deploy_${TRAVIS_BRANCH_SANITIZED}_drush_db_source" && drush_db_source="${!src}"
    src="deploy_${TRAVIS_BRANCH_SANITIZED}_dry_run" && deploy_dry_run="${!src}"
    isHotfix=0
    if echo ${TRAVIS_COMMIT_MESSAGE} | grep -iqF "hotfix"; then isHotfix=1; fi
    drush_cmd="${TRAVIS_BUILD_DIR}/vendor/bin/drush  -r ${TRAVIS_BUILD_DIR}/docroot"

    printf "ref: $(basename "$0")\n"

    # Manage SSH keys for deployment to Acquia.
    openssl aes-256-cbc -K $ACQUIA_KEY -iv $ACQUIA_VECTOR -in $TRAVIS_BUILD_DIR/scripts/deploy/acquia.enc -out $TRAVIS_BUILD_DIR/scripts/deploy/acquia_deploy -d
    chmod 600 $TRAVIS_BUILD_DIR/scripts/deploy/acquia_deploy
    eval "$(ssh-agent -s)"
    ssh-add $TRAVIS_BUILD_DIR/scripts/deploy/acquia_deploy

    # Note that the canonical repository is watched. Commits to forked repositories
    # will not trigger deployment unless DEPLOY_PR is true.
    if [[ "${TRAVIS_PULL_REQUEST}" == "false" ]] || [[ "${DEPLOY_PR}" == "true" ]]; then

        printout "INFO" "Deployments will be triggered on the '${source_branch}' branch (or on any tag)."
        printf   "       - Current branch is '${TRAVIS_BRANCH}' \n       - Travis build artifact id is '${TRAVIS_BUILD_ID}'.\n"
        printf   "       - Checking config for '${TRAVIS_BRANCH_SANITIZED}' \n"

        # Trigger deployment if $source_branch parameters matches or this is a tag.
        if [[ "${TRAVIS_BRANCH}" == "${source_branch}" ]] || [[ -n ${TRAVIS_TAG} ]]; then

            printout "INFO" "The Build Candidate is accepted and is now the Build Artifact.\n"

            printout "STEP" "== Generate Deploy Candidate ======"
            printf "Use the 'Deploy Artifact' in <${TRAVIS_BUILD_DIR}> to generate the 'Deploy Candidate' into ${deploy_dir}.\n"

            if [[ "${deploy_dry_run}" != "false" ]]; then
                printout "" "\n       ============================================================================="
                printout "WARNING" "DRY RUN - This is a test build of Deploy Candidate - it will NOT be deployed."
                printout "" "       =============================================================================\n"
            fi

            printout "INFO" "Recreate the deploy directory (${deploy_dir})"
            rm -rf ${deploy_dir} &&  mkdir -p ${deploy_dir}

            printout "STEP" "Initialize new git repo in deploy directory."
            remote_name=$(echo "${deploy_remote}" | openssl md5 | cut -d' ' -f 2)
            cd ${deploy_dir} &&
                git init &&
                git config gc.pruneExpire 3.days.ago &&
                git remote add ${remote_name} ${deploy_remote}

            printout "INFO" "Create and checkout the branch ${deploy_branch} in new repo."
            cd ${deploy_dir} &&
                git checkout -b ${deploy_branch}

            printout "INFO" "Fetch & merge files from remote repo."
            cd ${deploy_dir} &&
                git fetch ${remote_name} &&
                git merge ${remote_name}/${deploy_branch} &&
                rm -f .git/gc.log &&
                git prune

            printout "SUCCESS" "Created the Deploy Candidate in <${deploy_dir}>.\n"

            printout "STEP" "Copy files from (GitHub) into <${deploy_dir}>"
            # Remove the various .gitignore files so we can use git to manage full set of the Deploy Candidate files.
            printout "INFO" "Refine Build Artifact (GitHub branch ${TRAVIS_BRANCH} built in ${TRAVIS_BUILD_DIR})."

            chmod -R 777 ${TRAVIS_BUILD_DIR}/docroot/sites/default/settings
            mkdir ${deploy_dir}/docroot/sites/default/settings
            chmod -R 777 ${deploy_dir}/docroot/sites/default/settings

            # Move files from the Deploy Candidate into the Acquia Repo.
            printout "INFO" "Select Build Artifact files and copy to create the Deploy Candidate."
            rsync \
                -rlDW \
                --inplace \
                --delete \
                --exclude-from=${deploy_excludes_file} \
                --files-from=${deploy_includes_file} \
                ${TRAVIS_BUILD_DIR}/ ${deploy_dir}/

            # Sanitize - remove files from file defined in deploy_sanitize_file.
            # deploy_sanitize_file is a line delimited list of wildcard files or folders.
            if [[ -s ${deploy_sanitize_file} ]]; then
                # xargs -d '\n' rm < "${deploy_sanitize_file}"
                for f in $(cat ${deploy_sanitize_file}) ; do
                    if [[ ${f:0:1} != "/" ]]; then
                        set f="${TRAVIS_BUILD_DIR}/${f}"
                    fi
                    rm -f "$f"
                    if [[ ${?} -eq 0 ]]; then
                        printf " [notice] sanitize: deleted <${f}>\n"
                    fi
                done
            fi
            # Removes any gitignore files in contrib or custom modules.
            find ${TRAVIS_BUILD_DIR}/docroot/modules/. -type f -name ".gitignore" -delete -print &> /dev/null

            # After moving, ensure the Acquia hooks are/remain executable (b/c they are bash scripts).
            chmod +x ${TRAVIS_BUILD_DIR}/hooks/**/*.sh

            printout "SUCCESS" "Deploy Candidate is now ready in the Acquia repo in <${deploy_dir}>.\n"

            if [[ "${deploy_dry_run}" == "false" ]]; then

                printout "INFO" "Branch ${TRAVIS_BRANCH} now ready to deploy to Acquia as ${deploy_branch}."
                deploy_commitMsg="Deploying '${TRAVIS_COMMIT}' (${TRAVIS_BRANCH}) from github to "
                cd ${deploy_dir} &&
                    git add --all &&
                    git commit -m "${deploy_commitMsg}" --quiet &&
                    git push ${remote_name} ${deploy_branch}

                printout "SUCCESS" "Deployed ${deploy_branch} to ${remote_name}\n"
                printout "NOTE" "Acquia pipeline and hooks will now run.\n"

            else

                printf "==================================================================================\n"
                printout "OUTPUT" "Build Artifact"
                printf "----------------------------------------------------------------------------------\n"
                printout "INFO" "Repository Root:"
                ls -la ${TRAVIS_BUILD_DIR}
                printout "INFO" "Docroot:"
                ls -la ${TRAVIS_BUILD_DIR}/docroot
                printout "INFO" "Settings:"
                ls -la ${TRAVIS_BUILD_DIR}/docroot/sites/default/settings
                printf "==================================================================================\n\n"

                printf "==================================================================================\n"
                printout "OUTPUT" "Deploy Candidate"
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
            printout "INFO" "This Build Artifact is not tracked and will NOT be deployed."
        fi

      else
        printout "INFO" "Build artifacts are not deployed for Pull Requests."
    fi
