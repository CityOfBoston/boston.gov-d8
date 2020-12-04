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

    # Include the utilities file/libraries.
    # This causes the .lando.yml and .config.yml files to be read in and stored as variables.
    REPO_ROOT="${LANDO_MOUNT}"
    . "${LANDO_MOUNT}/scripts/cob_build_utilities.sh"
    . "${LANDO_MOUNT}/scripts/deploy/cob_utilities.sh"
    target_env="local"

    printout "SCRIPT" "starts <$(basename $BASH_SOURCE)>"
    printf "\n"
    if [[ "${patterns_local_build}" != "true" ]] && [[ "${patterns_local_build}" != "True" ]] && [[ "${patterns_local_build}" != "TRUE" ]]; then
        printout "INFO" "Patterns library will not be deployed."
        exit 0
    fi
    if [[ ! -d ${patterns_local_repo_local_dir}/components ]]; then
        printout "ERROR" "Patterns library is not present."
        exit 1
    fi
    printf "${Blue}       ================================================================================${NC}\n"
    printout "STEP" "Building Patterns."
    printf "${Blue}       ================================================================================${NC}\n"
    printout "INFO" "Patterns source files are found in ${patterns_local_repo_local_dir}."
    printout "INFO" "Patterns webapp is built/installed in the node container."

    # Install patterns requisites.
    printout "ACTION" "Installing packages and node dependencies for patterns app."
    (cd ${patterns_local_repo_local_dir} &&
      echo "$ rm node_modules & .stencil & public & assets folders"  &> ${setup_logs}/patterns_build.log &&
      rm -rf "node_modules" &&
      rm -rf ".stencil" &&
      rm -rf "public" &&
      rm -rf "assets" &&
      echo "$ npm run preinstall"  &>> ${setup_logs}/patterns_build.log &&
      npm run preinstall  &>> ${setup_logs}/patterns_build.log &&
      sleep 5 &&
      printout "SUCCESS" "Patterns library npm packages etc installed.\n") || (printout "ERROR" "Dependencies NOT installed." && exit 1)

    printout "ACTION" "Installing gulp cli and checking configs."
    (cd ${patterns_local_repo_localawk_dir} &&
      echo "$ npm install --no-fund --no-optional"  &>> ${setup_logs}/patterns_build.log &&
      npm install --no-fund --no-optional  &>> ${setup_logs}/patterns_build.log &&
      echo "$ npm install -g gulp-cli@latest  --no-fund --no-optional" &>> ${setup_logs}/patterns_build.log &&
      npm install -g  --no-fund --no-optional gulp-cli@latest &>> ${setup_logs}/patterns_build.log &&
      printf "Gulp " && gulp -v | grep "CLI" | awk "{print $1}" &&
      printout "SUCCESS" "Gulp latest installed.\n") || (printout "ERROR" "Patterns library NOT installed." && exit 1)

    # Run an initial build to be sure everything is there.
    printout "ACTION" "Building Patterns library."
    (cd ${patterns_local_repo_local_dir} &&
      npm run build &>> ${setup_logs}/patterns_build.log &&
      printout "SUCCESS" "Patterns library built.\n") || (printout "ERROR" "Patterns library NOT built.\n" && exit 1)

    printout "SCRIPT" "ends <$(basename $BASH_SOURCE)>"
    printf "\n"