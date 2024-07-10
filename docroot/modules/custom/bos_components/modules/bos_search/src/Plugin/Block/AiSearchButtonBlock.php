<?php

namespace Drupal\bos_search\Plugin\Block;

use Drupal\bos_search\AiSearch;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides an AI-enabled Search Button block.
 *
 * @Block(
 *   id = "Ai-enabled-search-button",
 *   admin_label = @Translation("AI Enabled Search Button"),
 *   category = @Translation("Boston"),
 * )
 */
class AiSearchButtonBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
    /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'search_button_title' => "Search",
      "search_button_css" => "",
      "aisearch_config_preset" => ""
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $presets = AiSearch::getPresets();
    $form = parent::blockForm($form, $form_state);
    $form['search_button_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button Text'),
      '#description' => $this->t('This is the text for the search button.'),
      '#default_value' => $this->configuration['search_button_title'] ?? "",
    ];
    $form['search_button_css'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search Button Custom css'),
      '#description' => $this->t('Add any additional css classes to the button'),
      '#default_value' => $this->configuration['search_button_css'] ?? "",
    ];
    $form['aisearch_config_preset'] = [
      '#type' => 'select',
      '#title' => $this->t('AI-Enabled Search Preset'),
      '#options' => $presets,
      '#description' => $this->t('This defines the AI Model (and settings) that the Search Form will utilise.'),
      '#default_value' => $this->configuration['aisearch_config_preset'] ?? "",
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['search_button_title'] = $form_state->getValue('search_button_title');
    $this->configuration['search_button_css'] = $form_state->getValue('search_button_css');
    $this->configuration['aisearch_config_preset'] = $form_state->getValue('aisearch_config_preset');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    return [
      '#theme' => 'aisearch-button',
      '#search_form_url' => Url::fromRoute('bos_search.open_AISearchForm'),
      '#button_title' => $this->configuration["search_button_title"],
      '#button_css' => $this->configuration["search_button_css"],
      '#preset' => $this->configuration["aisearch_config_preset"],
    ];

  }

}
