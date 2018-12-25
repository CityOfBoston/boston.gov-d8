#!/bin/sh
#
# Cloud Hook: post-code-update
#
# The post-code-update hook runs in response to code commits.
# When you push commits to a Git branch, the post-code-update hooks runs for
# each environment that is currently running that branch.. See
# ../README.md for details.
#
# Usage: post-code-update site target-env source-branch deployed-tag repo-url
#                         repo-type

site="$1"
target_env="$2"
source_branch="$3"
deployed_tag="$4"
repo_url="$5"
repo_type="$6"
drush_alias="@bostond8.dev"
target_docroot="/var/www/html/bostond8.dev/docroot"

if [ "$target_env" = 'dev' ]; then
    echo "$site.$target_env: The $source_branch branch has been updated on $target_env. Running post-update tasks."

    echo "Remove config_split module."
    cd ${target_docroot} && drush9 $drush_alias pmu config_split -y
    cd ${target_docroot} && drush9 $drush_alias cdel core.extension module.config_split -y

    echo "Import configurations from $source_branch."
    cd ${target_docroot} && drush9 $drush_alias cim -y

    #    DEV ONLY - enable migration modules.
    echo "DEVELOPMENT PRE-D8 LAUNCH ONLY: Add back in some dev modules."
    cd ${target_docroot} && drush9 $drush_alias en migrate -y

    echo "Update $site.$target_env DB with un-executed hook_updates on deploy target."
    cd ${target_docroot} && drush9 $drush_alias updb -y

    echo "Update existing entities on $site.$target_env (poss. redundant)."
    cd ${target_docroot} && drush9 $drush_alias entup -y

    echo "Rebuild user content access permissions on $site.$target_env."
    cd ${target_docroot} && drush9 $drush_alias php-eval 'node_access_rebuild()' -y

    echo "Run cron now on $site.$target_env (poss. redundant)."
    cd ${target_docroot} && drush9 $drush_alias cron

else
    echo "$site.$target_env: The $source_branch branch has been updated on $target_env."
fi
