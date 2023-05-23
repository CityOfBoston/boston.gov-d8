<?php

namespace Drupal\bos_email\Templates;

use Drupal\bos_email\CobEmail;
use Drupal\bos_email\Controller\PostmarkAPI;
use Drupal\bos_email\EmailTemplateCss;
use Drupal\bos_email\EmailTemplateInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;

/**
 * Template class for Postmark API.
 */
class Contactform extends EmailTemplateCss implements EmailTemplateInterface {

  const ENCODE = 0;
  const DECODE = 1;

  /**
   * Domain to be used as the sender.
   */
  const OUTBOUND_DOMAIN = "web-inbound.boston.gov";

  /**
   * @inheritDoc
   */
  public static function templatePlainText(&$emailFields): void {

    $cobdata = &$emailFields["postmark_data"];
    $msg = strip_tags($emailFields["message"]);

    if (empty($emailFields["TemplateID"]) && empty($emailFields["template_id"])) {
      $text = "-- REPLY ABOVE THIS LINE -- \n\n";
      $text .= "{$msg}\n\n";
      $text .= "-------------------------------- \n";
      $text .= "This message was sent using the contact form on Boston.gov.";
      $text .= " It was sent by {$emailFields["name"]} from {$emailFields["from_address"]}.";
      $text .= " It was sent from {$emailFields["url"]}.\n\n";
      $text .= "-------------------------------- \n";
      $cobdata->setField("TextBody", $text);
    }
    else {
      // we are using a template
      $cobdata->delField("TextBody");
      $cobdata->setField("TemplateID", $emailFields['TemplateID']);
      $cobdata->setField("TemplateModel", [
        "subject" => $emailFields["subject"],
        "TextBody" => $msg,
        "ReplyTo" => $emailFields["from_address"],
      ]);
      $emailFields["useHtml"] = 0;
    }

  }

  /**
   * @inheritDoc
   */
  public static function templateHtmlText(&$emailFields): void {

    if (empty($emailFields["TemplateID"]) && empty($emailFields["template_id"])) {

      $cobdata = &$emailFields["postmark_data"];

      $msg = Html::escape(Xss::filter($emailFields["message"]));
      $msg = str_replace("\n", "<br>", $msg);

      $html = "<br>----- REPLY ABOVE THIS LINE ----- <br><br>";
      $html .= "<div style='background-color:#eeeeee;color:#222;padding:5px 15px;border-left: 15px #288BE4 solid;'>{$msg}</div>";
      $html .= "<hr>";
      $html .= "<table style='border-spacing:10px;'><tr><td><img src='https://patterns.boston.gov/images/public/seal.png' height='50'></td>";
      $html .= "<td>This message was sent using the contact form on Boston.gov.<br>";
      $html .= " It was sent by <b>{$emailFields["name"]}</b> from {$emailFields["from_address"]}.<br>";
      $html .= " It was sent from {$emailFields["url"]}.</td>";
      $html .= "</tr></table>";
      $html .= "<hr>";

      $cobdata->setField("HtmlBody", $html);

    }

  }

