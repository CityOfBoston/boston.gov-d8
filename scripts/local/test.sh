#!/bin/bash


  LANDO_MOUNT=/home/david/sources/boston.gov-d8
  . "${LANDO_MOUNT}/scripts/cob_build_utilities.sh"
  echo "${LANDO_MOUNT}/scripts/local/.config.yml"
  eval $(parse_yaml "${LANDO_MOUNT}/scripts/local/.config.yml" "")
  eval $(parse_yaml "${LANDO_MOUNT}/.lando.yml" "lando_")
TRAVIS_BRANCH="replace-phi_ng"
TRAVIS_BRANCH=${TRAVIS_BRANCH/-/}
TRAVIS_BRANCH=${TRAVIS_BRANCH/_/}
    # Define branch-specific variables.
    src="deploy_${TRAVIS_BRANCH}_dir" && echo ${src} && deploy_dir="$(echo ${!src})"
    printf "$deploy_dir\n${TRAVIS_BRANCH}"

#  ( set -o posix ; set )
#  printout "STEP" "This is step" "here"
#  printout "SUCCESS" "This is success" "here"
#  printout "ERROR" "This is error" "here"
#  printout "WARNING" "This is warning" "here"
#  printout "INFO" "This is info" "here"
#  printout "UNK" "This is unknown" "here"

