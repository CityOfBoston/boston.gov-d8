<?php
/**
 * Created by PhpStorm.
 * User: kdonaldson
 * Date: 8/21/18
 * Time: 8:26 AM
 */

namespace Drupal\bos_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Perform value transformations that fixes an invalid URI.
 *
 * @MigrateProcessPlugin(
 *   id = "fix_uri"
 * )
 *
 * To fix uri use the following:
 *
 * @code
 * field_text:
 *   plugin: fix_uri
 *   source: text
 * @endcode
 *
 */

class FixUri extends ProcessPluginBase {
    /**
     * {@inheritdoc}
     */
    public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
        // In the transform() method we perform whatever operations our process
        // plugin is going to do in order to transform the $value provided into its
        // desired form, and then return that value.

        // if uri is valid, then do nothing
        // if uri is invalid, then fix it

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            // return valid URL
            return $value;
        } else {
            // prepend https and validate again to see if that fixes it
            $fixed_url = "https://" . $value;
            if (filter_var($fixed_url, FILTER_VALIDATE_URL)) {
                return $fixed_url;
            } else {
                throw new MigrateException('URL is invalid and can not be fixed');
            }
        }
    }
}
