<?php

namespace Drupal\bos_email\Templates;

use Drupal\bos_email\Controller\PostmarkAPI;
use Drupal\bos_email\EmailTemplateCss;
use Drupal\bos_email\EmailTemplateInterface;

/**
 * Template class for Postmark API.
 */
class Registry extends EmailTemplateCss implements EmailTemplateInterface {

  /**
   * @inheritDoc
   */
  public static function templateFormatEmail(array &$emailFields): void {

    /** @var $cobdata \Drupal\bos_email\CobEmail */
    $cobdata = &$emailFields["postmark_data"];
    $cobdata->setField("postmark_endpoint", PostmarkAPI::POSTMARK_TEMPLATE_ENDPOINT);

    // Set up the Postmark template.
    $cobdata->setField("TemplateID", $emailFields["template_id"]);
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
    isset($emailFields["name"]) && $cobdata->setField("ReplyTo", "{$emailFields["name"]}<{$emailFields["from_address"]}>");
    !empty($emailFields['cc']) && $cobdata->setField("Cc", $emailFields['cc']);
    !empty($emailFields['bcc']) && $cobdata->setField("Bcc", $emailFields['bcc']);

    // Create a relevant tag.
    if (str_contains($emailFields["subject"], "Birth")) {
      $cobdata->setField("Tag", "Birth Certificate");
    }
    elseif (str_contains($emailFields["subject"], "Intention")) {
      $cobdata->setField("Tag", "Marriage Intention");
    }
    elseif (str_contains($emailFields["subject"], "Death")) {
      $cobdata->setField("Tag", "Death Certificate");
    }

  }

  /**
   * @inheritDoc
   */
  public static function templatePlainText(&$emailFields): void {
  }

  /**
   * @inheritDoc
   */
  public static function templateHtmlText(&$emailFields): void {
  }

  /**
   * @inheritDoc
   */
  public static function honeypot(): string {
    return "";
  }

  /**
   * @inheritDoc
   */
  public static function postmarkServer(): string {
    return "registry";
  }

  /**
   * @inheritDoc
   */
  public static function incoming(array &$emailFields): void {
    // TODO: Implement incoming() method.
  }

}
