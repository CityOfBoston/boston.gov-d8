#!/bin/bash
#################################################
# Script release_candidate.sh
# Author: David Upton <david.upton@boston.gov>
#
# Runs on a Github actions runner machine.
# Copies selected files from the drupal docroot to
#   a release folder..
#################################################
  pwd=$(pwd);
  # Include our utility functions.
  source "${pwd}/utility.sh"

  root=${1}
  candidate_path=${2}

  printout "ACTION" "Creating the deploy directory (${candidate_path})"
  rm -rf ${candidate_path} &&  mkdir -p ${candidate_path}

  printout "ACTION" "Setting permissions on Drupal settings files."
  chmod -R 777 ${root}/docroot/sites/default/settings
  if [[ ! -d ${candidate_path}/docroot/sites/default/settings ]]; then
    mkdir -p ${candidate_path}/docroot/sites/default/settings
  fi
  chmod -R 777 ${candidate_path}/docroot/sites/default/settings &&
    printout "SUCCESS" "Deploy folders prepared."

  # Move files from the Deploy Candidate into the Acquia Repo.
  printout "INFO" "Deployment to Acquia involves taking the Release Candidate (which was created previously) and committing selected files into a branch in the Acquia Repo."
  printout "INFO" "Selecting and copying files from the Release Candidate creates a Deploy Artifact which can be committed/pushed to an Acquia Repo.\n"
  printout "ACTION" "Copying Drupal files from Release Candidate to create a Deploy Artifact."

  # Cleanup some files manually, if they exist in the GitHub repo, they will be copied across in the rsync.
  rm -f ${candidate_path}/.hotfix
  rm -rf ${candidate_path}/simplesaml

  # Initially, use rsync to copy everything except webapp files.
  # Files/folders to be copied are specified in the files-from file.
  # Excluding those files/folders in the exclude-from file, and deleting files from the
  # repo which aren't in the delpoy candidate.
  rsync \
      -rlDW \
      --delete \
      --files-from=deploy-from.txt \
      --exclude-from=deploy-excludes.txt \
      ${root}/ ${candidate_path}/

  # Force composer.json - or else drush get broken.
  # TODO: figure out anchoring in rsync include file to make sure the  composer.json file gets copied.
  cp ${root}/composer.json ${candidate_path}/composer.json

  # Adds gitignore to ensure git repos in modules are not accidentially merged
  rm -f ${candidate_path}/.gitignore
  printf "docroot/modules/**/.git\ndocroot/libraries/**/.git\n" > ${candidate_path}/.gitignore

  # After moving, ensure the Acquia hooks are/remain executable (b/c they are bash scripts).
  printout "ACTION" "Setting execute permissions on Acquia Hook files."
  chmod -R +x ${candidate_path}/hooks/

  printout "SUCCESS" "All files copied and the Deploy Artifact is now fully constructed and ready.\n"
