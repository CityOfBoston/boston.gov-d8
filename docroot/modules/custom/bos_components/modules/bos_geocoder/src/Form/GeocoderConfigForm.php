<?php

namespace Drupal\bos_geocoder\Form;

use Drupal\bos_core\Controllers\Settings\CobSettings;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/*
  class GeocoderConfigForm
  Creates the Administration/Configuration form for bos_geocoder

  david 02 2024
  @file docroot/modules/custom/bos_components/modules/bos_geocoder/src/Form/GeocoderConfigForm.php
*/

class GeocoderConfigForm extends ConfigFormBase {

  /**
   * TODO: Add notes
   */

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'bos_geocoder_GeocoderConfigForm';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ["bos_geocoder.settings"];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $config = CobSettings::getSettings("GEOCODER_SETTINGS", "bos_geocoder", "");

    $envar_list = array_flip($config["config"] ?? []);
    if (empty($envar_list)) {
      $rec_settings = array_intersect_key($config, array_flip($envar_list));
      $note = "<p style='color:red'>Genasys Settings are stored in Drupal config, this is not best practice and not recommended for production sites.</p>
          <p>Please set the <b>GEOCODER_SETTINGS</b> envar to this value:<br><b>" . CobSettings::envar_encode($rec_settings) . "</b></p>";
    }
    else {
      $note = "<b>Some settings are defined in the envar GEOCODER_SETTINGS and cannot be changed using this form.</b> This is best practice - Please change them in the environment.";
    }

    $form = [
      "bos_geocoder" => [
        '#type' => 'fieldset',
        '#title' => 'COB Geocoder Service',
        '#description' => 'Runs geocoding against the City\'s ArcGIS geocoder, and the public Google Geocoder API.',
        '#markup' => $note,
        '#collapsible' => FALSE,
        '#tree' => TRUE,
        'arcgis' => [
          '#type' => 'details',
          '#title' => 'City of Boston ArcGIS Geocoder',
          '#open' => TRUE,
          'base_url' => [
            '#type' => 'textfield',
            '#title' => t('Base URL for geocoder'),
            '#description' => t(''),
            '#default_value' => $config['arcgis']['base_url'] ?? "",
            '#disabled' => array_key_exists("base_url", $envar_list["arcgis"]??[]),
            '#required' => !array_key_exists("base_url", $envar_list["arcgis"]??[]),
            '#attributes' => [
              "placeholder" => 'e.g. https://geocoder.boston.gov/arcgis/rest/services/SAMLocator/GeocodeServer',
            ],
          ],
          'find_location' => [
            '#type' => 'textfield',
            '#title' => t('Endpoint for finding Lat/Long from an address (forward geocode)'),
            '#description' => t('see https://geocoder.boston.gov/arcgis/sdk/rest/index.html#/Find_Address_Candidates/02ss00000015000000/'),
            '#default_value' => $config['arcgis']['find_location'] ?? "",
            '#disabled' => array_key_exists("find_location", $envar_list["arcgis"]??[]),
            '#required' => !array_key_exists("find_location", $envar_list["arcgis"]??[]),
            '#attributes' => [
              "placeholder" => 'e.g. findAddressCandidates',
            ],
          ],
          'find_address' => [
            '#type' => 'textfield',
            '#title' => t('Endpoint for finding address from Lat/Long (reverse geocode)'),
            '#description' => t('see https://geocoder.boston.gov/arcgis/sdk/rest/index.html#/Reverse_Geocode/02ss00000030000000/'),
            '#default_value' => $config['arcgis']['find_address'] ?? "",
            '#disabled' => array_key_exists("find_address", $envar_list["arcgis"]??[]),
            '#required' => !array_key_exists("find_address", $envar_list["arcgis"]??[]),
            '#attributes' => [
              "placeholder" => 'e.g. reverseGeocode',
            ],
          ],
        ],
        'google' => [
          '#type' => 'details',
          '#title' => 'Google API\'s Geocoder',
          '#open' => TRUE,
          'base_url' => [
            '#type' => 'textfield',
            '#title' => t('Base URL for geocoder'),
            '#description' => t(''),
            '#default_value' => $config['google']['base_url'] ?? "",
            '#disabled' => array_key_exists("base_url", $envar_list["google"]??[]),
            '#required' => !array_key_exists("base_url", $envar_list["google"]??[]),
            '#attributes' => [
              "placeholder" => 'e.g. https://maps.googleapis.com',
            ],
          ],
          'find_location' => [
            '#type' => 'textfield',
            '#title' => t('Endpoint for finding Lat/Long from an address (forward geocode)'),
            '#description' => t('see https://developers.google.com/maps/documentation/geocoding'),
            '#default_value' => $config['google']['find_location'] ?? "",
            '#disabled' => array_key_exists("find_location", $envar_list["google"]??[]),
            '#required' => !array_key_exists("find_location", $envar_list["google"]??[]),
            '#attributes' => [
              "placeholder" => 'e.g. maps/api/geocode/json',
            ],
          ],
          'find_address' => [
            '#type' => 'textfield',
            '#title' => t('Endpoint for finding address from Lat/Long (reverse geocode)'),
            '#description' => t('see https://developers.google.com/maps/documentation/geocoding/requests-reverse-geocoding'),
            '#default_value' => $config['google']['find_address'] ?? "",
            '#disabled' => array_key_exists("find_address", $envar_list["google"]??[]),
            '#required' => !array_key_exists("find_address", $envar_list["google"]??[]),
            '#attributes' => [
              "placeholder" => 'e.g. maps/api/geocode/json',
            ],
          ],
          'token' => [
            '#type' => 'textfield',
            '#title' => t('The Google API token'),
            '#description' => t('see https://developers.google.com/maps/documentation/geocoding/get-api-key'),
            '#default_value' => $this->obfuscateToken($config['google']['token'] ?? ""),
            '#disabled' => array_key_exists("token", $envar_list["google"]??[]),
            '#required' => !array_key_exists("token", $envar_list["google"]??[]),
            '#attributes' => [
              "placeholder" => '',
            ],
          ],
        ],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues()["bos_geocoder"];
    $config = $this->config('bos_geocoder.settings');
    $config->set("arcgis.base_url", trim($values["arcgis"]["base_url"], "/\\"));
    $config->set("arcgis.find_location", trim($values["arcgis"]["find_location"], "/\\"));
    $config->set("arcgis.find_address", trim($values["arcgis"]["find_address"], "/\\"));
    $config->set("google.base_url", trim($values["google"]["base_url"], "/\\"));
    $config->set("google.find_location", trim($values["google"]["find_location"], "/\\"));
    $config->set("google.find_address", trim($values["google"]["find_address"], "/\\"));
    if (!str_contains($values["google"]["token"], "************")) {
      $config->set("google.token", $values["google"]["token"]);
    }
    $config->save();
    parent::submitForm($form, $form_state); // optional
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
    // TODO: Create a form validator here.
    //        Typically alter the $form_state object.
    $values = $form_state->getValues();
    $trigger = $form_state->getTriggeringElement();
  }

  /**
   * Make token hard to guess when shown on-screen.
   *
   * @param string $tokenThe token.
   *
   * @return string
   */
  private function obfuscateToken(string $token = "") {
    if (!empty($token)) {
      $token = trim($token);
      return substr($token, 0, 8) . "*****************" . substr($token, -4, 4);
    }
    return "No Token";
  }
}
