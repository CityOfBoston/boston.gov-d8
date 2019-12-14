#!/bin/bash

    printout "INFO" "Will pull latest code from public repo and merge in settings from private repo."

    # Include the utilities file/library.
    . "/app/scripts/local/lando_utilities.sh"

    # Pull develop branch from git.
    cd /app/docroot &&
        git fetch --all &&
        git pull --all

    # clone the private repo, and copy files into main repo folder
#    git clone git@github.com:CityOfBoston/boston.gov-d8-private.git /app/tmprepo
#    rm -rf /app/tmprepo/.git
#    find /app/tmprepo/. -iname '*..gitignore' -exec rename 's/\.\.gitignore/\.gitignore/' '{}' \;
#    rsync -aE /app/tmprepo/ /app/ --exclude=*.md
#    rm -rf /app/tmprepo

    # Clone the private repo and merge with the main repo.
    clone_private_repo

    printout "SUCCESS" "Public and private repos updated.\n"
