<?php

namespace Drupal\node_metrolist\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\salesforce\Event\SalesforceEvents;

/**
 * Class SalesforceEventsSubscriber.
 *
 * @package Drupal\node_metrolist\EventSubscriber
 */
class SalesforceEventsSubscriber implements EventSubscriberInterface {

   /**
     * {@inheritdoc}
     *
     * @return array
     *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
      return [
        SalesforceEvents::PULL_ENTITY_VALUE => 'fixLotteryUri',
      ];
  }

  /**
   * Callback.
   */
  public function fixLotteryUri($event) {
  }

}