<?php

namespace Drupal\bos_email\Services;

use Boston;
use Drupal;
use Drupal\bos_email\EmailServiceInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Site\Settings;
use Drupal\bos_email\CobEmail;

/**
 * Postmark class for API.
 */
class DrupalService implements EmailServiceInterface {

  const DEFAULT_ENDPOINT = 'internal';
  const TEMPLATE_ENDPOINT = 'internal';

  /**
   * @var array Retains the request
   */
  protected array $request;

  /**
   *  An associative array created from the most recent CuRL transaction and
   *  which can be extended by any service extending this class.
   *
   * @var array
   */
  protected array $response;

  public null|string $error;

  /**
   * @inheritDoc
   */
  public function id():string {
    return "bos_email.DrupalService";
  }

  /**
   * @inheritDoc
   */
  public function updateEmailObject(CobEmail &$email_object): void {
    $email_object->setField("endpoint", self::DEFAULT_ENDPOINT);
    $email_object->delField("TemplateID");
    $email_object->delField("TemplateModel");
  }

  /**
   * Send the email via Postmark.
   *
   * @param \Drupal\bos_email\CobEmail $mailobj The email object
   *
   * @return array
   */
  public function sendEmail(array $item):bool {

    // Check if we are sending out emails.
    $config = Drupal::configFactory()->get("bos_email.settings");
    if (!$config->get("enabled")) {
      $this->error = "Emailing temporarily suspended for all emails";
      Drupal::logger("bos_email:DrupalService")->error($this->error);
      return FALSE;
    }
    elseif ($item["server"] && !$config->get(strtolower($item["server"]))["enabled"]) {
      $this->error = "Emailing temporarily suspended for {$item["server"]} emails.";
      Drupal::logger("bos_email:DrupalService")->error($this->error);
      return FALSE;
    }

    /**
     * @var \Drupal\Core\Mail\MailManager $mailManager
     */
    try {

      // Send the email.
      $item["_error_message"] = "";

      $mailManager = Drupal::service('plugin.manager.mail');
      $sent = $mailManager->mail("bos_email", $item["server"] , $item["To"], "en", $item, $item["ReplyTo"], TRUE);

      $this->response = [
        "sent" => $sent ? "True" : "False",
      ];

      if (!$sent || !$sent["result"]) {
        if (!empty($params["_error_message"])) {
          $this->response["error"] = $params["_error_message"];
          throw new \Exception($params["_error_message"]);
        }
        else {
          $this->response["error"] = "Error sending email";
          throw new \Exception("Error sending email.");
        }
      }

      return TRUE;

    }
    catch (\Exception $e) {
      $this->error = $e->getMessage();
      $this->response["error"] = $this->error;
      if (Boston::is_local()) {
        Drupal::logger("bos_email:DrupalService")
          ->info("Queued {$item["server"]}");
        return FALSE;
      }

    }

  }

  /**
   * @inheritDoc
   */
  public function getVars(): array {

    $postmark_env = [];
    if (getenv('POSTMARK_SETTINGS')) {
      $get_vars = explode(",", getenv('POSTMARK_SETTINGS'));
      foreach ($get_vars as $item) {
        $json = explode(":", $item);
        if (!empty($json[0]) && !empty($json[1])) {
          $postmark_env[$json[0]] = $json[1];
        }
      }
    }
    else {
      $postmark_env = Settings::get('postmark_settings') ?? [];
    }

    return $postmark_env;
  }

  /**
   * Format the body part of the email using twig templates.
   * The idea here is to create Markup in the $message["body"] field.
   *
   * Called from bos_email.module bos_email_mail().
   *
   * @param array $params the Drupal mail params object
   * @param array $message the Drupal mail message object
   *
   * @return void
   */
  public static function renderEmail(array &$params, array &$message):void {

    // Map in the default values
    $message["from"] = $params["From"];
    $message["subject"] = $params["Subject"];
    $message["reply-to"] = $params["ReplyTo"];
    !empty($params["Cc"]) && $message['headers']["CC"] = $params["Cc"];
    !empty($params["Bcc"]) && $message['headers']["BCC"] = $params["Bcc"];
    $message['headers']['Content-Type'] = 'text/html; charset=UTF-8; format=flowed; delsp=yes';

    if (!empty($params["useHTML"]) && $params["useHTML"] == 1) {
      // The $params["message"] field is already in HTML, so just use it.
      // No twig templating required for the body.
      if (is_string($params["message"])) {
        $params["message"] = Markup::create($params["message"]);
      }
      $message["body"] = $params["message"];
      return;
    }

    // Get the template name.
    $path = Drupal::service('extension.list.module')
      ->get('bos_email')
      ->getPath();
    $twig_service = Drupal::service('twig');

    // Try to find the generic template
    $template_name = "{$path}/templates/{$params["server"]}.body.html.twig";
    if (file_exists($template_name)) {
      $rendered_template = $twig_service->render($template_name, $params);
      $params['message'] = Markup::create($rendered_template);
      $message["body"] = $params["message"];
      return;
    }

    // Create a variant field which the template can use to determine body
    // copy/format.
    if (!empty($params["Tag"])) {
      $params['variant'] = "{$params["server"]}.body.{$params["Tag"]}";
      // Try to find the processor-specific template
      $template_name = "{$path}/templates/{$params["variant"]}.html.twig";
      if (file_exists($template_name)) {
        $rendered_template = $twig_service->render($template_name, $params);
        $params['message'] = Markup::create($rendered_template);
        $message["body"] = $params["message"];
        return;
      }
    }

    // Use a generic body template.
    $template_name = "{$path}/templates/default.body.html.twig";
    $rendered_template = $twig_service->render($template_name, $params);
    $params['message'] = Markup::create($rendered_template);
    $message["body"] = $params["message"];

  }

  public function response(): array {
    return $this->response ?? [];
  }

}
