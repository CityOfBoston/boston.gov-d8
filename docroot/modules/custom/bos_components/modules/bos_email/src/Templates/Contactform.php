<?php

namespace Drupal\bos_email\Templates;

use Drupal\bos_email\EmailTemplateCss;
use Drupal\bos_email\EmailTemplateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;

/**
 * Template class for Postmark API.
 */
class Contactform extends EmailTemplateCss implements EmailTemplateInterface {

  /**
   * @inheritDoc
   */
  public static function templatePlainText(&$emailFields): void {

    // Create an anonymous sender
    $rand = substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, 12);
    $emailFields["modified_from_address"] = "Boston.gov Contact Form <{$rand}@contactform.boston.gov>";

    if (isset($emailFields["name"])) {
      $emailFields["ReplyTo"]  = "{$emailFields["name"]}<{$emailFields["from_address"]}>";
    }

    // Create the plain text body.
    if (empty($emailFields["TemplateID"]) && empty($emailFields["template_id"])) {
      $emailFields["TextBody"] = "-- REPLY ABOVE THIS LINE -- \n\n";
      $emailFields["TextBody"] .= "{$emailFields["message"]}\n\n";
      $emailFields["TextBody"] .= "-------------------------------- \n";
      $emailFields["TextBody"] .= "This message was sent using the contact form on Boston.gov.";
      $emailFields["TextBody"] .= " It was sent by {$emailFields["name"]} from {$emailFields["from_address"]}.";
      $emailFields["TextBody"] .= " It was sent from {$emailFields["url"]}.\n\n";
      $emailFields["TextBody"] .= "-------------------------------- \n";
    }
    else {
      // If we are using a template, then there is no html version of the body.
      $emailFields["useHtml"] = 0;
      if (isset($emailFields["HtmlBody"])) {
        unset($emailFields["HtmlBody"]);
      }

      $emailFields["postmark_endpoint"] = "https://api.postmarkapp.com/email/withTemplate";
      $emailFields["TemplateID"] = $emailFields["template_id"];
      $emailFields["TextBody"] = strip_tags($emailFields["message"]);
      $emailFields["TemplateModel"] = [
        "subject" => $emailFields["subject"],
        "TextBody" => strip_tags($emailFields["message"]),
        "ReplyTo" => $emailFields["from_address"],
      ];
    }

    $emailFields["tag"] = "Contact Form";

  }

  /**
   * @inheritDoc
   */
  public static function templateHtmlText(&$emailFields): void {

    $msg = Html::escape(Xss::filter($emailFields["message"]));
    $msg = str_replace("\n", "<br>", $msg);
    $emailFields["HtmlBody"] = "<br>----- REPLY ABOVE THIS LINE ----- <br><br>";
    $emailFields["HtmlBody"] .= "<div style='background-color:#eeeeee;color:#222;padding:5px 15px;border-left: 15px #288BE4 solid;'>{$msg}</div>";
    $emailFields["HtmlBody"] .= "<hr>";
    $emailFields["HtmlBody"] .= "<table style='border-spacing:10px;'><tr><td><img src='https://patterns.boston.gov/images/public/seal.png' height='50'></td>";
    $emailFields["HtmlBody"] .= "<td>This message was sent using the contact form on Boston.gov.<br>";
    $emailFields["HtmlBody"] .= " It was sent by <b>{$emailFields["name"]}</b> from {$emailFields["from_address"]}.<br>";
    $emailFields["HtmlBody"] .= " It was sent from {$emailFields["url"]}.</td>";
    $emailFields["HtmlBody"] .= "</tr></table>";
    $emailFields["HtmlBody"] .= "<hr>";

    // Make sure we have a clean text version.
    if (!empty($emailFields["TextBody"])) {
      $emailFields["TextBody"] = strip_tags($emailFields["TextBody"]);
    }
  }

  /**
   * @inheritDoc
   */
  public static function honeypot(): string {
    return "contact";
  }

  /**
   * @inheritDoc
   */
  public static function postmarkServer(): string {
    return "contactform";
  }

}
