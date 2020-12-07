<?php

namespace Drupal\bos_migration\Plugin\migrate\process;

/*
 * COB NOTE:
 * In this boston.gov implementation, this class/plugin is added by
 *   bos_migration->bos_migration_migration_plugins_alter()
 * which adds this plugin to the process of 'text_long', 'text_with_summary'
 * fields.
 */

use Drupal\bos_migration\FilesystemReorganizationTrait;
use Drupal\bos_migration\HtmlParsingTrait;
use Drupal\bos_migration\MediaEntityTrait;
use Drupal\file\Entity\File;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\Component\Utility\Html;
use Drupal\Component\Serialization\Json;

/**
 * Replace local image and link tags with entity embeds.
 *
 * @MigrateProcessPlugin(
 *   id = "rich_text_to_media_embed"
 * )
 */
class RichTextToMediaEmbed extends ProcessPluginBase {

  use HtmlParsingTrait;
  use FilesystemReorganizationTrait;
  use MediaEntityTrait;

  /**
   * Regex to identify the media embedded objects.
   *
   * @var string
   */
  protected static $mediaWysiwygTokenRegex = '/\[\[\{.*?"type":"media".*?\}\]\]/s';

  /**
   * Source from the $row object.
   *
   * @var array
   */
  protected $source = [];

  /**
   * The row from the migration.
   *
   * @var \Drupal\migrate\Row
   */
  protected $row;

  /**
   * MigrateExecutableInterface for the class.
   *
   * @var \Drupal\migrate\MigrateExecutableInterface
   */
  protected $migrateExecutable;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (NULL == $value || $this->quitEarly($value)) {
      return $value;
    }
    if (empty($value['format'])) {
      // Ensure the format is filtered_html if empty.
      $value['format'] = "filtered_html";
    }

    $this->source = $row->getSource();
    $this->row = $row;
    $this->migrateExecutable = $migrate_executable;

