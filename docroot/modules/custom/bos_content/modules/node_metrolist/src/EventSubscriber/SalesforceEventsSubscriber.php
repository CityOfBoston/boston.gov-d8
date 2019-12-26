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
    // @var $sf_data \Drupal\salesforce\SObject.
    $sf_data = $event->getMappedObject()->getSalesforceRecord();
    // Only run on Affordable Housing records.
    if ($sf_data->type() == 'Affordable_Housing__c') {
      // Get fields on current record.
      $sf_fields = array_keys($sf_data->fields());
      // Avoid index errors by making sure field is on record.
      if (in_array('Lottery_Advertisement_Flyer__c', $sf_fields)) {
        // Get URL value for record.
        $lottery_url = $sf_data->field('Lottery_Advertisement_Flyer__c');
        // Check that a URL is set and does not already have http.
        if (strlen($lottery_url) > 0 && strpos($lottery_url, 'http') !== 0) {
          // Add https prefix.
          $lottery_url = 'https://' . $lottery_url;
        }
        // Set the updated URL on the node.
        $event->getEntity()->field_mah_lottery_url = $lottery_url;
      }
      if (in_array('Neighborhood__c', $sf_fields)) {
        $vid = 'neighborhoods';
        $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
        $sf_neighborhood = $sf_data->field('Neighborhood__c');
        foreach ($terms as $term) {
          if ($sf_neighborhood == $term->name) {
            $event->getEntity()->field_mah_neighborhood->target_id = $term->tid;
          }
        }
      }
    }
  }

}
