#!/bin/bash

#  LANDO_MOUNT=/home/david/sources/boston.gov-d8
  . "${LANDO_MOUNT}/scripts/local/lando_utilities.sh"
  eval $(parse_yaml "${LANDO_MOUNT}/scripts/local/.config.yml" "")
  eval $(parse_yaml "${LANDO_MOUNT}/.lando.yml" "lando_")
#  ( set -o posix ; set )
#  printout "ERROR" " This is error" "here"
#  printout "WARNING" " This is warning" "here"
#  printout "INFO" " This is info" "here"
#  printout "UNK" " This is unknown" "here"
b="develop"
b="build_travis_${b}_database_source"
a="${!b}"
echo ${a}
