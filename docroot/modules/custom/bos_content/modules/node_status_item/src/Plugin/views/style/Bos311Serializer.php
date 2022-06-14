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
            $output[$status_item->id][$field] = [$this->_clean_url($value)];
            break;

          case "311_link_label":
            if (!empty($value) && $value !== "none") {
              if (!empty($status_item->{"311_link"})) {
                $suffix_link = $this->_clean_url($status_item->{"311_link"});
              }
              elseif (!empty($status_item["cta_link"])) {
                $suffix_link = $this->_clean_url($status_item["cta_link"]);
              }
              else {
                $suffix_link = "";
              }
              if (!empty($suffix_link)) {
                $suffix_label = t($status_item->{'311_link_label'},[],['langcode'=>$status_item->language]);
                $suffix = "<br/><a href=\"{$suffix_link}\">{$suffix_label}</a>";
                if (empty($output[$status_item->id]["body"])) {
                  $output[$status_item->id]["body"][$status_item->language] = $suffix;
                }
                else {
                  $output[$status_item->id]["body"][$status_item->language] .= $suffix;
                }
              }
            }
            break;

          case "311_link":
          case "cta_link":
            # not output
            break;

          case "show":
          case "published_at":
            // Only use published and show fields from the base (en) variant.
            if ($status_item->language == "en") {
              $output[$status_item->id][$field] = str_ireplace("\n", "", $value);
            }
            break;

          case "changed":
            // Make sure the most recent publish date is recorded.
            if (!isset($output[$status_item->id][$field])) {
              $output[$status_item->id][$field] = str_ireplace("\n", "", $value);
            }
            elseif (date(str_ireplace("\n", "", $value)) > date($output[$status_item->id][$field])) {
              $output[$status_item->id][$field] = str_ireplace("\n", "", $value);
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

  /**
   * Convert html encoded link/image into a plain url.
   *
   * @param string $url
   *
   * @return string
   */
  private function _clean_url(string $url) {

    $url = trim(str_ireplace("\n", "", $url));

    preg_match('/="(.*?)"/', $url, $url_match);
    if (!empty($url_match)) {
      if (substr($url_match[1],0, 4) != "http") {
        // URL was provided as an internal URL, expand out.
        $url_match[1] = (substr($url_match[1],0, 1) != "/" ? "/" . $url_match[1] : $url_match[1]);
        $url_match[1] = \Drupal::request()->getSchemeAndHttpHost() . $url_match[1];
      }
    }
    else {
      $url = \Drupal::pathValidator()->getUrlIfValid($url);
      if ($url) {
        if ($url->isExternal()) {
          $url_match = [1 => $url->getUri()];
        }
        else {
          $url_match = [1 => \Drupal::request()->getSchemeAndHttpHost() . "/" . $url->getInternalPath()];
        }
      }
      else {
        $url_match = [1 => ""];
      }
    }

    return $url_match[1];

  }
}
