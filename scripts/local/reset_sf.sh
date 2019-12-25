#!/bin/bash

lando drush entity:delete node --bundle=metrolist_affordable_housing
lando drush salesforce_mapping:purge-drupal metrolist_affordable_housing
lando drush salesforce_pull:reset metrolist_affordable_housing
lando drush cr