    $value['value'] = $this->convertToEntityEmbed($value['value']);
    return $value;
  }

  /**
   * Check the $value to see if we can/need to process this.
   *
   * @param array $value
   *   Value.
   *
   * @return bool
   *   True if we need to exit.
   */
  private function quitEarly(array $value) {
    if (empty($value['value'])) {
      // Skips null and empty values.
      return TRUE;
    }
    elseif (!empty($value['format']) && $value['format'] == 'plain_text') {
      // Nothing to do here (not a rich text field).
      return TRUE;
    }
    elseif (!is_string($value['value'])) {
      // Will flag this, but in the end just return the value without trying
      // to do anything with it.
      \Drupal::logger('migrate')->warning("RichTextToMediaEmbed process plugin only accepts rich text inputs.");
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Converts files to Entity Embed.
   *
   * @param string $value
   *   The value.
   *
   * @return string
   *   Process rich text string (html string).
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\migrate\MigrateException
   * @throws \Drupal\migrate\MigrateSkipRowException
   */
  public function convertToEntityEmbed(string $value) {
    // D7 media embeds get stored as funky tokens. Replace them with valid HTML
    // so that the preceeding processing works smoothly.
    $this->replaceD7MediaEmbeds($value);

    $document = $this->getDocument($value, $this->migrateExecutable);
    $xpath = new \DOMXPath($document);

    // Images.
    foreach ($xpath->query("//img") as $image_node) {
      $src = trim($image_node->getAttribute('src'));

      // Tidyup for strange content in COB D7 site.
      $src = str_replace('blob:http', 'http', $src);
      $src = preg_replace("~^((/)?modules/file)~", "/sites/modules/file", $src);

      // Change references to pre-production or editor sites.
      $src = $this->correctSubDomain(trim($src));

      if ($this->isExternalFile($src)) {
        // Don't want to build a media string for an external file.
        continue;
      }
      elseif (!$this->permittedFileType("image", $src)) {
        // This shouldn't ever be the case based on our query, but better safe
        // than sorry.
        $parts = explode('/', $src);
        $filename = trim(end($parts));
        $extension = end(explode(".", $filename));
        if (empty($extension)) {
          $extension = substr($filename, -4);
          $extension = end(explode(".", $extension));
        }
        $msg = t("In @type:@bundle#@id (in @field) expected an \"image\" file but got \"@ext\" (@filename)\n'@value'", [
          "@ext" => $extension,
          "@filename" => $filename,
          "@field" => $this->source["field_name"],
          "@id" => $this->source["item_id"],
          "@type" => $this->source["plugin"],
          "@bundle" => $this->source["bundle"],
          "@value" => ($extension == "" ? $value : ""),
        ]);
        \Drupal::logger('Migrate')->notice($msg);
        continue;
      }

      if ($media_entity = $this->process($src, 'image')) {
        $this->updateImageMediaAlt($media_entity, $image_node, $this->migrateExecutable);
        // Build <drupal-entity> element.
        $drupal_entity_node = $document->createElement('drupal-entity');
        $drupal_entity_node->setAttribute('data-embed-button', 'media_entity_embed');
        $drupal_entity_node->setAttribute('data-entity-embed-display', 'bos_media_image');
        $drupal_entity_node->setAttribute('data-entity-type', 'media');
        $drupal_entity_node->setAttribute('data-entity-uuid', $media_entity->uuid());
        $drupal_entity_node->setAttribute('alt', $media_entity->image[0]->alt);
        $drupal_entity_node->setAttribute('height', $image_node->getAttribute("height"));
        $drupal_entity_node->setAttribute('width', $image_node->getAttribute("width"));
        $drupal_entity_node->setAttribute('class', $image_node->getAttribute("class"));
        $drupal_entity_node->setAttribute('data-style', $image_node->getAttribute('style'));
        // Replace the image node with the created element.
        $image_node->parentNode->insertBefore($drupal_entity_node, $image_node);
        $image_node->parentNode->removeChild($image_node);
        continue;
      }
    }

    // Now links to the local filesystem.
    foreach ($xpath->query('//a[starts-with(@href, "/sites/default/files") or contains(@href, "boston.gov/sites/default/files")]') as $link_node) {
      $href = trim($link_node->getAttribute('href'));

      if ($this->isExternalFile($href)) {
        // This shouldn't ever be the case based on our query, but better safe
        // than sorry.
        \Drupal::logger('Migrate')->notice('Expected an internal "link" but got ' . $href);
        // Don't want to build a media string for an external file.
        continue;
      }
      elseif (!$this->permittedFileType("link", $href)) {
        // This shouldn't ever be the case based on our query, but better safe
        // than sorry.
        \Drupal::logger('Migrate')->notice('Expected an internal link to a "file" but got "' . $href . '"');
        // Don't want to build a media string for an external file.
        continue;
      }

      // Change references to pre-production or editor sites.
      $href = $this->correctSubDomain(trim($href));

      if ($media_entity = $this->process($href, 'document')) {
        // Alter <a> element.
        $link_node->setAttribute('data-entity-substitution', 'media');
        $link_node->setAttribute('data-entity-type', 'media');
        $link_node->setAttribute('data-entity-uuid', $media_entity->uuid());
        $link_node->setAttribute('alt', $link_node->getAttribute("alt"));
        $link_node->setAttribute('href', "/media/{$media_entity->id()}");
      }
    }

    return Html::serialize($document);
  }

  /**
   * Creates a File entity, and then a media entity for linking.
   *
   * @param string $src
   *   The original uri for the file.
   * @param string $targetBundle
   *   The media type.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The Media entity created.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\migrate\MigrateException
   * @throws \Drupal\migrate\MigrateSkipRowException
   */
  private function process(string $src, string $targetBundle) {
    if (!in_array($targetBundle, ['image', 'document', 'icon'])) {
      throw new MigrateException('Only image and document bundles are supported.');
    }

    // Create the destination Uri.
    if (NULL == ($dest = $this->createDestUri($src))) {
      return NULL;
    }

    // Try to find this file from its uri, or make a new file entity.
    if (NULL == ($files = $this->getFileEntities($dest))) {
      // File is not in the ManagedFiles table -> Need to go fetch the file ...
      // ... and save it.
      $config = [
        "move" => (\Drupal::state()->get("bos_migration.fileOps") == "move"),
        "copy" => (\Drupal::state()->get("bos_migration.fileOps") == "copy"),
        "file_exists" => \Drupal::state()->get("bos_migration.dest_file_exists", "use existing"),
        "file_exists_ext" => \Drupal::state()->get("bos_migration.dest_file_exists_ext", "skip"),
        "remote_source" => \Drupal::state()->get("bos_migration.remoteSource", "https://www.boston.gov/"),
      ];

      // Physically copy the file from $src to $dest.
      $value = [$src, $dest];
      $fileCopyExt = FileCopyExt::create(\Drupal::getContainer(), $config, "file_copy_ext", []);
      $dest = $fileCopyExt->transform($value, $this->migrateExecutable, $this->row, "");

      // Create a new File Entity for this file.
      try {
        $file = $this->createFileEntity($dest);
        if (empty($file)) {
          throw new \Exception("Could not copy " . $src . " and save as " . $dest . ".");
        }
      }
      catch (\Exception $e) {
        \Drupal::logger('Migrate')->warning($e->getMessage());
        return NULL;
      }

    }
    else {
      // A matching file was found: if more than one file, just get the first.
      $file = reset($files);
      $file = File::load($file);
    }

    // Since we only have uri's to work from, make a nice filename.
    $filename = $this->cleanFilename($file->getFilename());

    // Create and return the Media entity which links to the file entity
    // found or just created.
    return $this->createMediaEntity($targetBundle, $file->id(), $filename, $file->getOwnerId(), FALSE);
  }

  /**
   * Generate a uri to migrate the file to.
   *
   * @param string $uri
   *   The original source uri.
   *
   * @return string|null
   *   Generated destination uri.
   */
  private function createDestUri(string $uri) {
    $uri = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $uri);

    if (!$this->isRelativeUri($uri)) {
      $uri = $this->getRelativeUrl($uri);
      if ($uri === FALSE) {
        // Nothing to do if we can't extract the URI.
        return NULL;
      }
    }

    // Now we should have a relative URI, convert it to a stream wrapper.
    $uri = $this->convertToStreamWrapper($uri);

    // We are doing some reorganzing of the filesystem, so make sure that the
    // uri is converted to the new format if applicable.
    return $this->rewriteUri($uri, $this->source);
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
    preg_match_all(self::$mediaWysiwygTokenRegex, $value, $matches);
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
        throw new \Exception('String selected does not resolve to JSON. ', -889977);
      }
      $tag_info = Json::decode($json);
      if (!$file = File::load($tag_info['fid'])) {
        // Nothing to do if we can't find the file.
        throw new \Exception("Image " . $tag_info['fid'] . " could not be found.", -889977);
      }
      $tag_info['file'] = $file;
      if (!empty($tag_info['attributes']) && is_array($tag_info['attributes'])) {
        if (isset($tag_info['attributes']['style'])) {
          $css_properties = [];
          foreach (array_map('trim', explode(";", $tag_info['attributes']['style'])) as $declaration) {
            if ($declaration != '') {
              [$name, $value] = array_map('trim', explode(':', $declaration, 2));
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
    catch (\Exception $e) {
      // If we hit an error, don't perform replacement.
      if ($e->getCode() == -889977) {
        throw new MigrateSkipProcessException($e->getMessage());
      }
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

}
