#!/bin/bash
  ###############################################################
  #  These commands are installing packages we require
  #  in the Travis build-server (PHP container).
  ###############################################################

    REPO_ROOT=${TRAVIS_BUILD_DIR}
    . "${TRAVIS_BUILD_DIR}/scripts/cob_build_utilities.sh"

    printf "\n"
    printout "SCRIPT" "starts <$(basename $BASH_SOURCE)>"
    printf "${Blue}       =========================================================================================\n"
    printout "STEP" "Customize Travis Build container(s)."
    printf "${Blue}       =========================================================================================\n\n"
    php --version
    printout "INFO" "Travis deploys its own php container (see php version above)."
    printout "INFO" "For the COB build, we need to add some Linux packages to this standard build."
    printout "ACTION" "Installing required Linux packages in Travis container."

    # Installs linux apps and extensions into the appserver container.
    printout "WARN" "Temporarily, we are fixing the composer version to 1.x."
    composer self-update 1.10.13 &&
      printout "SUCCESS" "Composer is set to v1.10.13."
    sudo apt-get install -y --no-install-recommends libgd-dev openssh-client fontconfig openssl

    setup_logs="${TRAVIS_BUILD_DIR}/setup"

    # Prepare the folder which will hold setup logs.
    if [[ -e  ${setup_logs} ]]; then rm -rf ${setup_logs}/; fi
    mkdir -p ${setup_logs} &&
        chmod 777 ${setup_logs};

    printout "SUCCESS" "Travis container is prepared with custom modules.\n"
    printout "SCRIPT" "ends <$(basename $BASH_SOURCE)>"
