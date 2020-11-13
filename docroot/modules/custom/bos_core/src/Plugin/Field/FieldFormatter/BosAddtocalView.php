<?php


namespace Drupal\bos_core\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\addtocal\Plugin\Field\FieldFormatter\AddtocalView;

/**
 * Boston Add to Cal view formatter.
 *
 * @FieldFormatter(
 *  id = "bosaddtocal_view",
 *  label = @Translation("Boston Add to Cal"),
 *  field_types = {
 *    "date",
 *    "datestamp",
 *    "datetime",
 *    "daterange",
 *    "date_recur",
 *  }
 * )
 */
class BosAddtocalView extends AddtocalView {

  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items,$langcode);
    return $elements;
  }
}
