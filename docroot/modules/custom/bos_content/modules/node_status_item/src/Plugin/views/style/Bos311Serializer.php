<?php

namespace Drupal\node_status_item\Plugin\views\style;

use Drupal\rest\Plugin\views\style\Serializer;

/**
 * The style plugin for serialized output formats.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "node_status_item",
 *   title = @Translation("Status Item"),
 *   help = @Translation("Serializes views row data using the Serializer
 *   component."), display_types = {"data"}
 * )
 */
class Bos311Serializer extends Serializer {
  public function render() {
    $output = [];

    // Get the feed output.
    $feed = json_decode(parent::render());

    // Reformat the feed output.
    foreach($feed as $status_item) {
      !empty($output[$status_item->id]) ?: $output[$status_item->id] = [];
      foreach($status_item as $field => $value) {
        switch($field) {
          case "body":
          case "title":
            // Translatable fields.
            if (empty($output[$status_item->id][$field])) {
              $output[$status_item->id][$field] = [];
            }
            $output[$status_item->id][$field][$status_item->language] = trim(str_ireplace("\n", "", $value));
            break;
          case "media":
          case "link":
            $value = trim(str_ireplace("\n", "", $value));
            // Convert html encoded link/image into a plain url.
            preg_match('/="(.*?)"/', $value, $url);
            if (!empty($url)) {
              if (substr($url[1],0, 4) != "http") {
                // URL was provided as an internal URL, expand out.
                $url[1] = (substr($url[1],0, 1) != "/" ? "/" . $url[1] : $url[1]);
                $url[1] = \Drupal::request()->getSchemeAndHttpHost() . $url[1];
              }
              if ($field == "media") {
                $url[1] = [$url[1]];
              }
              $output[$status_item->id][$field] = $url[1];
            }
            else {
              $url = [1 => ""];
            }
            break;

          default:
            // Non-Translatable fields.
            $output[$status_item->id][$field]= str_ireplace("\n", "", $value);
            break;

        }
      }
    }

    // Now calculate the last updated date/time.
    foreach($output as &$row) {
      $row["updated_at"] = $row["changed"];
      if (!empty($row["show"]) && !empty($row["changed"])) {
        $row["updated_at"] = $row["changed"];
        if (date($row["show"]) > date($row["changed"])) {
          $row["updated_at"] = $row["show"];
        }
      }
      unset($row["show"]);    // redundant
      unset($row["changed"]);  // redundant
      unset($row["language"]);  // not relevant any longer
    }

    $output = array_values($output);
    return json_encode($output);
  }

}
