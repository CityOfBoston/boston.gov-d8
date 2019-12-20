#!/bin/bash
  ###############################################################
  #  These commands need to be run as root/admin user from lando.yml.
  #
  #  Essentially these commands are installing packages we require
  #  in the local docker database (MySQL) container.
  ###############################################################

  # Include the utilities file/library.
  REPO_ROOT="${LANDO_MOUNT}"
  . "${LANDO_MOUNT}/scripts/cob_build_utilities.sh"

  # Create script variables
  target_env="local"

  printout "INFO" "Install MySQl tools (mycli and pspg) into the database container."

  # Install python and pip so we can install mycli.
  # This is OPTIONAL - not all users require an extended cli on the actual container, opting instead to
  # use tolls such as PHPStorm, MySQL Workbench or SQL Pro on the hoexit
  # st machine.
  apt-get update &> /dev/null &&
    apt-get install -y apt-utils python-pip python-setuptools -qq &> /dev/null &&
    pip install mycli -y &> /dev/null

  # Install pspg pager to work with less.
  # This is OPTIONAL - this is an extension to the mycli package, which is also optional.
  apt-get install -y wget ca-certificates -qq &> /dev/null &&
    wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add - &&
    sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt/ stretch-pgdg main" >> /etc/apt/sources.list.d/pgdg.list' &&
    apt-get update &> /dev/null &&
    apt-get install -y pspg -qq &> /dev/null

  printout "SUCCESS" "Installed mycli and pspg.\n"