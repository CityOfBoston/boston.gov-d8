<?php

namespace Drupal\bos_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an AI-enabled Search Form block.
 *
 * @Block(
 *   id = "Ai-enabled-search-form",
 *   admin_label = @Translation("AI Enabled Search Form"),
 *   category = @Translation("Boston"),
 * )
 */
class AiSearchFormBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
    /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      "search_form_title" => "AI Search of Site",
      "aisearch_config_preset" => ""
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['search_form_title'] = $form_state->getValue('search_form_title');
    $this->configuration['aisearch_config_preset'] = $form_state->getValue('aisearch_config_preset');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $params = [
      "preset" => \Drupal::request()->get("preset"),
    ];
    return [
      [
        '#lazy_builder' => ['bos_search.callbacks:renderSearchForm', $params ],
        '#create_placeholder' => TRUE,
      ],
    ];
  }

}
