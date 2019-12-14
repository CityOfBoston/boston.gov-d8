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

  if [[ -z "${git_private_repo_local_dir}" ]]; then git_private_repo_local_dir="${LANDO_MOUNT}/tmprepo"; fi

  # Empty the folder if it exists.
  if [[ -e "${git_private_repo_local_dir}" ]]; then rm -rf ${git_private_repo_local_dir}; fi

  # Clone the repo and merge
  printout "INFO" "private repo: ${git_private_repo_repo} - branch: ${git_private_repo_branch} - into ${git_private_repo_local_dir}.\n"
git clone -b ${git_private_repo_branch} git@github.com:${git_private_repo_repo} ${git_private_repo_local_dir} -q --depth 1
if [[ $? -eq 0 ]]; then printout "SUCCESS" "Cloned"; fi
rm -rf ${git_private_repo_local_dir}/.git
if [[ $? -eq 0 ]]; then printout "SUCCESS" "Removed git"; fi
find ${git_private_repo_local_dir}/. -iname '*..gitignore' -exec rename 's/\.\.gitignore/\.gitignore/' '{}' \;
if [[ $? -eq 0 ]]; then printout "SUCCESS" "Renamed gitignores"; fi
rsync -aE "${git_private_repo_local_dir}/" "${LANDO_MOUNT}/" --exclude=*.md
if [[ $? -eq 0 ]]; then printout "SUCCESS" "Merged"; fi
rm -rf ${git_private_repo_local_dir}
if [[ $? -eq 0 ]]; then printout "SUCCESS" "Deleted remains"; fi
printout "SUCCESS" "Private repo merged.\n"