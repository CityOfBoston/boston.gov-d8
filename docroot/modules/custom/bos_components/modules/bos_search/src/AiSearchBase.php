<?php

namespace Drupal\bos_search;

use Drupal\bos_google_cloud\Services\GcServiceInterface;
use Drupal\Component\Plugin\PluginBase;

abstract class AiSearchBase extends PluginBase implements AiSearchInterface {

  /** @var GcServiceInterface Holds the injected AI service. */
  protected $service;
   /**
   * @inheritDoc
   */
  public function getServiceId() {
    // Return the service defined in the plugin annotation on the plugin class.
    return (string) $this->pluginDefinition['service'];
  }

  /**
   * @inheritDoc
   */
  public function getService() {
    if (empty($this->service)) {
      $serviceid = $this->getServiceId();
      $this->service = \Drupal::getContainer()->get($serviceid);
    }
    return $this->service;
  }

  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->getService();
  }

  /**
   * Reformats the metadata array into a better format for rendering in twig.
   *
   * @param array $metadata
   * @param array $map
   * @param array $exclude_elem
   *
   * @return void
   */
  protected function flatten_metadata(array &$metadata, array $map = [], array $exclude_elem = []):array {

    foreach($metadata as $key => &$elem) {
      if (is_array($elem)) {
        $elem = $this->flatten_md($elem, $map, $exclude_elem);
      }
      else {
        $key = ucwords(str_replace("_", " ", $key ));
        $metadata[$key] = $elem;
      }
    }
    return $metadata;

  }

  private function flatten_md(array $elements, array $map = [], array $exclude_elem = [], string $prefix = ''):?array {

    $output = [];

    foreach($elements as $key => $value) {

      if (!in_array($key, $exclude_elem)) {

        if (is_array($value)) {
          $output["$prefix.$key"] = $this->flatten_md($value, $map, $exclude_elem, "$prefix.$key");
//            return $this->flatten_md($value, $map, $exclude_elem, "$prefix.$key");
        }
        else {
          $key = ($map[$key] ?? $key);
          $metatitle = str_replace(" ", "", ucwords(str_replace("_", " ", ($prefix ? "$prefix.$key" : $key))));
          $output["$prefix.$key"] = [
            "key" => $metatitle,
            "value" => $value
          ];
        }
      }

    }

    return (empty($output) ? NULL : $output);

  }

}
