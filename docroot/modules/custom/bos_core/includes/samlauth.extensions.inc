<?php

use Drupal\Core\Render\Markup;

/**
 * Extends the samlauth configuration form to allow roles and realname to be
 * customised from the UI.
 * The settings are saved in the custom submit handler
 * _bos_core_saml_configure_form_submit() and the configuration is used by
 * samlAuthentication.php to extend basic saml processes from samlauth and
 * externalauth modules.
 *
 * Implements hook_form_FORM_ID_alter().
 */
function bos_core_form_samlauth_configure_form_alter(&$form, $form_state, $form_id) {
  $config = \Drupal::config("samlauth.authentication");
  $form["user_info"]["realname"] = [
    "#default_value" => $config->get("realname") ?: "",
    '#description' => Markup::create("When a user is created, create a realname from these attributes. Linked users do not get additional roles.<br>Example <i>sn lastname</i>"),
    '#title' => Markup::create("User realname attribute"),
    '#attributes' => ["placeholder" => "sn lastname"],
    '#type' => 'textfield',
  ];

  $options = ["none" => "Select Roles"];
  foreach(user_roles() as $key => $role) {
    if (!in_array($key, ["anonymous", "authenticated"])) {
      $options[$key] = $role->get("label");
    }
  }

  $form["user_info"]["roles"] = [
    "#default_value" => $config->get("roles") ?: [],
    '#description' => Markup::create("When a user is created, add these roles. Linked users do not get additional roles.<br>Use Ctrl+Shift to select multiple roles. "),
    '#title' => Markup::create("New User Roles"),
    '#type' => 'select',
    '#multiple' => TRUE,
    '#options' => $options,
  ];
  $form["#submit"][] = "samlauth_extensions_form_submit";
  $form_state->setRebuild(true);
}

/**
 * Custom submit handler for the samlauth sconfig form which was extended in
 * bos_core_form_samlauth_configure_form_alter. Saves new fields into the
 * 'samlauth.authentication' configuration for use in samlAuthentication.php.
 *
 * @param $form
 * @param $form_state
 *
 * @return void
 */
function samlauth_extensions_form_submit($form, $form_state) {
  $save = FALSE;
  $config = \Drupal::configFactory()->getEditable("samlauth.authentication");
  if ($form_state->hasValue("roles")) {
    $newval = $form_state->getValue("roles");
    $existingval = $config->get("roles") ?: [];
    if ($newval !== $existingval) {
      $config->set("roles", $newval);
      $save = TRUE;
    }
  }
  if ($form_state->hasValue("realname")) {
    $newval = $form_state->getValue("realname");
    $existingval = $config->get("realname") ?: "";
    if ($newval !== $existingval) {
      $config->set("realname", $newval);
      $save = TRUE;
    }
  }
  if ($save) {
    $config->save();
  }
}
