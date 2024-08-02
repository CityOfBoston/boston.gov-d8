<?php

namespace Drupal\bos_search\Form;

use Drupal\bos_search\AiSearchRequest;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

/*
  class PromptTesterForm
  Creates the Administration/Configuration form for bos_google_cloud

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

    if ($preset = $form_state->getValue("preset")  ?: \Drupal::request()->get("preset", "")) {
      $config = $this->config("bos_search.settings")->get("presets.$preset");
    }

     $form = [
      "#attached" => ["library" => ["bos_search/overrides"]],
      '#modal_title' => $config["modalform"]["modal_titlebartitle"] ?? "",
      'AiSearchForm' => [
        '#tree' => FALSE,

        'search' => [
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
              [
                '#markup' => Markup::create("<div class='sf--h'><div class='sf--t'>{$config["modalform"]["body_text"]}</div></div>")
              ],
              [
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
                    '#content' => $config["modalform"]["card_1"],
                  ],
                  [
                    '#type' => 'card',
                    '#theme' => 'card',
                    '#attributes' => [
                      'class' => ['br--4', "bg--lb"]
                    ],
                    '#content' => $config["modalform"]["card_2"],
                  ],
                  [
                    '#type' => 'card',
                    '#theme' => 'card',
                    '#attributes' => [
                      'class' => ['br--4', "bg--lb"]
                    ],
                    '#content' => $config["modalform"]["card_3"],
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
        'submit' => [
          '#type' => 'button',
          '#value' => 'Search',
          "#attributes" => [
            "class" => ["hidden"],
          ],
          '#ajax' => [
            'callback' => '::ajaxCallbackSearch',
            'progress' => [
              'type' => 'throbber',
              'message' => 'Scanning boston.gov for information ...'
            ]
          ],
        ],
        'searchtext' => [
          '#theme' => 'search_bar',
          '#default_value' => "",
          '#audio_search_input' => $config["modalform"]["audio_search_input"] ?? FALSE,
          '#attributes' => [
            "placeholder" => $config["modalform"]["search_text"] ?? "",
          ],
          "#description" => $config["modalform"]["disclaimer_text"] ?? "",
        ],
      ],
    ];

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
    $fake = TRUE;     // TRUE = don't actually send to AI Model.

    try {

      // Find the plugin being used (from the preset).
      $preset = $config[$form_values["preset"]] ?? FALSE;
      if (!$preset) {
        throw new \Exception("Cannot find the preset {$form_values['preset']}");
      }
      $plugin_id = $preset["aimodel"];

      // Create the search request object.
      $request = new AiSearchRequest($form_values["searchtext"], $preset['results']["result_count"] ?? 0, $preset['results']["output_template"]);
      $request->set("include_annotations", $preset["results"]["citations"] ?? FALSE);
      $request->set("prompt", $preset["prompt"] ?? FALSE);

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
      \Drupal::messenger()->addError("Test");
      return $form["AiSearchForm"]["search"]["searchresults"];
    }

    // Save this search so we can continue the conversation later
    if ($request->get("conversation_id") != $result->getAll()["conversation_id"]) {
      // Either the conversation_id was not yet created, or else the session
      // for the original conversation has timed-out.
      // Load the conversation_id into the request.
      $request->set("conversation_id", $result->getAll()["conversation_id"]);
    }
    $request->addHistory($result);
    $request->save();

    // Recreate the results container and set the rendered results
    $show_citations = ($preset['results']["citations"] == 1);
    $show_references = ($preset['results']["references"] == 1);
    $show_metadata = ($preset['results']["metadata"] == 1);

    $rendered_result = [
      "#markup" => $result->render($show_citations, $show_references, $show_metadata)
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

    // Render the results into the desired template (from preset).
    foreach($request->getHistory() as $res) {
      $form["AiSearchForm"]["search"]["searchresults"][] = [
        '#markup' => $res->render($show_citations, $show_references, $show_metadata),
      ];
    }

    // Ensure the conversationid is on the form.
    $form["AiSearchForm"]["search"]["searchresults"]["conversation_id"]["#value"] = $request->get("conversation_id");

    // Return the results container.
    return $form["AiSearchForm"]["search"]["searchresults"] ;

  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Not required for this test form.
  }

}