  /**
   * @inheritDoc
   */
  public static function templateFormatEmail(array &$emailFields): void {

    $cobdata = &$emailFields["postmark_data"];
    $cobdata->setField("Tag", "Contact Form");

    $cobdata->setField("postmark_endpoint", $emailFields["postmark_endpoint"] ?: PostmarkAPI::POSTMARK_DEFAULT_ENDPOINT);

    self::templatePlainText($emailFields);
    if (!empty($emailFields["useHtml"])) {
      self::templateHtmlText($emailFields);
    }

    // Create a hash of the original poster's email
    $hashemail = self::encodeEmail($emailFields["from_address"]);
    $cobdata->setField("Metadata", [
      "opmail" => self::hashText($emailFields["from_address"], self::ENCODE)
    ]);

    $cobdata->setField("To", $emailFields["to_address"]);
    $cobdata->setField("From", "Boston.gov Contact Form <{$hashemail}>");
    $cobdata->setField("ReplyTo", $emailFields["from_address"]);
    isset($emailFields["name"]) && $cobdata->setField("ReplyTo", "{$emailFields["name"]}<{$emailFields["from_address"]}>");
    !empty($emailFields['cc']) && $cobdata->setField("Cc", $emailFields['cc']);
    !empty($emailFields['bcc']) && $cobdata->setField("Bcc", $emailFields['bcc']);
    $cobdata->setField("Subject", $emailFields["subject"]);
    !empty($emailFields['headers']) && $cobdata->setField("Headers", $emailFields['headers']);
    !empty($emailFields['tag']) && $cobdata->setField("Tag", $emailFields['tag']);

    if (empty($emailFields["TemplateID"])  && empty($emailFields["template_id"])) {
      // Remove redundant fields
      $cobdata->delField("TemplateModel");
      $cobdata->delField("TemplateID");
    }
    else {
      // An email template is to be used.
      $cobdata->setField("postmark_endpoint", PostmarkAPI::POSTMARK_TEMPLATE_ENDPOINT);
      $cobdata->delField("TextBody");
      $cobdata->delField("Subject");
      $cobdata->delField("HtmlBody");
    }

  }

  /**
   * @inheritDoc
   */
  public static function incoming(array &$emailFields): void {

//    if ($emailFields["postmark_endpoint"]->getField("server") == "contactform"
//      && str_contains($emailFields["OriginalRecipient"], "@web-inbound.boston.gov")) {
//      $server = PostmarkAPI::AUTORESPONDER_SERVERNAME;
//    }

    // Find the original recipient
    $original_recipient = self::decodeEmail($emailFields["OriginalRecipient"]);

    // Create the email.
    $cobdata = &$emailFields["postmark_data"];
    $cobdata->setField("To", $original_recipient);
    $cobdata->setField("From", "contactform@boston.gov");
    $cobdata->setField("Subject", $emailFields["Subject"]);
    $cobdata->setField("HtmlBody", $emailFields["HtmlBody"]);
    $cobdata->setField("TextBody", $emailFields["TextBody"]);
    $cobdata->setField("postmark_endpoint", PostmarkAPI::POSTMARK_DEFAULT_ENDPOINT);
    // Select Headers
    self::processHeaders($cobdata, $emailFields["Headers"]);

    // Remove redundant fields
    $cobdata->delField("TemplateModel");
    $cobdata->delField("TemplateID");

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

  private static function encodeEmail(string $email): string {
    $hash = self::hashText($email, self::ENCODE);
    return $hash . "@" . self::OUTBOUND_DOMAIN;
  }

  private static function decodeEmail(string $email): string {

    if (str_contains($email, self::OUTBOUND_DOMAIN)) {

      $original_recipient = explode("@", $email)[0];
      $original_recipient = self::hashText($original_recipient, self::DECODE);

      // Verify the original recipient
      if (preg_match('/[^A-Za-z0-9_@!#~$%=\*\^\+\-\.]/', $original_recipient) == 1) {
        // This did not decode well, it has chars we do not expect, so probably
        // was not a base64_encoded string originally.
        $original_recipient = "david.upton@boston.gov";
      }

      return $original_recipient;

    }

    return $email;

  }

  private static function hashText(string $text, int $flag = self::ENCODE) {
    switch ($flag) {
      case self::ENCODE:
        return base64_encode($text);
        break;
      case self::DECODE:
        return base64_decode($text);
        break;
    }
  }

  public static function processHeaders(CobEmail &$email, $headers) {
    $keep = [
      "Message-ID",
      "References",
      "In-Reply-To",
      "X-Auto-Response-Suppress",
      "auto-submitted",
      "MIME-Version",
      "X-OriginatorOrg",
    ];
    $_headers = [];
    foreach($headers as $header) {
      if (in_array($header->Name, $keep)) {
        $_headers[] = $header;
      }
    }
    if (!empty($_headers)) {
      $email->setField("Headers", $_headers);
    }
  }

}
