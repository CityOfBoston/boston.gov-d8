#!/bin/bash
  ###############################################################
  #  These commands need to be run as root/admin user from lando.yml.
  #
  #  Essentially these commands are installing packages we require
  #  in the local docker database (MySQL) container.
  ###############################################################

  # Include the utilities file/library.
  . "${LANDO_MOUNT}/scripts/local/lando_utilities.sh"

  # Read in config and variables.
  eval $(parse_yaml "${LANDO_MOUNT}/scripts/local/.config.yml" "")
  eval $(parse_yaml "${LANDO_MOUNT}/.lando.yml" "lando_")

  # Create script variables
  target_env="local"

  printout "INFO" "Install MySQl tools mycli and pspg into the database container."

  # Install python and pip so we can install mycli.
  # This is OPTIONAL - not all users require an extended cli on the actual container, opting instead to
  # use tolls such as PHPStorm, MySQL Workbench or SQL Pro on the host machine.
  apt-get update &&
    apt-get install python-pip python-setuptools -qq > /dev/null &&
    pip install mycli > /dev/null

  # Install pspg pager to work with less.
  # This is OPTIONAL - this is an extension to the mycli package, which is also optional.
  apt-get install wget ca-certificates -qq > /dev/null &&
    wget --quiet -O - https://www.postgresql.org/media/keys/ACCC4CF8.asc | apt-key add - &&
    sh -c 'echo "deb http://apt.postgresql.org/pub/repos/apt/ stretch-pgdg main" >> /etc/apt/sources.list.d/pgdg.list' &&
    apt-get update &&
    apt-get install pspg -qq > /dev/null

  printout "INFO" "Installed mycli and pspg."