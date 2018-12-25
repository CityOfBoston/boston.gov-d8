#!/bin/sh
#
# Cloud Hook: post-code-deploy
#
# The post-code-deploy hook is run whenever you use the Workflow page to 
# deploy new code to an environment, either via drag-drop or by selecting
# an existing branch or tag from the Code drop-down list. See 
# ../README.md for details.
#
# Usage: post-code-deploy site target-env source-branch deployed-tag repo-url 
#                         repo-type

site="$1"
target_env="$2"
source_branch="$3"
deployed_tag="$4"
repo_url="$5"
repo_type="$6"
drush_alias="@bostond8.test"
target_docroot="/var/www/html/bostond8.test/docroot"

if [ "$source_branch" != "$deployed_tag" ]; then
    echo "$site.$target_env: Deployed branch $source_branch as $deployed_tag.  Running post-deploy tasks."

    echo "Import configurations from $source_branch."
    cd $target_docroot && drush9 $drush_alias cim -y

    echo "Update $site.$target_env DB with un-executed hook_updates on deploy target."
    cd $target_docroot && drush9 $drush_alias updb -y

    echo "Update existing entities on $site.$target_env (poss. redundant)."
    cd $target_docroot && drush9 $drush_alias entup -y

    echo "Rebuild user content access permissions on $site.$target_env."
    cd $target_docroot && drush9 $drush_alias php-eval 'node_access_rebuild()' -y
else
    echo "$site.$target_env: Deployed $deployed_tag."
fi
