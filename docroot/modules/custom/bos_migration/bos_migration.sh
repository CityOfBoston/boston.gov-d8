#!/bin/bash
# Lowest level safe-dependencies.
drush sset "bos_migration.fileOps" "none"
drush sset "bos_migration.active" "1"
drush mim --tag="bos:initial:1" --force     #OK

# First pass.
drush mim --tag="bos:taxonomy:1" --force
drush mim --tag="bos:paragraph:1" --force
drush mim --group=bos_field_collection --force
drush mim --tag="bos:taxonomy:2" --force
drush mim --tag="bos:paragraph:2" --force # deps:tax/fc/para1
drush mim --tag="bos:paragraph:3" --force # deps:para2
drush mim --tag="bos:paragraph:4" --force # Components/sidebar-components
drush mim --tag="bos:node:1" --force
drush mim --tag="bos:paragraph:5" --force # deps:nodes
drush mim --tag="bos:node:2" --force

# Second pass.
drush mim --group=d7_taxonomy_term --update
drush mim --group=bos_paragraphs --update
drush mim --group=d7_nodes --update
# Finish off.
drush mim d7_menu_links,d7_menu,d7_block
drush mim d7_rdf_mapping

drush sdel "bos_migration.active"
drush sset "bos_migration.fileOps" "copy"
