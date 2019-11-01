<?php

namespace Drupal\bos_migration\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\node\Plugin\migrate\source\d7\NodeRevision;

/**
 * Drupal 7 node revision source from database.
 *
 * @MigrateSource(
 *   id = "d7_node_revision_ext",
 *   source_module = "node"
 * )
 */
class NodeRevisionExt extends NodeRevision {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Want to apply a sort, and add in WorkBench Moderation information.
    $query = parent::query();
    $query->orderBy('nid', 'ASC');
    $query->orderBy('vid', 'ASC');
    $query->orderBy('stamp', 'ASC');
    $query->leftjoin('workbench_moderation_node_history', "wb", "n.nid = wb.nid AND nr.vid = wb.vid");
    $query->addField("wb", "state", "wb_state");
    $query->addField("wb", "uid", "wb_uid");
    $or = $query->orConditionGroup()
      ->condition("wb.state", "draft")
      ->condition("wb.state", "published")
      ->condition("wb.state", NULL, "is");
    $query->condition($or);

    // Make sure the current vid is included in the $row.
    $tables = &$query->getTables();
    $tables["n"]["condition"] = "n.nid = nr.nid";

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if (parent::prepareRow($row)) {
      // Only return the row if it is published (it's in workbench["all"]) or
      // if it is the current record.
      // Changes to the NodeRevisionExt now make this largely redundant
      // but its one use is to restrict the number of node revisions to migrate
      // in any migration.
      $this_vid = $row->getSourceProperty("vid");
      if (isset($this_vid) && (
        isset($row->workbench["all"][$this_vid])
        || $row->workbench["current"]->vid == $this_vid)
      ) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
