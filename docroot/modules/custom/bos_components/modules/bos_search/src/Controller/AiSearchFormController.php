<?php

namespace Drupal\bos_search\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/*
  class SearchFormController
  Creates the search form for bos_search

  david 06 2024
  @file docroot/modules/custom/bos_components/modules/bos_search/src/Form/src/Controller/AiSearchFormController.php
*/

class AiSearchFormController extends ControllerBase {

  /**
   * This Controller class is used to launch a modal instance of the
   * AiSearchForm.
   */

  protected $formBuilder;

  /**
   * The constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   */
  public function __construct(FormBuilder $formBuilder) {
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Callback for opening the modal form.
   */
  public function openModalForm() {

    $request = \Drupal::request();

    $response = new AjaxResponse();

    // Get the modal form using the form builder.
    $modal_form = $this->formBuilder->getForm('Drupal\bos_search\Form\AiSearchForm');
    $modal_form["AiSearchForm"]["search"]["preset"]["#default_value"] = $request->get("preset");
    $modal_form["AiSearchForm"]["search"]["preset"]["#value"] = $request->get("preset");
    // Add an AJAX command to open a modal dialog with the form as the content.
    $response->addCommand(new OpenModalDialogCommand($request->get("title"), $modal_form, ['width' => '85%', 'height' => '95%']));

    return $response;
  }

}
