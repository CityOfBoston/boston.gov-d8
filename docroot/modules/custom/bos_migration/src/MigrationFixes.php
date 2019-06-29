<?php

namespace Drupal\bos_migration;

use Drupal\Core\Database\Database;

/**
 * Class migrationFixes.
 *
 * Makes various migration fixes particular to COB.
 *
 * Idea is that public static functions are created that can be called by
 * drush commands at various points during migration.
 * Example:
 * lando ssh -c"/app/vendor/bin/drush php-eval ...
 * ...'\Drupal\bos_migration\migrationFixes::fixTaxonomyVocabulary();'"
 *
 * @package Drupal\bos_migration
 */
class MigrationFixes {

  /**
   * An array to map d7 view + displays to d8 equivalents.
   *
   * @var array
   */
  protected static $viewListMap = [
    'bos_department_listing' => [
      'listing' => ['departments_listing', 'page_1'],
    ],
    'bos_news_landing' => [
      'page' => ["news_landing", 'page_1'],
    ],
    'calendar' => [
      'feed_1' => ["calendar", "page_1"],
      'listing' => ["calendar", "page_1"],
    ],
    'metrolist_affordable_housing' => [
      'page' => ["metrolist_affordable_housing", "page_1"],
      'page_1' => ["metrolist_affordable_housing", "page_1"],
    ],
    'news_announcements' => [
      'departments' => ["news_and_announcements", "block_1"],
      'events' => ["news_and_announcements", "block_1"],
      'guides' => ["news_and_announcements", "block_1"],
      'most_recent' => ["news_and_announcements", "block_2"],
      'news_events' => ["news_and_announcements", "block_1"],
      'places' => ["news_and_announcements", "block_1"],
      'posts' => ["news_and_announcements", "block_1"],
      'programs' => ["news_and_announcements", "block_1"],
    ],
    'places' => [
      'listing' => ["places", "page_1"],
    ],
    'public_notice' => [
      'archive' => ["public_notice", "page_1"],
      'landing' => ["public_notice", "page_2"],
    ],
    'status_displays' => [
      'homepage_status' => ["homepage_status", "block_1"],
    ],
    'topic_landing_page' => [
      'page_1' => ["topic_landing_page", "page_1"],
    ],
    'transactions' => [
      'main_transactions' => ["transactions", "page_1"],
    ],
    'upcoming_events' => [
      'most_recent' => ["upcoming_events", "block_1"],
    ],
  ];

  /**
   * This updates the taxonomy_vocab migration map.
   *
   * Required so that taxonomy entries can later be run with --update flag set.
   */
  public static function fixTaxonomyVocabulary() {
    $d7_connection = Database::getConnection("default", "migrate");
    $query = $d7_connection->select("taxonomy_vocabulary", "v")
      ->fields("v", ["vid", "machine_name"]);
    $source = $query->execute()->fetchAllAssoc("vid");

    if (!empty($source)) {
      $d8_connection = Database::getConnection("default", "default");
      foreach ($source as $vid => $row) {
        $d8_connection->update("migrate_map_d7_taxonomy_vocabulary")
          ->fields([
            "destid1" => $row->machine_name,
            "source_row_status" => 0,
          ])
          ->condition("sourceid1", $vid)
          ->execute();
      }
      $d8_connection->truncate("migrate_message_d7_taxonomy_vocabulary")
        ->execute();
    }

    echo "Updated Drupal 8 taxonomy_vocab table.";

  }

  /**
   * This updates the paragraph__field_list table.
   *
   * Translates D7 view names and displays to the D8 equivalents.
   */
  public static function fixListViewField() {
    // Fetch all the list records into a single object.
    $d8_connection = Database::getConnection("default", "default");
    $query = $d8_connection->select("paragraph__field_list", "list")
      ->fields("list", ["field_list_target_id", "field_list_display_id"]);
    $row = $query->execute()->fetchAllKeyed("field_list_target_id");

    // Process each row, making substitutions from map array $viewListMap.
    foreach ($row as $view => $display) {
      $map = self::$viewListMap;
      if (isset($map[$view][$display])) {
        $d8_connection->update("paragraph__field_list")
          ->fields([
            "field_list_target_id" => $map[$view][$display][0],
            "field_list_display_id" => $map[$view][$display][1],
          ])
          ->condition("field_list_target_id", $view)
          ->condition("field_list_display_id", $display)
          ->execute();
      }
    }

  }

}
