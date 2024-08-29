<?php

namespace Drupal\bos_search;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Lazy build callbacks.
 */
class AiSearchFormCallbacks implements TrustedCallbackInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected FormBuilderInterface $form_builder;

  /**
   * Callbacks constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, FormBuilderInterface $form_builder) {
    $this->entityTypeManager = $entity_type_manager;
    $this->form_builder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['renderSearchForm'];
  }

  /**
   * Lazy builder callback for switch-back link.
   *
   * @return array|string
   *   Render array or an emty string.
   */
  public function renderSearchForm(?string $preset = NULL) { //(string $title = "", ?string $preset = NULL) {

    return $this->form_builder->getForm('Drupal\bos_search\Form\AiSearchForm', $preset);

  }

}
