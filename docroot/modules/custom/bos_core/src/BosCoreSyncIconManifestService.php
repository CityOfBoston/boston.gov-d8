<?php

namespace Drupal\bos_core;

use Drupal\file\Entity\File;

/**
 * Class BosCoreSyncIconManifestService.
 *
 *    Reads a manifest file and creates media (icon) entries assoc with files.
 *    Drush command to switch is: drush import-icon-manifest (alias biim)
 *
 * @see modules/custom/bos_core/src/Commands/BosCoreCommands.php
 *
 * @package Drupal\bos_core
 */
class BosCoreSyncIconManifestService {

  /**
   * Read the manifest file and create media and file entities.
   *
   * @param int $mode
   *   Determine if we are using this during a migration.
   *
   * @return bool
   *   Whether the manifest file was sucessfully processed.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function import($mode = BosCoreMediaEntityHelpers::SYNC_NORMAL) {

    $moduleHandler = \Drupal::service('module_handler');
    $migration_enabled = FALSE;
    if ($mode == BosCoreMediaEntityHelpers::SYNC_IN_MIGRATION) {
      $migration_enabled = TRUE;
      if (!$moduleHandler->moduleExists('migrate')) {
        printf("[error] Migration module is not enabled !.\n");
        \Drupal::logger("drush")->error("Migration module is not enabled !");
        return FALSE;
      }
    }

    // Load the manifest file.
    try {
      $manifest = self::loadManifestFile();
      $manifest_file = \Drupal::config("bos_core.settings")
        ->get("icon.manifest");
    }
    catch (\Exception $e) {
      printf("[warning] %s\n", $e->getMessage());
      \Drupal::logger("drush")->warning($e->getMessage());
      return FALSE;
    }

    // Initialize the manifest cache.
    $manifest_cache = [];
    if (!$migration_enabled) {
      // See if we have a manifest cached.
      $manifest_cache = \Drupal::state()
        ->get("bos_core.icon_library.manifest", []);
    }

    if (!empty($manifest_cache)) {
      // If using cache, then work out which rows in manifest need processing.
      $manifest_cache = array_values($manifest_cache);
      $manifest = array_diff($manifest, $manifest_cache);
      if (empty(array_filter($manifest))) {
        printf("[notice] All icons in manifest are already in the media library !.\n");
        return TRUE;
      }
    }

    // Keep track of whats been done.
    $cnt = [
      "Total" => 0,
      "Imported" => 0,
      "Media" => 0,
      "Found" => 0,
      "mFound" => 0,
    ];

    // Create the migrate map and message tables if they are not already there.
    if ($migration_enabled) {
      BosCoreMediaEntityHelpers::createMigrateMap();
      BosCoreMediaEntityHelpers::createMigrateMessage();
    }

    // Find the max fid in the files table and the max vid from the media table.
    $last = ["fid" => 0, "vid" => 0];
    try {
      BosCoreMediaEntityHelpers::findLastIds($last["fid"], $last["vid"], $mode);
    }
    catch (\Exception $e) {
      printf("[error] %s\n", $e);
      \Drupal::logger("drush")->error($e);
      return FALSE;
    }

    // Process each row of the manifest file in turn.
    foreach ($manifest as $icon_uri) {
      if (!empty($icon_uri)) {
        self::processFileUri($icon_uri);
        $manifest_cache[] = $icon_uri;
      }
    }

    // Save this manifest for later use (e.g. migration).
    \Drupal::state()->set("bos_core.icon_library.manifest", $manifest_cache);

    printf("\nImports icons from %s\n", $manifest_file);
    printf("Manifest defines %d icon files.\n", $cnt["Total"]);
    printf("---------------- ---------- ------- ---------- --------------\n");
    printf("Group            Manifest   Files   Imported   Media Library \n");
    printf("---------------- ---------- ------- ---------- --------------\n");
    printf("Icon Library     %s         %s      %s         %s            \n", $cnt["Total"], $cnt["Found"], $cnt["Imported"], ($cnt["Media"] + $cnt["mFound"]));
    printf("---------------- ---------- ------- ---------- --------------\n");

    return TRUE;

  }

  /**
   * Create or Update File and Media entities.
   *
   * @param string $icon_uri
   *   The file URI.
   * @param array $last
   *   The max File Entity ID known.
   * @param array $cnt
   *   The max File Entity Revision ID known.
   * @param int $migration_enabled
   *   Is this running during a migration.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function processFileUri(string $icon_uri, array $last = [], array $cnt = [], $migration_enabled = BosCoreMediaEntityHelpers::SYNC_NORMAL) {
    $icon_uri = trim($icon_uri);
    if (empty($icon_uri)) {
      return;
    }
    $icon_filename = self::cleanFilenameFolder($icon_uri);

    empty($cnt) ?: $cnt["Total"]++;

    // Try to get (or create) the file from/in the file_managed table.
    $file = NULL;
    $file_ids = self::retrieveFileEntities($icon_uri, $icon_filename, $file, $last, $cnt, $migration_enabled);

    // We should now have files in file_managed for all $file_ids.
    // Try to find a media entity attached to each file_id.
    foreach ($file_ids as $key => $file_id) {
      $result = BosCoreMediaEntityHelpers::getMediaEntityFromFile($file_id, "icon");
      if (empty($result)) {
        // Need to create a matching media entity for this file entity.
        if (NULL == $file || $file->id() != $file_id) {
          $file = File::load($file_id);
        }

        $last["fid"] = 0;
        $last["vid"] = 0;
        if ($migration_enabled == BosCoreMediaEntityHelpers::SYNC_IN_MIGRATION) {
          // When migrating, try to use the same file entity id as the id for
          // any new media entity created.
          $last["fid"] = $file->id();
        }
        $media = BosCoreMediaEntityHelpers::createMediaEntity($file->id(), $file->getOwnerId(), $icon_filename, "icon", $last["fid"], $last["vid"]);
        empty($cnt) ?: $cnt["Media"]++;
        // Only put the first media item into the library.
        BosCoreMediaEntityHelpers::updateMediaLibrary($media->id(), ($key == 0));
      }
      else {
        // Already is a media item for this file.
        foreach ($result as $media_entity) {
          empty($cnt) ?: $cnt["mFound"]++;
          BosCoreMediaEntityHelpers::updateMediaLibrary($media_entity->mid, ($key == 0));
          if ($key == 0) {
            break;
          }
        }
      }
      if ($key == 0 && !$migration_enabled) {
        break;
      }
    }
  }

  /**
   * Makes a clean filename with its containing folder.
   *
   * @param string $path
   *   The filename and full path.
   *
   * @return string
   *   The filename and its containing folder.
   */
  private static function cleanFilenameFolder($path) {
    $filename = BosCoreMediaEntityHelpers::cleanFilename($path);
    $path_bits = explode("/", $path);
    $path_bits = array_reverse($path_bits);
    if (!empty($path_bits[1])) {
      $filename = BosCoreMediaEntityHelpers::cleanFilename($path_bits[1]) . " " . $filename;
    }
    return $filename;
  }

