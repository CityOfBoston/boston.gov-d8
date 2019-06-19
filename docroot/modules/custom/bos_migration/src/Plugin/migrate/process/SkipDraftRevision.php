<?php

namespace Drupal\bos_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Core\Database\Database;

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
 * @endcode
 */
class SkipDraftRevision extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // In the transform() method we perform whatever operations our process
    // plugin is going to do in order to transform the $value provided into its
    // desired form, and then return that value.

    // If the moderation state in Drupal 7 is not Published, then don't migrate.
    // Be mindful that the latest revision coud be draft, so keep that.

    try {
      $connection = Database::getConnection("default", "migrate");
      $query = $connection->select("workbench_moderation_node_history", "history")
        ->fields('history', ["from_state", "state", "published", "is_current"]);
      $query->condition("vid", $value);
      $workbench = $query->execute()->fetchAssoc();

      if ($workbench['state'] != "published" && !$workbench['is_current']) {

        throw new MigrateSkipRowException("Skipped Draft (" . $value . ")", TRUE);
      }
    }
    catch (Error $e) {
      // Some other error.
      throw new MigrateException($e->getMessage(), $e->getCode(), $e->getPrevious(), MigrationInterface::MESSAGE_ERROR, MigrateIdMapInterface::STATUS_NEEDS_UPDATE);
    }
    return;

  }

}
