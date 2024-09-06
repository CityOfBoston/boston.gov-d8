<?php

namespace Drupal\bos_search\Form;

use Drupal\bos_search\AiSearch;
use Drupal\bos_search\AiSearchRequest;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/*
  class PromptTesterForm
  - Performs AI Searches using the requested preset.

  david 04 2024
  @file docroot/modules/custom/bos_components/modules/bos_google_cloud/src/Form/src/Form/PromptTesterForm.php
*/

class AiDisclaimerForm extends FormBase {

  /**
   * This form allows a user to submit a conversation-based search.
   */

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'bos_search_AIDisclaimerForm';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = AiSearch::getPresetValues();

    $form = [
      "#attached" => ["library" => ["bos_search/overrides"]],
      '#modal_title' => $config["searchform"]["modal_titlebartitle"] ?? "",
      '#theme' => "disclaimer__{$config["searchform"]["theme"]}",
      'notice' => [
        "#markup" => Markup::create($config["searchform"]["disclaimer"]["text"]),
      ],
      'actions' => [
        'submit' => [
          '#type' => 'submit',
          '#value' => 'Continue',
          '#attributes' => [
            "class" => [
              "btn-submit"
            ],
          ],
        ],
        'cancel' => [
          '#type' => 'button',
          '#value' => 'Cancel',
          '#access' => FALSE,
          '#attributes' => [
            "class" => [
              "btn-cancel"
            ],
          ],
        ],
      ],
    ];

    return $form;

  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Not required.
  }

}
