<?php

namespace Drupal\bos_migration\Plugin\migrate\process;

use Drupal\bos_migration\MigrationPrepareRow;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Do not migrate (i.e. skip revisions with workbench status = draft).
 *
 * @MigrateProcessPlugin(
 *   id = "skip_draft_revision"
 * )
 *
 * To skip non-essential draft revisions:
 *
 * @code
 * field_text:
 *   plugin: skip_draft_revision
 *   source: text
 *   save_to_map: boolean
 *   message: text
 * @endcode
 *
 * Source will usually be the vid field.
 * Save_to_map - true if draft vids should still be saved in migrate_map table.
 * Message - if set and not empty/false will write messages to message table.
 *
 * Also, adds the moderation values from d7 to the $row - which can later be
 * used for conditional migration workench moderation processing/handling.
 */
class SkipDraftRevision extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // In the transform() method we perform whatever operations our process
    // plugin is going to do in order to transform the $value provided into its
    // desired form, and then return that value.
    //
    // If the moderation state in Drupal 7 is not Published, then don't migrate.
    // Be mindful that the latest revision coud be draft, so keep that.
    $vid = $row->getSource()["vid"];

    $workbench = MigrationPrepareRow::findWorkbench($vid);
    try {
      $result = MigrationPrepareRow::shouldProcessRow($row->getSource()["nid"], $vid, $workbench, $this->configuration);
      // Add the d7 workbench moderation data to the source so other plugins
      // can use it in later processing.
      $row->workbench = $workbench;
      return $value;
    }
    catch(MigrateSkipRowException $e) {
      throw new MigrateSkipRowException($e->getMessage(), $e->getSaveToMap());
    }
    catch(Error $e) {
      throw new MigrateException($e->getMessage(), $e->getCode(), $e->getPrevious(), MigrationInterface::MESSAGE_ERROR, MigrateIdMapInterface::STATUS_NEEDS_UPDATE);
    }

  }

}
