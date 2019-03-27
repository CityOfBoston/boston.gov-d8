<?php

namespace Drupal\linkit_media_creation\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\media\Entity\Media;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormBuilder;

/**
 * Create media dialogue.
 */
class MediaCreationDialogue extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManager $entityTypeManager, FormBuilder $formBuilder) {
    $this->entityTypeManager = $entityTypeManager;
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $media = Media::create([
      'targetEntityType' => "media",
      'bundle' => "document",
      'status' => TRUE,
    ]);

    $form = $this->entityTypeManager
      ->getFormObject('media', 'default')
      ->setEntity($media);

    $form = $this->formBuilder->getForm($form);
    $form['revision_log_message']['#access'] = FALSE;
    $form['#attached']['library'][] = 'linkit_media_creation/commands';
    $form['#attached']['library'][] = 'linkit_media_creation/dialogue.display';
    return $form;
  }

}