  /**
   * Find the manifest file and load into an array.
   *
   * @return array
   *   An array with each element containing a row of the manifest file.
   *
   * @throws \Exception
   */
  public static function loadManifestFile() {
    // Get the manifest file name as set in the settings form.
    $manifest_file = \Drupal::config("bos_core.settings")->get("icon.manifest");
    if (empty($manifest_file)) {
      $manifest_file = "https://assets.boston.gov/manifest/icons_manifest.txt";
      \Drupal::configFactory()->getEditable('bos_core.settings')->set('icon.manifest', $manifest_file)->save();
    }

    // Fetch the contents of the manifest file.
    $manifest = file_get_contents($manifest_file);
    if (empty($manifest)) {
      throw new \Exception("Icon manifest file not found!");
    }

    // Explode each line in the file into a row in an array and return.
    return explode("\n", $manifest);
  }

  /**
   * Retrieve all file entitie which have this uri.
   *
   * If there are no entities, then create a new one.
   *
   * @param string $icon_uri
   *   The URI for the icon file.
   * @param string $icon_filename
   *   The filename to assign this file.
   * @param object $file
   *   This will be populated if a new file is created.
   * @param array $last
   *   Array with the last known file ID and revsision.
   * @param array $cnt
   *   The process counter array.
   * @param int $migration_enabled
   *   Whether this is being called during a migration.
   *
   * @return array|null
   *   An array of file entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function retrieveFileEntities(string $icon_uri, string $icon_filename, &$file, array $last = [], array $cnt = [], int $migration_enabled = BosCoreMediaEntityHelpers::SYNC_NORMAL) {
    // Fetch all file entities which reference this uri.
    $file_ids = BosCoreMediaEntityHelpers::getFileEntities($icon_uri);

    $multiple = (!empty($file_ids) && count($file_ids) > 1);

    if (empty($file_ids)) {
      if (!isset($last["fid"])) {
        $last["fid"] = 0;
      }
      // The file does not exist in file_managed table, so create it.
      $file = BosCoreMediaEntityHelpers::createFileEntity($icon_uri, $last["fid"]);
      // Now add the file to the list of files.
      $file_ids[] = $file->id();
      empty($cnt) ?: $cnt["Imported"]++;
      if ($migration_enabled) {
        // Need to create a migration id_map for this new file entity.
        try {
          BosCoreMediaEntityHelpers::createSimpleMappingEntry($file->id(), $file->id(), "d7_file");
        }
        catch (\Exception $e) {
          \Drupal::logger("drush")->warning($e->getMessage());
          \Drupal::messenger()->addWarning($e->getMessage());
        }
      }
    }
    else {
      // Icon does already exist (at least once) in the file_managed table.
      $file_ids = array_values($file_ids);
      empty($cnt) ?: ($cnt["Found"] = $cnt["Found"] + count($file_ids));
      if ($migration_enabled) {
        // The file already exists - check and update filename if needed.
        if (!$multiple) {
          BosCoreMediaEntityHelpers::updateFilename($file_ids[0], $icon_filename);
        }
        else {
          foreach ($file_ids as $file_id) {
            BosCoreMediaEntityHelpers::updateFilename($file_id, $icon_filename);
          }
        }
      }
    }
    return $file_ids;
  }

}
