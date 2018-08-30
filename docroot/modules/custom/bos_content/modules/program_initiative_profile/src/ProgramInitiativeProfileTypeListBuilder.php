<?php

namespace Drupal\program_initiative_profile;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of program initiative profile type entities.
 *
 * @see \Drupal\program_initiative_profile\Entity\ProgramInitiativeProfileType
 */
class ProgramInitiativeProfileTypeListBuilder extends ConfigEntityListBuilder {

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
      'No program initiative profile types available. <a href=":link">Add program initiative profile type</a>.',
      [':link' => Url::fromRoute('entity.program_initiative_profile_type.add_form')->toString()]
    );

    return $build;
  }

}
