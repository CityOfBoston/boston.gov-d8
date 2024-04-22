<?php

namespace Drupal\bos_email\Templates;

use Drupal\bos_email\Controller\EmailController;
use Drupal\bos_email\EmailServiceInterface;
use Drupal\bos_email\EmailTemplateBase;
use Drupal\bos_email\EmailTemplateInterface;
use Exception;

/**
 * Template class for Postmark API.
 */
class Sanitation extends EmailTemplateBase implements EmailTemplateInterface {

  /**
   * @inheritDoc
   */
  public static function formatOutboundEmail(array &$emailFields): void {

    /** @var $cobdata \Drupal\bos_email\CobEmail */
    $cobdata = &$emailFields["email_object"];
    $cobdata->setField("endpoint", EmailController::POSTMARK_TEMPLATE_ENDPOINT);

    // Set up the Postmark template.
    $template_id = \Drupal::config("bos_email.settings")->get("sanitation.template");
    $cobdata->setField("TemplateID", $template_id);
    $cobdata->setField("TemplateModel", [
      "subject" => $emailFields["subject"],
      "TextBody" => $emailFields["message"],
      "ReplyTo" => $emailFields["from_address"]
    ]);
    $cobdata->delField("HtmlBody");
    $cobdata->delField("TextBody");
    $cobdata->delField("Subject");

    // Set general email fields.
    $cobdata->setField("To", $emailFields["to_address"]);
    $cobdata->setField("From", $emailFields["from_address"]);
    $cobdata->setField("ReplyTo", $emailFields["from_address"]);

    $cobdata->setField("Tag", $emailFields["type"]);

    // is this to be scheduled?
    if (!empty($emailFields["senddatetime"])) {
      try {
        $senddatetime = strtotime($emailFields["senddatetime"]);
        $cobdata->setField("senddatetime", $senddatetime);
      }
      catch (Exception $e) {
        $cobdata->delField("senddatetime");
      }
    }
    else {
      $cobdata->delField("senddatetime");
    }

  }

  /**
   * @inheritDoc
   */
  public static function templatePlainText(&$emailFields): void {
    // Only use templates ATM.
  }

  /**
   * @inheritDoc
   */
  public static function templateHtmlText(&$emailFields): void {
    // Only use templates ATM.
  }

  /**
   * @inheritDoc
   */
  public static function getHoneypotField(): string {
    return "";
  }

  /**
   * @inheritDoc
   */
  public static function getEmailService(): EmailServiceInterface {
    $config = \Drupal::service("config.factory")->get("bos_email.settings");
    $email_service = $config->get(self::getGroupID() . ".service");
    $email_service = "Drupal\\bos_email\\Services\\{$email_service}";
    return new $email_service;
  }

  /**
   * @inheritDoc
   */
  public static function getGroupID(): string {
    return "sanitation";
  }

  /**
   * @inheritDoc
   */
  public static function formatInboundEmail(array &$emailFields): void {
    // Not Used
  }

}
