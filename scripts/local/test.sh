#!/bin/bash

#  LANDO_MOUNT=/home/david/sources/boston.gov-d8
  . "${LANDO_MOUNT}/scripts/local/lando-utilities.sh"
  eval $(parse_yaml "${LANDO_MOUNT}/scripts/local/.config.yml" "")
  eval $(parse_yaml "${LANDO_MOUNT}/.lando.yml" "lando_")
  ( set -o posix ; set )
#  printout "ERROR" " This is error" "here"
#  printout "WARNING" " This is warning" "here"
#  printout "INFO" " This is info" "here"
#  printout "UNK" " This is unknown" "here"