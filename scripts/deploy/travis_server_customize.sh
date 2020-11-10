#!/bin/bash
  ###############################################################
  #  These commands are installing packages we require
  #  in the Travis build-server (PHP container).
  ###############################################################

    REPO_ROOT=${TRAVIS_BUILD_DIR}
    . "${TRAVIS_BUILD_DIR}/scripts/cob_build_utilities.sh"

    printf "\n"
    printf "ref: $(basename "$0")\n"
    printout "INFO" "Installing Linux packages in Travis container."

    # Installs linux apps and extensions into the appserver container.
    composer self-update 1.10.13
    sudo apt-get install -y --no-install-recommends libgd-dev openssh-client fontconfig openssl

    setup_logs="${TRAVIS_BUILD_DIR}/setup"

    # Prepare the folder which will hold setup logs.
    if [[ -e  ${setup_logs} ]]; then rm -rf ${setup_logs}/; fi
    mkdir -p ${setup_logs} &&
        chmod 777 ${setup_logs};

    printout "SUCCESS" "Travis container is prepared with custom modules.\n"
