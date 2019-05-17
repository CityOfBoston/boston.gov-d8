#!/bin/bash
# Lowest level safe-dependencies.
drush mim d7_user_role,d7_user                      # OK
drush mim d7_url_alias,d7_path_redirect             # OK
drush mim d7_file                                   # OK
# First pass.
drush mim --group=d7_taxonomy_term --force          # OK - 30sec
drush mim --group=bos_paragraphs --force
drush mim --group=d7_nodes --force
drush mim --group=bos_field_collection --force
# Second pass.
drush mim --group=d7_taxonomy_term --update
drush mim --group=bos_paragraphs --update
drush mim --group=d7_nodes --update
# Finish off.
drush mim d7_menu_links,d7_menu,d7_block
drush mim d7_rdf_mapping