<?php


namespace Drupal\bos_migration\Plugin\migrate\process;


use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Plugin\migrate\process\SkipOnEmpty;
use Drupal\migrate\Row;

/**
 * Extends core plugin and adds logging so we can work out why rows are skipped.
 *
 * @MigrateProcessPlugin(
 *   id = "skip_on_empty_ext"
 * )
 */
class skipOnEmptyExt extends SkipOnEmpty {
  public function process($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    try {
      return parent::process($value, $migrate_executable, $row, $destination_property);
    }
    catch (MigrateSkipProcessException $e) {
      $msg = "";
      \Drupal::logger('migration')
        ->info($msg);
    }
  }

}