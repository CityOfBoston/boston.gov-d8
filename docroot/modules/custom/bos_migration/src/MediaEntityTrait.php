<?php


namespace Drupal\bos_migration;


use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Logic relevant to filesystem reorganization.
 */
trait MediaEntityTrait {

  /**
   * Creates or updates a media entity referencing the fid of a file entity.
   *
   * The media entity and file entity have the same id's (mid == fid).
   *
   * @param string $targetBundle
   *   Media type: icon|media|document
   * @param int $fid
   *   The (im most cases already existing) file entity id.
   * @param string $filename
   *   Filename for media entity.
   * @param int|NULL $author
   *   The uid of the author.  Will default to uid = 1.
   * @param bool $library
   *   Should this be added to the mwdia library.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createMediaEntity(string $targetBundle, int $fid, string $filename, int $author = NULL, bool $library = FALSE) {
    $status = 1;
    if (empty($author)) {
      if (NULL != ($file = File::load($fid))) {
        $author = $file->get("uid")->target_id;
        // If we find a file, then reset the media name to match the filename.
        $filename = $file->get("status")->value;
        // If we find a file, then reset the media status to match the files'.
        $status = $file->get("status")->value;
      }
    }
    if (empty($author)) {
      // If we still dont have an author, then set to user1.
      $author = 1;
    }

    // Image has 3 bundles: image|icon|document, but actually only 2
    // file-types image|document.  Map icon bundles into the file-type image.
    $field_name = (in_array($targetBundle, ['icon', 'image']) ? 'image' : 'field_document');

    // Create the Media entity if needed.
    $dirty = FALSE;
    if (NULL == ($media = Media::load($fid))) {
      $media = Media::create([
        'mid' => $fid,
        'bundle' => $targetBundle,
        'uid' => $author,
        'status' => $status,
        $field_name => [
          'target_id' => $fid,
        ],
      ]);
      $dirty = TRUE;
    }

    // If media item exists, and filename is different then change.
    // Clean up the filename first though.
    if ($media->name->value != $filename) {
      $media->name = $this->cleanFilename($filename);
      $dirty = TRUE;
    }
    // If media item exists, and media_library setting is different then change.
    if ($media->field_media_in_library->value != $library) {
      $media->field_media_in_library = $library;
      $dirty = TRUE;
    }
    // Create alt text if none exists.
    if ($targetBundle == "image" && empty($media->image[0]->alt)) {
      $media->image[0]->alt = "Image for " . $filename;
      $dirty = TRUE;
    }

    if ($dirty) {
      $media->setNewRevision(FALSE);
      $media->save();
    }

    return $media;

  }

  /**
   * Update the alt text for the media item.
   *
   * @param \Drupal\media\MediaInterface $media_entity
   * @param \DOMElement $image_node
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateImageMediaAlt(MediaInterface $media_entity, \DOMElement $image_node, MigrateExecutableInterface $migrate_executable) {
    $alt_text = $image_node->getAttribute('alt');
    if (empty($alt_text)) {
      // Nothing to do.
      return;
    }
    $current_alt_text = $media_entity->image[0]->alt;
    if (!empty($current_alt_text)) {
      if ($alt_text == $current_alt_text) {
        // The same, no log entry.
        return;
      }
      // Something different already added, skip, but log.
      $message = sprintf('Skipping image media (id=%d) alt text update(new_alt="%s"), alt already set (old_alt="%s").', $media_entity->id(), $alt_text, $current_alt_text);
      $migrate_executable->saveMessage($message, MigrationInterface::MESSAGE_WARNING);
      return;
    }
    // Update alt text for the related media entity image field.
    $media_entity->image[0]->alt = $alt_text;
    $media_entity->setNewRevision(FALSE);
    $media_entity->save();
  }

}
