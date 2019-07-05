<?php

namespace Drupal\bos_migration\Plugin\migrate\process;

/*
 * COB NOTE:
 * In this boston.gov implementation, this class/plugin is added by
 *   bos_migration->bos_migration_migration_plugins_alter()
 * which adds this plugin to the process of 'text_long', 'text_with_summary'
 * fields.
 */

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Component\Utility\Html;
use Drupal\media\MediaInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\media\Entity\Media;
use Drupal\Component\Serialization\Json;
use Exception;

/**
 * Replace local image and link tags with entity embeds.
 *
 * @MigrateProcessPlugin(
 *   id = "rich_text_to_media_embed"
 * )
 */
class RichTextToMediaEmbed extends ProcessPluginBase {
  use \Drupal\bos_migration\HtmlParsingTrait;
  use \Drupal\bos_migration\FilesystemReorganizationTrait;

  protected static $MediaWYSIWYGTokenREGEX = '/\[\[\{.*?"type":"media".*?\}\]\]/s';
  protected static $localReferenceREGEX = '((http(s)?://)??((edit|www)\.)?boston\.gov|^(/)?sites/default/files/)';
  protected $source = [];

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->source = $row->getSource();

    if (empty($value['value'])) {
      // Skips null and empty values.
      return $value;
    }
    elseif (!empty($value['format']) && $value['format'] == 'plain_text') {
      // Nothing to do here (not a rich text field).
      return $value;
    }
    elseif (!is_string($value['value'])) {
      // Will flag this, but in the end just return the value without trying
      // to do anything with it.
      \Drupal::logger('migrate')->warning("RichTextToMediaEmbed process plugin only accepts rich text inputs.");
      return $value;
    }

