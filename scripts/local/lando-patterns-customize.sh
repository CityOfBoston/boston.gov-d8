#!/bin/bash
  ###############################################################
  #  These commands need to be run as root/admin user from lando.yml.
  #
  #  Essentially these commands are installing packages we require
  #  in the local docker node container.
  ###############################################################

    # Include the utilities file/library.
    # This causes the .lando.yml and .config.yml files to be read in and stored as variables.
    REPO_ROOT="${LANDO_MOUNT}"
    . "${LANDO_MOUNT}/scripts/cob_build_utilities.sh"

    # Create script variables
    target_env="local"

    printf "\n"
    printf "[LANDO] starts <$(basename "$0")>\n"
    if [[ "${patterns_local_build}" != "true" ]] && [[ "${patterns_local_build}" != "True" ]] && [[ "${patterns_local_build}" != "TRUE" ]]; then
        printout "INFO" "Patterns library will not be deployed."
        exit 0
    fi
    printf "\n${LightPurple}       ================================================================================${NC}\n"
    printout "STEP" "Installing Linux packages in the patterns node container."
    printf "${LightPurple}       ================================================================================${NC}\n"

    # Copy the node.js executable file in the container to a location that can be seen on the host.
    # This way eslint can run from PHPStorm without needing to install node.js on the host.
    # In PHPStorm point the path for node in eslint settings dialog to /user/.node_js/node
    if [[ "${patterns_local_build}" != "true" ]] && [[ "${patterns_local_build}" != "True" ]] && [[ "${patterns_local_build}" != "TRUE" ]]; then
        printf "mkdir /user/.node_js\n"
        if [[ ! -d /user/.node_js ]]; then mkdir /user/.node_js; fi
        if [[ ! -e /user/.node_js/node ]]; then cp /usr/local/bin/node /user/.node_js/.; fi
        printout "INFO" "node.js executable is linked to /user/.node.js/ on the host PC (for IDE linting)"
    fi

    # Create a clean folder into which the repo can be cloned.
    if [[ -d ${patterns_local_repo_local_dir} ]]; then rm -rf ${patterns_local_repo_local_dir}; fi
    mkdir ${patterns_local_repo_local_dir}
    chown node:node ${patterns_local_repo_local_dir}
    # Clone the patterns repo into a folder within the Main boston.gov d8 repo.
    clone_patterns_repo
    printout "INFO" "Patterns source files can be editted at ${patterns_local_repo_local_dir}"

    printf "[LANDO] ends <$(basename "$0")>\n"
