<?php

namespace Drupal\node_metrolist\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\salesforce\Event\SalesforceEvents;
use Drupal\salesforce_mapping\Event\SalesforcePullEntityValueEvent;

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
  public function fixLotteryUri(SalesforcePullEntityValueEvent $event) {
      $sf_data = $event->getMappedObject()->getSalesforceRecord();
      $lottery_url = $sf_data->field('Lottery_Advertisement_Flyer__c');
      if (strlen($lottery_url) > 0 && strpos($lottery_url, 'http') !== 0) {
        $lottery_url = 'https://' . $lottery_url;
      }
      $event->getEntity()->field_mah_lottery_url = $lottery_url;
  }

}