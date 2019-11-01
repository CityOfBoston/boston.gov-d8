<?php

namespace Drupal\bos_migration\Plugin\migrate\source\d7;

use Drupal\node\Plugin\migrate\source\d7\Node;

/**
 * Drupal 7 node revision source from database.
 *
 * @MigrateSource(
 *   id = "d7_node_ext",
 *   source_module = "node"
 * )
 */
class NodeExt extends Node {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Want to apply a sort, and add in WorkBench Moderation information.
    $query = parent::query();
    $query->leftjoin('workbench_moderation_node_history', "wb", "n.nid = wb.nid AND nr.vid = wb.vid");
    $query->addField("wb", "state", "wb_state");
    $query->addField("wb", "uid", "wb_uid");
    $query->addField("wb", "published", "wb_published");
    $query->addField("wb", "is_current", "wb_current");
    $query->condition("wb.is_current", "1");
    return $query;
  }

}
