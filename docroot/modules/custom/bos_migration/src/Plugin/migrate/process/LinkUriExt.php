<?php

namespace Drupal\bos_migration\Plugin\migrate\process;

use Drupal\menu_link_content\Plugin\migrate\process\LinkUri;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Maps <nolink> menu items to route:<nolink>.
 *
 * @code
 * process:
 *   link/uri:
 *     plugin: link_uri_ext
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "link_uri_ext"
 * )
 */
class LinkUriExt extends LinkUri {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    try {
      return parent::transform($value, $migrate_executable, $row, $destination_property);
    }
    catch (MigrateException $e) {
      return 'route:<nolink>';
    }
  }

}
