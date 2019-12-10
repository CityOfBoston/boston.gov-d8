#!/bin/bash

    site=drupal
    source_branch="current branch"
    target_env=local

    . "/app/hooks/common/cob_utilities.sh"

    printf "[info] Will pull latest code from public repo and merge in settings from private repo.\n"
    # Pull develop branch from git.
    cd /app/docroot
    git fetch --all
    git pull --all

    # clone the private repo, and copy files into main repo folder
    cd /app
    git clone git@github.com:CityOfBoston/boston.gov-d8-private.git /app/tmprepo
    cd tmprepo
    rm -rf /app/tmprepo/.git
    find . -iname '*..gitignore' -exec rename 's/\.\.gitignore/\.gitignore/' '{}' \;
    rsync -aE /app/tmprepo/ /app/ --exclude=*.md
    cd /app
    rm -rf /app/tmprepo

    printf "[success] Public and private repos updated.\n"
