<?php

namespace Drupal\bos_sql\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\user\Plugin\views\argument_default\CurrentUser;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

/**
 * Admin Settings form for bos_sql.
 *
 * @see https://developers.google.com/analytics/devguides/collection/protocol/v1/reference
 */

/**
 * Class DbconnectorSettingsForm.
 *
 * @package Drupal\bos_sql\Form
 */
class DbconnectorSettingsForm extends ConfigFormBase {

  const ENVAR_NAME = "DBCONNECTOR_SETTINGS";

  /**
   * @inheritDoc
   */
  protected function getEditableConfigNames() {
    return ["bos_sql.settings"];
  }

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return "bos_sql_config";
  }

  /**
   * Checks to see if an environment variable is set.
   *
   * @return bool Is the self:ENVAR_NAME environment variable set.
   */
  private static function isEnvarSet() {
    $env = getenv(self::ENVAR_NAME);
    return !($env === FALSE);
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $envname = self::ENVAR_NAME;

    if ($this::isEnvarSet()) {
      $config = json_decode(getenv($envname));
      $disabled = TRUE;
      $description = '<span class="form-item--error-message"><b>These values are not editable because they are set in an environment variable.</b></span>';
    }
    else {
      $config = $this->config('bos_sql.settings');
      $disabled = FALSE;
      $description = "There is no environment variable set, so the DBConnector is using the values below which are set in config (i.e the database) for this environment.<br>For production environments, it is recommended to set the variables in the {$envname} environment variable (see JSON builder below).";
    }

    // Adds in Actions and other form defaults.
    $form = parent::buildForm($form, $form_state);

    // Now customize the form.
    $form += [
      '#tree' => TRUE,
      'bos_sql' => [
        '#type' => 'fieldset',
        '#title' => 'DBConnector Configuration',
        '#description' => $description,
        '#description_display' => "before",
        '#collapsible' => FALSE,
        "host" => [
          '#type' => 'textfield',
          '#title' => t('DBConnector service location'),
          '#access' => $this->currentUser()->hasPermission('Configure DBConnector') ,
          '#description' => t('The hostname for the DBConnector service used by the bos_sql module.'),
          '#default_value' => $config->get('host') ?? 'https://dbconnector.boston.gov',
          '#disabled' => $disabled,
          '#required' => TRUE,
        ],
        "secure" => [
          '#type' => 'checkbox',
          '#title' => t('Verify the SSL Cert'),
          '#access' => $this->currentUser()->hasPermission('Configure DBConnector') ,
          '#description' => t('When checked a full, current SSL certificate will be required.'),
          '#default_value' => $config->get('secure') ?? TRUE,
          '#disabled' => $disabled,
        ],
        'apps' => [],
        'connections' => [
          '#type' => 'fieldset',
          '#title' => 'DBConnector Connections',
          '#description' => 'The following connections are being used by the DBConnector.',
          '#description_display' => "before",
          '#collapsible' => FALSE,
          'render' => [
            '#type' => 'fieldset',
            '#id' => 'edit-builder',
            '#title' => 'Environment Variable Builder',
            '#description' => 'Use this to build a json string using the active DBConnector configuration (i.e. as on this form).<br>NOTE: Environment Variable settings always override configuration.',
            '#description_display' => "before",
            '#collapsible' => FALSE,
            '#weight' => 100,

            'config' => [
              '#type' => 'textarea',
              '#title' => "Configuration (bos_sql.settings)",
              '#default_value' => json_encode($config->get()),
              '#disabled' => TRUE,
            ],
            'json' => [
              '#type' => 'textarea',
              '#title' => "{$envname}",
              '#default_value' => getenv($envname),
              '#disabled' => TRUE,
            ],
            'builder' => [
              '#type' => 'textarea',
              '#title' => "This form as JSON",
              '#description' => "NOTE: The environment variable you should paste this string into is {$envname}",
              '#default_value' => "click button below to populate"
            ],
            'create' => [
              "#type" => "button",
              "#button_type" => "button",
              '#value' => t('Extract JSON'),
              '#ajax' => [
                'callback' => '::JsonBuilder',
                'event' => 'click',
                'wrapper' => 'edit-builder',
                'progress' => [
                  'type' => 'throbber',
                  'message' => t('Building...'),
                ],
              ]
            ]
          ]

          // This array element will be updated by components that use the
          // bos_sql component using a hook_form_alter() function.
        ]
      ],
    ];

    // Disable the submit button if this form content is driven by an ENVAR.
    if ($this::isEnvarSet()) {
      $form["actions"]["submit"]["#disabled"] = TRUE;
      $form["actions"]["submit"]["#button_type"] = "button";
    }
    return $form;
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config_save = FALSE;
    $config = $this->config('bos_sql.settings');

    // Set the DBConnector setting, if eligible.
    if ($form["bos_sql"]["host"]["#disabled"] === FALSE) {
      if ($form_state->getValue("bos_sql")["host"] != $config->get("host")) {
        $config->set("host", $form_state->getValue("bos_sql")["host"]);
        $config_save = TRUE;
      }
      if ($form_state->getValue("bos_sql")["secure"] !== $config->get("secure")) {
        $config->set("secure", $form_state->getValue("bos_sql")["secure"]);
        $config_save = TRUE;
      }
    }

    // Set the apps when eligible.
    foreach(Element::children($form["bos_sql"]["connections"]["apps"]) as $appname) {
      if ($form["bos_sql"]["connections"]["apps"][$appname]["token"]["#disabled"] === FALSE) {
        $app = $form_state->getValue("bos_sql")["connections"]["apps"][$appname];
        $app_array = [
          "username" => $app["authentication"]["username"],
          "password" => $app["authentication"]["password"],
          "token" => $app["token"],
          "apiver" => $app["apiver"],
        ];
        if ($app_array != $config->get($appname)) {
          $config->set($appname, $app_array);
          $config_save = TRUE;
        }
      }

      // If something was updated, then save the configs.
      if ($config_save) {
        $config->save();
      }
    }


    parent::submitForm($form, $form_state);
  }

  /**
   * Create a form array with settings for this application.
   *
   * Always display values from the environment variable if there is a value
   * set for this appname.
   *
   * DBConnector provides connections to databases with the connection strings
   * and queries abstracted behind tokens so no connection details or query
   * info is transfered between the browser and the server.
   *
   * @param $form array (by ref) The form object to inject this app config to.
   * @param $appname  string The appname to inject.
   * @param $username string Username to authenicate for the token.
   * @param $password string Password to authenicate for the token.
   * @param $token string The token to use to identify the connection string.
   * @param string $apiver Which API versiopn to use (default: v1))
   *
   * @return void
   */
  public static function addConfig(&$form, $appname, $username, $password, $token, $apiver = "v1") {

    $envname = self::ENVAR_NAME;

    if (self::isEnvarSet()) {
      // There is no environment variable.
      // Use the username, password and token as provided in arguments.
      $disabled = FALSE;
    }
    else {
      // There is an envar set, see if the app is set in that envar
      $config = json_decode(getenv($envname));
      if (!empty($config[$appname])) {
        // It is, so read it and mark this as un-editable.
        // Use the username, password and token from the envar.
        $token = $config[$appname]["token"];
        $username = $config[$appname]["username"];
        $password = $config[$appname]["password"];
        $apiver = $config[$appname]["apiver"];
        $disabled = TRUE;
      }
      else {
        // Envar is set, but does not have the app defined.
        // Use the username, password and token as provided in arguments.
        $disabled = FALSE;
      }
    }

    $form["bos_sql"]["connections"]["apps"][$appname] = [
      '#type' => 'details',
      '#title' => $disabled ? "{$appname} (ENVAR)" : "{$appname} (Config)"  ,
      '#description' => $disabled ? "Found in {$envname} local environment variable" : "From bos_sql.settings/configuration",
      '#collapsible' => TRUE,
      "apiver" => [
        '#type' => 'textfield',
        '#title' => t('DBConnector API endpoint version'),
        '#access' => \Drupal::currentUser()->hasPermission('Configure DBConnector') ,
        '#description' => t('The API endpoint to use (defaults to v1).'),
        '#default_value' => $apiver,
        '#disabled' => $disabled,
        '#required' => TRUE,
      ],
      'token' => [
        '#type' => 'textfield',
        '#title' => t('Connectionstring Token'),
        '#access' => \Drupal::currentUser()->hasPermission('Configure DBConnector') ,
        '#description' => t('The DBConnector issued token for this connectionstring.'),
        '#required' => TRUE,
        '#default_value' => $token,
        '#disabled' => $disabled,
      ],
      'authentication' => [
        '#type' => 'fieldset',
        '#title' => "Authentication",
        '#collapsible' => TRUE,
        'username' => [
          '#type' => 'textfield',
          '#title' => t('DBConnector Username'),
          '#access' => \Drupal::currentUser()->hasPermission('Configure DBConnector') ,
          '#description' => t('The DBConnector account name to use this connectionstring.'),
          '#required' => TRUE,
          '#default_value' => $username,
          '#disabled' => $disabled,
        ],
        'password' => [
          '#type' => 'textfield',
          '#title' => t('DBConnector Password'),
          '#access' => \Drupal::currentUser()->hasPermission('Configure DBConnector') ,
          '#description' => t('The DBConnector password to use this connectionstring.'),
          '#required' => TRUE,
          '#default_value' => $password,
          '#disabled' => $disabled,
      ],
      ],
    ];
  }

  /**
   * Process the current config (i.e. which is showing on the form) and create
   * a json string which can be used as an envar on the deploy environment.
   *
   * @param array $form The form object
   * @param \Drupal\Core\Form\FormStateInterface $form_state The form_state obj
   *
   * @return array Response to a Drupal Ajax call.
   */
  public function JsonBuilder(array &$form, FormStateInterface $form_state) {
    $app_array = [
      "host" => $form["bos_sql"]["host"]["#value"],
      "secure" => $form["bos_sql"]["secure"]["#value"],
    ];
    foreach(Element::children($form["bos_sql"]["connections"]["apps"]) as $appname) {
      $app = $form["bos_sql"]["connections"]["apps"][$appname];
      $app_array[$appname] = [
        "username" => $app["authentication"]["username"]["#value"] ?? "",
        "password" => $app["authentication"]["password"]["#value"] ?? "",
        "token" => $app["token"]["#value"] ?? "",
      ];
    }
    $form["bos_sql"]["connections"]["render"]["builder"]["#value"] = json_encode($app_array);
    return $form["bos_sql"]["connections"]["render"];
  }

}
