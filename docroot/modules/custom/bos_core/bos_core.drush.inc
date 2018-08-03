<?php
/**
 * @file
 * Drush command for Boston Core.
 */

/**
 * Implements hook_drush_command().
 */
function bos_core_drush_command(){
  return array(
    'bos-test' => array(
      'description' => 'Test drush',
      'aliases' => ['bt'],
      'drupal dependencies' => ['bos_core'],
    )
  );
}

/**
 * Drush command.
 */
function drush_bos_core_bos_test() {
  $type = drush_choice(['success','error'], dt('What kind of message you\'d like to print?'));
}
