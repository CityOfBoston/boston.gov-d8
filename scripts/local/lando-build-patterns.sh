#!/bin/bash

###############################################################
#  These commands need to be run as normal user from lando.yml.
#
#  NOTE: THIS SCRIPT SHOULD BE RUN INSIDE THE CONTAINER.
#
#  These commands install the patterns repo, start the node service
#  and start gulp to monitor for changes to files.
#
#  PRE-REQUISITES:
#     - docker container for node is created and started, and
#     - .lando.yml and .config.yml files are correctly configured.
#
#  Basic workflow:

#   1. Clone the patterns repo
#   2. Install npm dependencies in package.json
###############################################################

    printf "ref: $(basename "$0")\n"

    # Include the utilities file/libraries.
    # This causes the .lando.yml and .config.yml files to be read in and stored as variables.
    REPO_ROOT="${LANDO_MOUNT}"
    . "${LANDO_MOUNT}/scripts/cob_build_utilities.sh"
    . "${LANDO_MOUNT}/hooks/common/cob_utilities.sh"

    printf "\n"
    printf "ref: $(basename "$0")\n"
    if [[ "${patterns_local_repo_local_dir}" != "true" ]] && [[ "${patterns_local_repo_local_dir}" != "True" ]] && [[ "${patterns_local_repo_local_dir}" != "TRUE" ]]; then
        printout "INFO" "Patterns library will not be deployed.."
        exit 0
    fi
    printf "\n${LightPurple}       ================================================================================${NC}\n"
    printout "STEP" "Building Patterns."
    printf "${LightPurple}       ================================================================================${NC}\n"

    # Clone the patterns repo into a folder within the Main boston.gov d8 repo.
    if [[ -n ${GITHUB_TOKEN} ]]; then
        # Will enforce a token which should be passed via and ENVAR.
        REPO_LOCATION="https://${GITHUB_USER}:${GITHUB_TOKEN}@github.com/"
    else
        # Will rely on the user have an SSL cert which is registered with the private repo.
        REPO_LOCATION="git@github.com:"
    fi
    printout "INFO" "Cloning ${patterns_local_repo_branch} branch of Patterns library."
    # Create a clean folder into which the repo can be cloned.
    if [[ ! -d ${patterns_local_repo_local_dir} ]]; then
        mkdir ${patterns_local_repo_local_dir}
        chown node:node ${patterns_local_repo_local_dir}
    fi
    git clone -b ${patterns_local_repo_branch} ${REPO_LOCATION}${patterns_local_repo_name} ${patterns_local_repo_local_dir} -q --depth 100
    if [[ $? != 0 ]]; then
        printout "ERROR" "Patterns library NOT cloned or installed."
        exit 1
    fi
    # Make the public folder that gulp and fractal will build into.
    if [[ ! -d ${patterns_local_repo_local_dir}/public ]]; then
        mkdir ${patterns_local_repo_local_dir}/public
        chown node:node ${patterns_local_repo_local_dir}/public
        chmod 755 ${patterns_local_repo_local_dir}/public
    fi
    printout "SUCCESS" "Patterns library cloned."

    # Install the patterns app.
    printout "INFO" "Building Patterns library."
    cd ${patterns_local_repo_local_dir} && npm install
    if [[ $? != 0 ]]; then
        printout "ERROR" "Patterns library NOT built or installed."
        exit 1
    fi
    printout "SUCCESS" "Patterns library built."
