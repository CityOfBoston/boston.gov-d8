<?php

namespace Drupal\bos_email\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class ConfigForm extends ConfigFormBase {

  /**
   * @inheritDoc
   */
  protected function getEditableConfigNames() {
    return ["bos_email.settings"];
  }

  /**
   * @inheritDoc
   */
  public function getFormId() {
    return "bos_email_config_settings";
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get(self::getEditableConfigNames()[0]);
    $form["bos_email"] = [
      '#type' => 'fieldset',
      '#title' => 'City of Boston Emailer',
      '#markup' => 'Fine-grain management for emails sent via City of Boston REST API.',
      "#tree" => TRUE,

      "service" => [
        "#type" => "select",
        '#title' => t('Current Email Service'),
        '#description' => t('The Email Service which is currently being used.'),
        "#options" => [
          "drupal" => "Drupal",
          "postmark" => "Postmark"
        ],
        '#default_value' => $config->get('service')
      ],

      "enabled" => [
        '#type' => 'checkbox',
        '#title' => t('Email Service Enabled'),
        '#description' => t('When selected, emails will be sent via the indicated email service. When unselected all emails are added to the queue.'),
        '#default_value' => $config->get('enabled'),
      ],
      "q_enabled" => [
        '#type' => 'checkbox',
        '#title' => t('Email-fail Queue Enabled'),
        '#description' => t('When selected, emails that the email service cannot process will be queued and there will be attempts to be resend. When unselected failed emails are discarded.'),
        '#default_value' => $config->get('q_enabled'),
      ],

      "alerts" => [
        '#type' => 'details',
        '#title' => 'Email Service monitoring',
        '#description' => 'Configure internal alert emails for issues which arise during operations.',
        '#open' => FALSE,

        "conditions" => [
          '#type' => 'fieldset',
          '#title' => 'Service Abuse',
          '#markup' => 'Emails will be sent to the recipient below when these potential abuse events occur:',
          '#collapsible' => FALSE,

          "token" => [
            '#type' => 'checkbox',
            '#title' => t('An incorrect API authentication token is provided. This could indicate a hacking attempt or attempted spam/relay abuse.'),
            '#default_value' => $config->get('alerts.token') ?? 0,
          ],
          "honeypot" => [
            '#type' => 'checkbox',
            '#title' => t('The honeypot field (a hidden input field a \'person\' cannot see or update) in a submitted form has data in it. This could indictate hacking attempt or attempted spam/relay abuse.'),
            '#default_value' => $config->get('alerts.honeypot') ?? 0,
          ],
          "recipient" => [
            '#type' => 'textfield',
            "#title" => "Email recipient",
            "#description" => "The email (or email group) to receive hardbounce alerts.",
            "#attributes" => ["placeholder" => "someone@boston.gov"],
            "#default_value" => $config->get('alerts.recipient') ?? "",
          ],
        ],

        "monitoring" => [
          '#type' => 'fieldset',
          '#title' => 'Service Monitoring',
          '#markup' => 'Emails will be sent to the recipient below when these unexpected service error events occur:',
          '#collapsible' => FALSE,
          "all" => [
            '#type' => 'checkbox',
            '#title' => t('All non-abuse failures.'),
            '#default_value' => $config->get('monitor.all') ?? 0,
          ],
          "recipient" => [
            '#type' => 'textfield',
            "#title" => "Email recipient",
            "#description" => "The email (or email group) to receive service error emails.",
            "#attributes" => ["placeholder" => "someone@boston.gov"],
            "#default_value" => $config->get('monitor.recipient') ?? "",
          ],
        ],

        "hb" => [
          '#type' => 'fieldset',
          '#title' => 'Hard Bounce / Recipient Supression',
          '#markup' => 'Emails will be sent to the recipient below when the following normal conditions occur:',
          '#collapsible' => FALSE,

          "hardbounce" => [
            '#type' => 'checkbox',
            '#title' => t('The intended recipient is suppressed by Email Service.'),
            '#default_value' => $config->get('hardbounce.hardbounce') ?? 0,
          ],
          "recipient" => [
            '#type' => 'textfield',
            "#title" => "Email recipient",
            "#description" => "The email (or email group) to receive hardbounce alerts.",
            "#attributes" => ["placeholder" => "someone@boston.gov"],
            "#default_value" => $config->get('hardbounce.recipient') ?? "",
          ],
        ],

        "footnote" => ['#markup' => "NOTE: These email alerts are sent via Drupal mail."],
      ],

      "contactform" => [
        '#type' => 'fieldset',
        '#title' => 'Contact Form',
        '#markup' => 'Emails from the main Contact Form - when clicking on email addresses on boston.gov.',
        '#collapsible' => FALSE,

        "enabled" => [
          '#type' => 'checkbox',
          '#title' => t('Contact Form email service enabled'),
          '#default_value' => $config->get('contactform.enabled'),
        ],
        "q_enabled" => [
          '#type' => 'checkbox',
          '#title' => t('Contact Form queue processing enabled'),
          '#description' => t('When selected, emails which initially fail to send are queued will be processed on each cron run.'),
          '#default_value' => $config->get('contactform.q_enabled'),
        ],
      ],

      "registry" => [
        '#type' => 'fieldset',
        '#title' => 'Registry Suite',
        '#markup' => 'Emails from the Registry App - confirmations.',
        '#collapsible' => FALSE,

        "enabled" => [
          '#type' => 'checkbox',
          '#title' => t('Registry email service enabled'),
          '#default_value' => $config->get('registry.enabled'),
        ],
        "q_enabled" => [
          '#type' => 'checkbox',
          '#title' => t('Registry queue processing enabled'),
          '#description' => t('When selected, emails which initially fail to send are queued will be processed on each cron run.'),
          '#default_value' => $config->get('registry.q_enabled'),
        ],
      ],

      "commissions" => [
        '#type' => 'fieldset',
        '#title' => 'Commissions App',
        '#markup' => 'Emails from the Commissions App.',
        '#collapsible' => FALSE,

        "enabled" => [
          '#type' => 'checkbox',
          '#title' => t('Commission email service enabled'),
          '#default_value' => $config->get('commissions.enabled'),
        ],
        "q_enabled" => [
          '#type' => 'checkbox',
          '#title' => t('Commissions queue processing enabled'),
          '#description' => t('When selected, emails which initially fail to send are queued will be processed on each cron run.'),
          '#default_value' => $config->get('commissions.q_enabled'),
        ],
      ],

      "metrolist" => [
        '#type' => 'fieldset',
        '#title' => 'Metrolist Listing Form',
        '#markup' => 'Emails sent from Metrolist Listing Form processes.',
        '#collapsible' => FALSE,

        "enabled" => [
          '#type' => 'checkbox',
          '#title' => t('Metrolist email service enabled'),
          '#default_value' => $config->get('metrolist.enabled'),
        ],
        "q_enabled" => [
          '#type' => 'checkbox',
          '#title' => t('Metrolist queue processing enabled'),
          '#description' => t('When selected, emails which initially fail to send are queued will be processed on each cron run.'),
          '#default_value' => $config->get('metrolist.q_enabled'),
        ],
      ],

      "sanitation" => [
        '#type' => 'fieldset',
        '#title' => 'Sanitation Email Services',
        '#markup' => 'Emails sent from Sanitation WebApp.',
        '#collapsible' => FALSE,

        "enabled" => [
          '#type' => 'checkbox',
          '#title' => t('Sanitation email service enabled'),
          '#default_value' => $config->get('sanitation.enabled'),
        ],
        "q_enabled" => [
          '#type' => 'checkbox',
          '#title' => t('Sanitation queue processing enabled'),
          '#description' => t('When selected, emails which initially fail to send are queued will be processed on each cron run.'),
          '#default_value' => $config->get('sanitation.q_enabled'),
        ],
        "sched_enabled" => [
          '#type' => 'checkbox',
          '#title' => t('Sanitation scheduled email processing enabled'),
          '#description' => t('When selected, scheduled emails are queued will be processed on each cron run.'),
          '#default_value' => $config->get('sanitation.sched_enabled'),
        ],
      ],

    ];
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    if ($input = $form_state->getUserInput()["bos_email"]) {
      $this->configFactory->getEditable(self::getEditableConfigNames()[0])
        ->set("service", $input["service"])
        ->set("enabled", $input["enabled"])
        ->set("q_enabled", $input["q_enabled"])
        ->set("contactform.enabled", $input["contactform"]["enabled"] ?? 0)
        ->set("contactform.q_enabled", $input["contactform"]["q_enabled"] ?? 0)
        ->set("registry.enabled", $input["registry"]["enabled"] ?? 0)
        ->set("registry.q_enabled", $input["registry"]["q_enabled"] ?? 0)
        ->set("commissions.enabled", $input["commissions"]["enabled"] ?? 0)
        ->set("commissions.q_enabled", $input["commissions"]["q_enabled"] ?? 0)
        ->set("metrolist.enabled", $input["metrolist"]["enabled"] ?? 0)
        ->set("metrolist.q_enabled", $input["metrolist"]["q_enabled"] ?? 0)
        ->set("sanitation.enabled", $input["sanitation"]["enabled"] ?? 0)
        ->set("sanitation.sched_enabled", $input["sanitation"]["sched_enabled"] ?? 0)
        ->set("sanitation.q_enabled", $input["sanitation"]["q_enabled"] ?? 0)
        ->set("alerts.recipient", $input["alerts"]["conditions"]["recipient"] ?? "")
        ->set("hardbounce.hardbounce", $input["alerts"]["hb"]["hardbounce"] ?? 0)
        ->set("hardbounce.recipient", $input["alerts"]["hb"]["recipient"] ?? "")
        ->set("alerts.recipient", $input["alerts"]["conditions"]["recipient"] ?? 0)
        ->set("alerts.token", $input["alerts"]["conditions"]["token"] ?? 0)
        ->set("alerts.honeypot", $input["alerts"]["conditions"]["honeypot"] ?? 0)
        ->set("monitor.recipient", $input["alerts"]["monitoring"]["recipient"] ?? 0)
        ->set("monitor.all", $input["alerts"]["monitoring"]["all"] ?? 0)
        ->save();
    }

//    parent::submitForm($form, $form_state);

  }

}
