<?php

namespace Drupal\procurement_advertisement;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of procurement advertisement type entities.
 *
 * @see \Drupal\procurement_advertisement\Entity\ProcurementAdvertisementType
 */
class ProcurementAdvertisementTypeListBuilder extends ConfigEntityListBuilder {

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
      'No procurement advertisement types available. <a href=":link">Add procurement advertisement type</a>.',
      [':link' => Url::fromRoute('entity.procurement_advertisement_type.add_form')->toString()]
    );

    return $build;
  }

}
