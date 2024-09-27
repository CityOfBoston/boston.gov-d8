<?php

namespace Drupal\bos_search;

use Drupal\bos_google_cloud\Services\GcServiceInterface;
use Drupal\Component\Plugin\PluginBase;

abstract class AiSearchBase extends PluginBase implements AiSearchInterface {

  /** @var \Drupal\bos_google_cloud\Services\GcServiceInterface Holds the injected AI service. */
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


}
