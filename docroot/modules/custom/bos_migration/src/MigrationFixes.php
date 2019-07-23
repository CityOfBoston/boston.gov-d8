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
    'news_and_announcements' => [
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
    echo "View list conversion:\n";

    foreach (["paragraph__field_list", "paragraph_revision__field_list"] as $table) {

      $d8_connection = Database::getConnection("default", "default");
      $query = $d8_connection->select($table, "list")
        ->fields("list", ["field_list_target_id", "field_list_display_id"]);
      $query = $query->groupBy("field_list_target_id");
      $query = $query->groupBy("field_list_display_id");
      $row = $query->execute()->fetchAll();

      $count = count($row);
      echo "Will change $count references in $table.\n";

      // Process each row, making substitutions from map array $viewListMap.
      foreach ($row as $display) {
        $map = self::$viewListMap;
        if (isset($map[$display->field_list_target_id][$display->field_list_display_id])) {

          $entry = $map[$display->field_list_target_id][$display->field_list_display_id];
          echo sprintf("Change %s/%s to %s/%s", $display->field_list_target_id ?: "--", $display->field_list_display_id ?: "--", $entry[0], $entry[1]);

          $d8_connection->update($table)
            ->fields([
              "field_list_target_id" => $entry[0],
              "field_list_display_id" => $entry[1],
            ])
            ->condition("field_list_target_id", $display->field_list_target_id)
            ->condition("field_list_display_id", $display->field_list_display_id)
            ->execute();

          echo ": Done.\n";
        }
        else {
          echo sprintf("%s/%s", $display->field_list_target_id ?: "--", $display->field_list_display_id ?: "--");
          echo ": Not found\n";
        }
      }
      echo "----------------\n\n";
    }

  }

  /**
   * This makes sure the filename is set properly from the uri.
   */
  public static function fixFilenames() {
    Database::getConnection()
      ->query("
        UPDATE file_managed
        SET filename = SUBSTRING_INDEX(uri, '/', -1) 
        WHERE locate('.', filename) = 0 and fid > 0;
      ")
      ->execute();
  }

  /**
   * Updates the install config files based on the current DB entries.
   *
   * Sort of super CEX for config_update.
   */
  public static function updateModules() {
    _bos_core_global_update_configs();
  }

}
