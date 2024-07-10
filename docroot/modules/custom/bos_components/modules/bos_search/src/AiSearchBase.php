<?php

namespace Drupal\bos_search;

use Drupal\bos_search\Plugin\AiSearch\AiSearchPluginManager;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AiSearchBase extends PluginBase implements AiSearchInterface {

   /**
   * @inheritDoc
   */
  public function getService() {
    // Return the service defined in the plugin annotation on the plugin class.
    return (string) $this->pluginDefinition['service'];
  }

}
