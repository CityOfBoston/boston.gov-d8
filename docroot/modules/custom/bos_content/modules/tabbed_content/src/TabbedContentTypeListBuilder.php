<?php

namespace Drupal\tabbed_content;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of tabbed content type entities.
 *
 * @see \Drupal\tabbed_content\Entity\TabbedContentType
 */
class TabbedContentTypeListBuilder extends ConfigEntityListBuilder {

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
      'No tabbed content types available. <a href=":link">Add tabbed content type</a>.',
      [':link' => Url::fromRoute('entity.tabbed_content_type.add_form')->toString()]
    );

    return $build;
  }

}
