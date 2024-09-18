<?php

namespace Drupal\bos_search;

use Drupal\Component\Plugin\PluginBase;

abstract class AiSearchBase extends PluginBase implements AiSearchInterface {

   /**
   * @inheritDoc
   */
  public function getService() {
    // Return the service defined in the plugin annotation on the plugin class.
    return (string) $this->pluginDefinition['service'];
  }

}
