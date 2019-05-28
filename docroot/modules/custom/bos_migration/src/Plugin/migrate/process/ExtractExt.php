<?php

namespace Drupal\bos_migration\Plugin\migrate\process;

use Drupal\Component\Utility\NestedArray;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\Extract;
use Drupal\migrate\Row;

/**
 * Extracts a value from an array.
 *
 * Improves resiliency.
 *
 * @code
 * process:
 *   new_text_field:
 *     plugin: extract_ext
 *     source: some_text_field
 *     index:
 *       - und
 *       - 0
 *       - value
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "extract_ext",
 *   handle_multiples = TRUE
 * )
 */
class ExtractExt extends Extract {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    try {
      return parent::transform($value, $migrate_executable, $row, $destination_property);
    }
    catch (MigrateException $e) {
      if ($e->getMessage() == "Array index missing, extraction failed.") {

        // Some paragraphs seem to be returning $value arrays with different
        // keys.  This tries known alternative keys.
        if ($this->configuration['index'] == ["0"] && array_key_exists("id", $value)) {
          $new_value = NestedArray::getValue($value, ["id"], $key_exists);
        }
        elseif ($this->configuration['index'] == ["1"] && array_key_exists("revision_id", $value)) {
          $new_value = NestedArray::getValue($value, ["revision_id"], $key_exists);
        }

        if (isset($new_value)) {
          return $new_value;
        }

      }
      // Pass/bubble the error upwards.
      throw new MigrateException($e->getMessage());
    }
  }

}
