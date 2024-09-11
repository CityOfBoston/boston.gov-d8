<?php

namespace Drupal\bos_search\Form;

use Drupal\bos_search\AiSearch;
use Drupal\bos_search\AiSearchRequest;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

/*
  class PromptTesterForm
  - Performs AI Searches using the requested preset.

  david 04 2024
  @file docroot/modules/custom/bos_components/modules/bos_google_cloud/src/Form/src/Form/PromptTesterForm.php
*/

class AiSearchForm extends FormBase {

  /**
   * This form allows a user to submit a conversation-based search.
   */

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'bos_search_AISearchForm';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form = [
      "#attached" => ["library" => ["bos_search/overrides"]],
      '#modal_title' => $config["searchform"]["modal_titlebartitle"] ?? "",
    ];

    $preset = AiSearch::getPreset();
    $config = $this->config("bos_search.settings")->get("presets.$preset");
    if (empty($config)) {
      $form += [
        '#errors' => true,
        "problem" => [
          "message" => [
            "#markup" => "<h2 class='warning'>Configuration Error:</h2><div>The Preset for this form is not correctly setup.<br>Please set up a configuration at /admin/config/system/boston/aisearch</div>"
          ],
        ],
      ];
      return $form;
    }

     $form += [
      'AiSearchForm' => [
        '#tree' => FALSE,

        'content' => [
          '#type' => 'container',
          '#attributes' => [
            'id' => ['edit-aisearchform'],
          ],
          'preset' => [
            '#type' => 'hidden',
            '#default_value' => $preset,
          ],
          'messages' => [
            '#type' => 'container',
            '#attributes' => ['id' => ['edit-messages']],
          ],
          'searchresults' => [
            '#type' => 'container',
            '#attributes' => ['id' => ['edit-searchresults']],
            'welcome' => [
              '#type' => 'container',
              '#attributes' => [
                "id" => "edit-welcome",
              ],
              "title" => [
                '#markup' => Markup::create($config["searchform"]['welcome']["body_title"])
              ],
              "body" => [
                '#markup' => Markup::create($config["searchform"]['welcome']["body_text"])
              ],
              "cards" => [
                '#type' => 'grid_of_cards',
                '#theme' => 'grid_of_cards',
                "#title" => "Example",
                "#title_attributes" => [],
                '#cards' => [
                  [
                    '#type' => 'card',
                    '#theme' => 'card',
                    '#attributes' => [
                      'class' => ['br--4', "bg--lb"]
                    ],
                    '#content' => $config["searchform"]['welcome']["cards"]["card_1"] ?: "",
                  ],
                  [
                    '#type' => 'card',
                    '#theme' => 'card',
                    '#attributes' => [
                      'class' => ['br--4', "bg--lb"]
                    ],
                    '#content' => $config["searchform"]['welcome']["cards"]["card_2"] ?: "",
                  ],
                  [
                    '#type' => 'card',
                    '#theme' => 'card',
                    '#attributes' => [
                      'class' => ['br--4', "bg--lb"]
                    ],
                    '#content' => $config["searchform"]['welcome']["cards"]["card_3"] ?: "",
                  ],
                ]
              ],
            ],
          ],
          'conversation_id' => [
            '#type' => 'hidden',
            '#prefix' => "<div id='edit-conversation_id'>",
            '#suffix' => "</div>",
            '#default_value' => $form_state->getValue("conversation_id")  ?: "",
          ],
        ],
        'actions' => [
          '#type' => 'button',
          '#value' => 'Search',
          "#attributes" => [
            "class" => ["hidden"],
          ],
          '#ajax' => [
            'callback' => '::ajaxCallbackSearch',
            'progress' => [
              'type' => 'none',
//              'message' => $config["results"]["waiting_text"]
            ]
          ],
        ],
        'searchbar' => [
          '#theme' => 'search_bar',
          '#default_value' => "",
          '#audio_search_input' => $config["searchform"]['searchbar']["audio_search_input"] ?? FALSE,
          '#attributes' => [
            "placeholder" => $config["searchform"]['searchbar']["search_text"] ?? "",
          ],
          "#description" => $config["searchform"]['searchbar']["search_note"] ?? "",
          "#description_display" => "after",
        ],
      ],
    ];

    if (!$config["searchform"]["welcome"]["cards"]["enabled"]) {
      unset($form["AiSearchForm"]['content']["searchresults"]["welcome"]["cards"]);
    }

    return $form;

  }

  /**
   * Ajax callback to run the desired test against the selected AI Model.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   * @throws \Exception
   */
  public function ajaxCallbackSearch(array $form, FormStateInterface $form_state): AjaxResponse {
    $config = \Drupal::config("bos_search.settings")->get("presets");
    $form_values = $form_state->getUserInput();
    $fake = FALSE;     // TRUE = don't actually send to AI Model.

    try {

      // Find the plugin being used (from the preset).
      $preset = $config[$form_values["preset"]] ?? FALSE;
      if (!$preset) {
        throw new \Exception("Cannot find the preset {$form_values['preset']}");
      }
      if (empty($preset['aimodel'])) {
        throw new \Exception("The prerset {$preset['aimodel']} is not defined.");
      }
      $plugin_id = $preset["aimodel"];

      // Create the search request object.
      $request = new AiSearchRequest($form_values["searchbar"], $preset['results']["result_count"] ?? 0);
      $request->set("preset", $preset);

      // TODO: what if the model does not allow a conversation?
      if (!empty($form_values["conversation_id"])) {
        // Set the conversationid. This causes any history for the conversation
        // to be reloaded into the $request object.
        $request->set("conversation_id", $form_values["conversation_id"]);
      }

      // Instantiate the plugin, and call the search using the search object.
      /** @var \Drupal\bos_search\AiSearchInterface $plugin */
      $plugin = \Drupal::service("plugin.manager.aisearch")
        ->createInstance($plugin_id);

      $result = $plugin->search($request, $fake);

    }
    catch (\Exception $e) {
      // TODO: Create and populate an error message on the page..
      \Drupal::messenger()->addError($e->getMessage());
      return $form["AiSearchForm"]["search"]["searchresults"];
    }

    // Save this search so we can continue the conversation later
    // TODO: what if the model does not allow a conversation?
    if ($request->get("conversation_id") != $result->getAll()["conversation_id"]) {
      // Either the conversation_id was not yet created, or else the session
      // for the original conversation has timed-out.
      // Load the conversation_id into the request.
      $request->set("conversation_id", $result->getAll()["conversation_id"]);
    }
    $request->addHistory($result);
    $request->save();

    // This will render the output form using the input array.
    $rendered_result = [
      "#type" => "inline_template",
      "#template" => $result->render()
    ];
    $output = new AjaxResponse();
    $output->addCommand(new AppendCommand('#edit-searchresults', $rendered_result));
    $output->addCommand(new ReplaceCommand('#edit-conversation_id', [
      'conversation_id' => [
        '#type' => 'hidden',
        '#attributes' => [
          "data-drupal-selector" => "edit-conversation-id",
          "name" => "conversation_id",
        ],
        '#prefix' => "<div id='edit-conversation_id'>",
        '#suffix' => "</div>",
        '#value' => $request->get("conversation_id")  ?: "",
      ]
    ]));

    return $output;

  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Not required for this form.
  }

}
