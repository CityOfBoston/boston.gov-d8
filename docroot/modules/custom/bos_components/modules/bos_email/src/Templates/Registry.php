<?php

namespace Drupal\bos_email\Templates;

use Drupal\bos_email\EmailTemplateCss;
use Drupal\bos_email\EmailTemplateInterface;

/**
 * Template class for Postmark API.
 */
class Registry extends EmailTemplateCss implements EmailTemplateInterface {

  /**
   * @inheritDoc
   */
  public static function templatePlainText(&$emailFields): void {

    $emailFields["postmark_endpoint"] = "https://api.postmarkapp.com/email/withTemplate";
    $emailFields["TemplateID"] = $emailFields["template_id"];
    $emailFields["TextBody"] = $emailFields["message"];

    $emailFields["TemplateModel"] = [
      "subject" => $emailFields["subject"],
      "TextBody" => $emailFields["message"],
      "ReplyTo" => $emailFields["from_address"]
    ];

    if (str_contains($emailFields["subject"], "Birth")) {
      $emailFields["tag"] = "Birth Certificate";
    }
    elseif (str_contains($emailFields["subject"], "Intention")) {
      $emailFields["tag"] = "Marriage Intention";
    }
    elseif (str_contains($emailFields["subject"], "Death")) {
      $emailFields["tag"] = "Death Certificate";
    }

  }

  /**
   * @inheritDoc
   */
  public static function templateHtmlText(&$emailFields): void {
    // Registry form does not have an html version.
    if (!empty($emailFields["HtmlBody"])) {
      unset ($emailFields["HtmlBody"]);
    }
    $emailFields["useHtml"] = "0";
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
}
