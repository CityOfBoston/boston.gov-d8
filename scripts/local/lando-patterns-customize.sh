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
    printout "SCRIPT" "starts <$(basename $BASH_SOURCE) >\n"
    if [[ "${patterns_local_build}" != "true" ]] && [[ "${patterns_local_build}" != "True" ]] && [[ "${patterns_local_build}" != "TRUE" ]]; then
        printout "INFO" "Patterns library will not be deployed."
        exit 0
    fi

    # During the appserver build, it created a clean folder into which the repo can be cloned.
    # Clone the patterns repo into a folder within the Main boston.gov d8 repo.
    if [[ "$(ls -A ${patterns_local_repo_local_dir})" ]]; then
      # So the folder was not completely removed.
      printout "ACTION" "Removing Patterns repo (it will be re-cloned later)."
      # Try to remove the folder.  This may be difficult.
      rm -rf ${patterns_local_repo_local_dir}
      # If the folder still exists, try to rename it
      if [[ -d ${patterns_local_repo_local_dir} ]]; then
        if [[ -d ${patterns_local_repo_local_dir}_old ]]; then rm -rf ${patterns_local_repo_local_dir}_old; fi
        mv -f ${patterns_local_repo_local_dir} ${patterns_local_repo_local_dir}_old
      fi
      # So now try to make the folder again (empty).  If this fails then we know we have not created an new empty folder.
      mkdir ${patterns_local_repo_local_dir} || printout "WARNING" "Patterns repo still could not be removed.\n"
    fi

    # If all is good, then clone the repo.
    clone_patterns_repo

    printout "SCRIPT" "ends <$(basename $BASH_SOURCE)>"
