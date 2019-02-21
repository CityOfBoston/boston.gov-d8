<?php

namespace Drupal\bos_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Component\Utility\Html;
use Drupal\media\MediaInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\media\Entity\Media;

/**
 * Perform value transformations that fixes an invalid URI.
 *
 * @MigrateProcessPlugin(
 *   id = "rich_text_to_media_embed"
 * )
 */
class RichTextToMediaEmbed extends ProcessPluginBase {
  use HtmlParsingTrait;
  use \Drupal\bos_migration\FilesystemReorganizationTrait;

  protected static $migratedFileBaseUri = "public://";
  protected static $baseUrl = "www.boston.gov";
  protected static $editDomain = "edit.boston.gov";
  protected static $relativeUrl = "sites/default/files";

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!is_string($value)) {
      throw new MigrateException('RichTextToMediaEmbed process plugin only accepts string values');
    }

    return $this->convertToEntityEmbed($value, $migrate_executable);
  }

  /**
   * Converts files to Entity Embed.
   *
   * @param string $value
   *   The value.
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   The migrate executable.
   *
   * @returns string
   */
  public function convertToEntityEmbed($value, MigrateExecutableInterface $migrate_executable) {
    $document = $this->getDocument($value, $migrate_executable);
    $xpath = new \DOMXPath($document);

    // Images.
    foreach ($xpath->query("//img") as $image_node) {
      $src = $image_node->getAttribute('src');
      if ($this->isExternalFile($src)) {
        continue;
      }
      elseif ($this->resolveFileType($src) !== 'image') {
        // Fail loudly if image type is unknown. This entire operation is
        // brittle so we want to know when unexpected things happen.
        throw new MigrateException("Unsuported image type: {$src}");
      }
      else {
        if ($media_entity = $this->createMediaEntity($src, 'image')) {
          $this->updateImageMedia($media_entity, $image_node, $migrate_executable);
          // Build <drupal-entity> element.
          $drupal_entity_node = $document->createElement('drupal-entity');
          $drupal_entity_node->setAttribute('data-embed-button', 'media_entity_embed');
          $drupal_entity_node->setAttribute('data-entity-embed-display', 'bos_media_image');
          $drupal_entity_node->setAttribute('data-entity-type', 'media');
          $drupal_entity_node->setAttribute('data-entity-uuid', $media_entity->uuid());
          // Replace the image node with the created element.
          $image_node->parentNode->insertBefore($drupal_entity_node, $image_node);
          $image_node->parentNode->removeChild($image_node);
          continue;
        }
      }
    }

    // Now links to the local filesystem.
    foreach ($xpath->query('//a[contains(@href, "/sites/default/files")]') as $link_node) {
      $link_node = $image_node->getAttribute('src');
      if ($this->isExternalFile($src)) {
        // This shouldn't ever be the case based on our query, but better safe
        // than sorry.
        throw new MigrateException("Your query is broke son: {$src}");
      }
      elseif ($this->resolveFileType($src) !== 'file') {
        // Fail loudly if image type is unknown. This entire operation is
        // brittle so we want to know when unexpected things happen.
        throw new MigrateException("Why are we linking to an image: {$src}");
      }
      else {
        if ($media_entity = $this->createMediaEntity($src, 'document')) {
          // Alter <a> element.
          $link_node->setAttribute('data-entity-substitution', 'media');
          $link_node->setAttribute('data-entity-type', 'media');
          $link_node->setAttribute('data-entity-uuid', $media_entity->uuid());
          $link_node->setAttribute('href', "/media/{$media_entity->id()}");
        }
      }
    }

    return Html::serialize($document);
  }

  /**
   * Determines if file is external.
   *
   * @param string $src
   *   The image src.
   *
   * @return bool
   *   Yes or no.
   */
  protected function isExternalFile($src) {
    if (strpos($src, self::$baseUrl) === FALSE && strpos($src, self::$relativeUrl) === FALSE) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Creates a media entity.
   *
   * @param string $src
   *   The image src.
   * @param string $targetBundle
   *   The bundle of the media entity we want to create.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\media\Entity\Media|null
   *   The media entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createMediaEntity(string $src, string $targetBundle) {
    if (!in_array($targetBundle, ['image', 'document'])) {
      throw new MigrateException('Only image and document bundles are supported.');
    }

    $uri = $src;
    if (!$this->isRelativeUri($src)) {
      $uri = $this->getRelativeUrl($src);
      if ($uri === FALSE) {
        throw new MigrateException("Something has gone horribly wrong: {$src}");
      }
    }

    // Now we should have a relative URI, convert it to a stream wrapper.
    $uri = $this->convertToStreamWrapper($uri);
    // We are doing some reorganzing of the filesystem, so make sure that the
    // uri is converted to the new format if applicable.
    $uri = $this->rewriteUri($uri);
    $file = $this->getFile($uri);
    if (!$file) {
      throw new MigrateException("Failed to find file: {$uri}");
    }
    $field_name = $targetBundle == 'image' ? 'image' : 'field_document';

    // Create the Media entity.
    return Media::create([
      'bundle' => $targetBundle,
      'uid' => '1',
      'status' => '1',
      $field_name => [
        'target_id' => $file->id(),
      ],
      // Don't add these images to media library. The media library should be
      // curated by a human.
      'field_media_in_library' => FALSE,
    ])->save();
  }

  /**
   * Retrieves the relative part of an absolute URL.
   *
   * @param string $uri
   *   An uri to inspect.
   *
   * @return string|bool
   *   The request path part of the uri, or FALSE if not an searched absolute
   *   uri.
   */
  protected function getRelativeUrl(string $uri) {
    $from_main_domain = '@^http(s|)://(www.|)boston.gov[/]+(.*)@';
    if (!preg_match($from_main_domain, $uri, $matches)) {
      // Not a searched absolute uri.
      return FALSE;
    }

    return $matches[3];
  }

  /**
   * Determines if uri is relative.
   *
   * @param string $uri
   *   The Uri.
   *
   * @return bool
   *   TRUE or FALSE.
   */
  protected function isRelativeUri(string $uri) {
    if (!preg_match('@^/sites/default/files/.*@', $uri)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Converts relative URIs to stream wrapper format.
   *
   * @param string $uri
   *   The relative URI.
   *
   * @return string
   *   The converted URI.
   */
  protected function convertToStreamWrapper(string $uri) {
    $new_uri = str_replace('/sites/default/files/private', 'private://', $uri);
    if ($new_uri === $uri) {
      // The replacement wasn't found, so this must be a public file.
      $new_uri = str_replace('/sites/default/files', 'public://', $uri);
    }

    return $new_uri;
  }

  /**
   * Retrieves the corresponding file entity.
   *
   * Best effort search.
   * Assumes the uri have not changed from d7 to d8 during migration, which is
   * just an heuristic.
   *
   * @todo inject entityTypeManager?
   *
   * @param string $uri
   *   File uri.
   *
   * @return \Drupal\file\Entity\File|null
   *   File entity object.
   */
  public function getFile($uri) {
    $file_entities = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->loadByProperties(['uri' => $uri]);
    return !empty($file_entities) ? reset($file_entities) : NULL;
  }

  /**
   * Updates an image media entity with the passed node element.
   *
   * @param \Drupal\media\MediaInterface $media_entity
   *   The entity to update.
   * @param \DOMElement $image_node
   *   The element to use for the update.
   * @param \Drupal\migrate\MigrateExecutableInterface\MigrateExecutableInterface $migrate_executable
   *   Related migrate executable object, used to store any message if needed.
   */
  public function updateImageMedia(MediaInterface $media_entity, \DOMElement $image_node, MigrateExecutableInterface $migrate_executable) {
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
    $media_entity->save();
  }

  /**
   * Determine filetype.
   *
   * @param string $uri
   *   The URI.
   *
   * @return string
   *   File type.
   */
  public function resolveFileType($uri) {
    $parts = explode('/', $uri);
    $image_formats = [
      'jpg',
      'png',
      'jpeg',
      'svg',
      'gif',
      'tif',
    ];
    $file_formats = [
      'pdf',
      'xls',
      'xlsx',
      'docx',
      'doc',
      'pptx',
      'pptm',
      'ppt',
      'rtf',
      'ppt',
      'xlsm',
    ];
    if (in_array($parts[count($parts) - 1], $image_formats)) {
      return 'image';
    }
    elseif (in_array($parts[count($parts) - 1], $file_formats)) {
      return 'file';
    }

    // White list files based on file_managed table in D7.
    throw new MigrateException("Unrecognized file format: {$uri}");
  }

}
