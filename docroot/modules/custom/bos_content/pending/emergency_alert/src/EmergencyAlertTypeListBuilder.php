<?php

namespace Drupal\emergency_alert;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of emergency alert type entities.
 *
 * @see \Drupal\emergency_alert\Entity\EmergencyAlertType
 */
class EmergencyAlertTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = $this->t('Label');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['title'] = [
      'data' => $entity->label(),
      'class' => ['menu-label'],
    ];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No emergency alert types available. <a href=":link">Add emergency alert type</a>.',
      [':link' => Url::fromRoute('entity.emergency_alert_type.add_form')->toString()]
    );

    return $build;
  }

}