    $value['value'] = $this->convertToEntityEmbed($value['value'], $row, $migrate_executable);
    return $value;
  }

  /**
   * Converts files to Entity Embed.
   *
   * @param string $value
   *   The value.
   * @param \Drupal\migrate\Row $row
   *   The current row.
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   The migrate executable.
   *
   * @return string
   *   Process rich text string (html string).
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\migrate\MigrateException
   */
  public function convertToEntityEmbed($value, Row $row, MigrateExecutableInterface $migrate_executable) {
    // D7 media embeds get stored as funky tokens. Replace them with valid HTML
    // so that the preceeding processing works smoothly.
    $this->replaceD7MediaEmbeds($value);

    $document = $this->getDocument($value, $migrate_executable);
    $xpath = new \DOMXPath($document);

    // Images.
    foreach ($xpath->query("//img") as $image_node) {
      $src = $image_node->getAttribute('src');

      // Tidyup for strange content in COB D7 site.
      $src = str_replace('blob:http', 'http', $src);
      $src = preg_replace("~^((/)?modules/file)~", "/sites/modules/file", $src);

      // Change references to pre-production or editor sites.
      $src = $this->correctSubDomain(trim($src));

      if ($this->isExternalFile($src)) {
        continue;
      }
      elseif (!in_array("image", $this->resolveFileType($src))) {
        // This shouldn't ever be the case based on our query, but better safe
        // than sorry.
        $parts = explode('/', $src);
        $extension = $parts[count($parts) - 1];
        \Drupal::logger('Migrate')->notice('Expected an "image" file but got "' . $extension);
        continue;
      }

      if ($media_entity = $this->createMediaEntity($src, 'image', $row, $migrate_executable)) {
        $this->updateImageMedia($media_entity, $image_node, $migrate_executable);
        // Build <drupal-entity> element.
        $drupal_entity_node = $document->createElement('drupal-entity');
        $drupal_entity_node->setAttribute('data-embed-button', 'media_entity_embed');
        $drupal_entity_node->setAttribute('data-entity-embed-display', 'bos_media_image');
        $drupal_entity_node->setAttribute('data-entity-type', 'media');
        $drupal_entity_node->setAttribute('data-entity-uuid', $media_entity->uuid());
        $drupal_entity_node->setAttribute('data-style', $image_node->getAttribute('style'));
        // Replace the image node with the created element.
        $image_node->parentNode->insertBefore($drupal_entity_node, $image_node);
        $image_node->parentNode->removeChild($image_node);
        continue;
      }
    }

    // Now links to the local filesystem.
    foreach ($xpath->query('//a[starts-with(@href, "/sites/default/files") or contains(@href, "boston.gov/sites/default/files")]') as $link_node) {
      $href = $link_node->getAttribute('href');
      if ($this->isExternalFile($href)) {
        // This shouldn't ever be the case based on our query, but better safe
        // than sorry.
        \Drupal::logger('Migrate')->notice('Expected an internal "link" but got ' . $href);
        continue;
      }
      elseif (!in_array("file", $this->resolveFileType($href))) {
        // This shouldn't ever be the case based on our query, but better safe
        // than sorry.
        \Drupal::logger('Migrate')->notice('Expected an internal link to a "file" but got ' . $href);
        continue;
      }

      // Change references to pre-production or editor sites.
      $href = $this->correctSubDomain(trim($href));

      if ($media_entity = $this->createMediaEntity($href, 'document', $row, $migrate_executable)) {
        // Alter <a> element.
        $link_node->setAttribute('data-entity-substitution', 'media');
        $link_node->setAttribute('data-entity-type', 'media');
        $link_node->setAttribute('data-entity-uuid', $media_entity->uuid());
        $link_node->setAttribute('href', "/media/{$media_entity->id()}");
      }
    }

    return Html::serialize($document);
  }

  /**
   * Replaces D7 media embeds with valid HTML.
   *
   * @param string $value
   *   Raw value.
   *
   * @return string
   *   Value with media embeds replaced as HTML.
   */
  protected function replaceD7MediaEmbeds(&$value) {
    $count = 1;
    preg_match_all(self::$MediaWYSIWYGTokenREGEX, $value, $matches);
    if (!empty($matches[0])) {
      foreach ($matches[0] as $match) {
        $replacement = $this->mediaWysiwygTokenToMarkup($match);
        if ($replacement) {
          $value = str_replace($match, $replacement, $value, $count);
        }
      }
    }

    return $value;
  }

  /**
   * Transform media embed token to markup.
   *
   * @param string $token
   *   The token value.
   *
   * @return string
   *   Token with replacements made.
   */
  protected function mediaWysiwygTokenToMarkup(string $token) {
    $json = str_replace("[[", "", $token);
    $json = str_replace("]]", "", $json);

    try {
      if (!is_string($json)) {
        throw new Exception('Unable to find matching tag');
      }
      $tag_info = Json::decode($json);
      if (!$file = \Drupal::service('entity_type.manager')->getStorage('file')->load($tag_info['fid'])) {
        // Nothing to do if we can't find the file.
        return NULL;
      }
      $tag_info['file'] = $file;
      if (!empty($tag_info['attributes']) && is_array($tag_info['attributes'])) {
        if (isset($tag_info['attributes']['style'])) {
          $css_properties = [];
          foreach (array_map('trim', explode(";", $tag_info['attributes']['style'])) as $declaration) {
            if ($declaration != '') {
              list($name, $value) = array_map('trim', explode(':', $declaration, 2));
              $css_properties[strtolower($name)] = $value;
            }
          }
          foreach (['width', 'height'] as $dimension) {
            if (isset($css_properties[$dimension]) && substr($css_properties[$dimension], -2) == 'px') {
              $tag_info[$dimension] = substr($css_properties[$dimension], 0, -2);
            }
            elseif (isset($tag_info['attributes'][$dimension])) {
              $tag_info[$dimension] = $tag_info['attributes'][$dimension];
            }
          }
        }
        foreach (['title', 'alt'] as $field_type) {
          if (isset($tag_info['attributes'][$field_type])) {
            $tag_info['attributes'][$field_type] = Html::decodeEntities($tag_info['attributes'][$field_type]);
          }
        }
      }
    }
    catch (Exception $e) {
      // If we hit an error, don't perform replacement.
      return NULL;
    }

    if (isset($tag_info['link_text'])) {
      $file->filename = Html::decodeEntities($tag_info['link_text']);
    }

    $document = new \DOMDocument();
    $img = $document->createElement('img');
    $uri = $tag_info['file']->getFileUri();
    $url = file_create_url($uri);
    $img->setAttribute('src', file_url_transform_relative($url));
    foreach ($tag_info['attributes'] as $attribute => $value) {
      $img->setAttribute($attribute, $value);
    }
    $document->appendChild($img);
    $document_parts = explode("\n", $document->saveXML());
    $image = array_filter($document_parts, function ($value) {
      return strpos($value, '<img') === 0;
    });
    return array_pop($image);
  }

  /**
   * Determines if file is external.
   *
   * @param string $src
   *   The file src.
   *
   * @return bool
   *   Yes or no.
   */
  protected function isExternalFile($src) {
    return !preg_match(self::$localReferenceREGEX, $src);
  }

  /**
   * Creates a media entity.
   *
   * @param string $src
   *   The file src.
   * @param string $targetBundle
   *   The bundle of the media entity we want to create.
   * @param \Drupal\migrate\Row $row
   *   The current row.
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   Interface.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\media\Entity\Media|null
   *   The media entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\migrate\MigrateException
   */
  public function createMediaEntity(string $src, string $targetBundle, Row $row, MigrateExecutableInterface $migrate_executable) {
    if (!in_array($targetBundle, ['image', 'document'])) {
      throw new MigrateException('Only image and document bundles are supported.');
    }

    $uri = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $src);

    if (!$this->isRelativeUri($src)) {
      $uri = $this->getRelativeUrl($src);
      if ($uri === FALSE) {
        // Nothing to do if we can't extract the URI.
        \Drupal::logger('Migrate')->notice('4:URI extraction failed.');
        return NULL;
      }
    }

    // Now we should have a relative URI, convert it to a stream wrapper.
    $uri = $this->convertToStreamWrapper($uri);
    // We are doing some reorganzing of the filesystem, so make sure that the
    // uri is converted to the new format if applicable.
    $uri = $this->rewriteUri($uri, $row->getSource());
    $file = $this->getFile($uri);
    if (!$file) {
      // File is not in the ManagedFiles table -> Need to go fetch the file ...
      // ... and save it.
      $config = [
        "move" => (\Drupal::state()->get("bos_migration.fileOps") == "move"),
        "copy" => (\Drupal::state()->get("bos_migration.fileOps") == "copy"),
        "file_exists" => \Drupal::state()->get("bos_migration.dest_file_exists", "use existing"),
        "file_exists_ext" => \Drupal::state()->get("bos_migration.dest_file_exists_ext", "skip"),
        "remote_source" => \Drupal::state()->get("bos_migration.remoteSource", "https://www.boston.gov/"),
      ];

      // Copy the file from $src to $uri.
      $fileCopyExt = FileCopyExt::create(\Drupal::getContainer(), $config, "file_copy_ext", []);
      $value = [$src, $uri];
      $uri = $fileCopyExt->transform($value, $migrate_executable, $row, "");

      // Create a new File Entity for this file.
      try {
        $file = $this->saveFile($uri);
        if (empty($file)) {
          throw new Exception("Could not copy " . $src . " and save as " . $uri . ".");
        }
      }
      catch (Exception $e) {
        \Drupal::logger('Migrate')->warning($e->getMessage());
        return NULL;
      }

    }

    $field_name = $targetBundle == 'image' ? 'image' : 'field_document';

    // Create the Media entity.
    $media = Media::create([
      'bundle' => $targetBundle,
      'uid' => $file->getOwnerId() ?? 0,
      'status' => $file->get("status")->getValue()[0]['value'] ?? 1,
      $field_name => [
        'target_id' => $file->id(),
      ],
      // Don't add these images to media library. The media library should be
      // curated by a human.
      'field_media_in_library' => FALSE,
    ]);
    $media->save();
    return $media;
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
    $from_main_domain = '@^http(s|)://(www.|edit.|)boston.gov[/]+(.*)@';
    if (!preg_match($from_main_domain, $uri, $matches)) {
      // Not a searched absolute uri.
      return FALSE;
    }
    if (substr($matches[3], 1, 1) != "/") {
      $matches[3] = "/" . $matches[3];
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
    if (preg_match('@^/sites/default/files/.*@', $uri)) {
      return TRUE;
    }

    return FALSE;
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
    $new_uri = str_replace('/sites/default/files/private/', 'private://', $uri);
    if ($new_uri === $uri) {
      // The replacement wasn't found, so this must be a public file.
      $new_uri = str_replace('/sites/default/files/', 'public://', $uri);
    }

    return $new_uri;
  }

  /**
   * Replaces domain/uri strings.
   *
   * E.g: edit.boston.gov with www.boston.gov.
   *
   * @param string $uri
   *   The original URI (or source or whatever).
   *
   * @return string
   *   The source with correct destintaion mapped in.
   */
  protected function correctSubDomain(string $uri) {
    $regex_swaps = [
      "~(edit|edit-stg)\.boston.gov~" => "www.boston.gov",
      "~http(s|)://boston\.gov~" => "https://www.boston.gov",
      "~http://.*\.boston\.gov~" => "https://www.boston.gov",
    ];
    foreach ($regex_swaps as $find => $replace) {
      $swap = $uri;
      try {
        $uri = preg_replace($find, $replace, $uri);
        if (is_null($uri)) {
          $uri = $swap;
        }
      }
      catch (Exception $e) {
        return $swap;
      }
    }
    return $uri;
  }

  /**
   * Retrieves the corresponding file entity.
   *
   * Best effort search.
   * Assumes the uri have not changed from d7 to d8 during migration, which is
   * just an heuristic.
   *
   * @param string $uri
   *   File uri.
   *
   * @return \Drupal\file\Entity\File|null
   *   File entity object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @todo inject entityTypeManager?
   */
  public function getFile($uri) {
    $file_entities = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->loadByProperties(['uri' => $uri]);
    return !empty($file_entities) ? reset($file_entities) : NULL;
  }

  /**
   * Create a new File object and save (in table file_managed).
   *
   * @param string $uri
   *   The uri to create in the file_managed table.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The file object just created.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function saveFile(string $uri) {
    $filename = end(explode("/", $uri));
    $entity = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->create([
        'uri' => $uri,
        'uid' => '1',
        'filename' => $filename,
        'status' => '1',
      ]);
    $entity->save();
    return $entity;
  }

  /**
   * Updates an image media entity with the passed node element.
   *
   * @param \Drupal\media\MediaInterface $media_entity
   *   The entity to update.
   * @param \DOMElement $image_node
   *   The element to use for the update.
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   Related migrate executable object, used to store any message if needed.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
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
    // White list files based on file_managed table in D7.
    $allowed_formats = [
      'image' => [
        '.jpg',
        '.png',
        '.jpeg',
        '.svg',
        '.gif',
        '.tif',
        '.pdf', /* Technically not correct but ... */
      ],
      'file' => [
        '.pdf',
        '.xls',
        '.xlsx',
        '.docx',
        '.doc',
        '.pptx',
        '.pptm',
        '.ppt',
        '.rtf',
        '.ppt',
        '.jnlp', /* Not sure we should allow this. */
        '.xlsm',
        '.mp3',
        '.mp4',
        '.jpg', /* These are images, but could also be. */
        '.png', /* Downloadable files. */
        '.jpeg', /* ... */
        '.tif', /* ... */
        '.svg', /* ... */
      ],
    ];
    $parts = explode('/', $uri);
    $index = count($parts) - 1;
    $type = [];
    foreach ($allowed_formats as $file_type => $formats) {
      foreach ($formats as $extension) {
        if (strpos($parts[$index], $extension) !== FALSE) {
          $type[] = $file_type;
        }
      }
    }
    if (!empty($type)) {
      return $type;
    }

    // If there is no extension, or the extension is not matched, then return
    // a type of "link".
    return ["link"];
  }

  /**
   * Attempts to find & set a physical path for this media entity.
   */
  protected function setPath($uri) {
    $filename = end(explode("/", $uri));
    /*$filename = explode(".", $filename)[0];*/
    return "embed/" . $filename[0];
  }

}
