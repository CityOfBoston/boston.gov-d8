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
    try {
      $vid = $row->getSource()["vid"];
      $connection = Database::getConnection("default", "migrate");
      $query = $connection->select("workbench_moderation_node_history", "history")
        ->fields('history', ["from_state", "state", "published", "is_current"]);
      $query->condition("vid", $vid);
      $query->orderBy("hid", "DESC");
      $workbench = $query->execute()->fetchAssoc();

      $map =((empty($this->configuration["save_to_map"]) || $this->configuration["save_to_map"] == "false") ? false : TRUE);

      if ($workbench['state'] != "published" && !$workbench['is_current']) {
        $msg = $this->configuration["message"] ?: NULL;
        if (!empty($msg)) {
          $msg = \Drupal::translation()->translate("@msg (nid:@nid / vid:@vid).", [
            "@msg" => $msg,
            "@nid" => $row->getSource()["nid"],
            "@vid" => $vid,
          ]);
        }
        throw new MigrateSkipRowException($msg, $map);
      }
    }
    catch (Error $e) {
      // Some other error.
      throw new MigrateException($e->getMessage(), $e->getCode(), $e->getPrevious(), MigrationInterface::MESSAGE_ERROR, MigrateIdMapInterface::STATUS_NEEDS_UPDATE);
    }
    $row->_workbench = $workbench;
    return $value;

  }
}
