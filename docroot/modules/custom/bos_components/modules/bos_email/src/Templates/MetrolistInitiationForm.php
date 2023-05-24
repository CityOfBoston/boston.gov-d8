<?php

namespace Drupal\bos_email\Templates;

use Drupal\bos_email\Controller\PostmarkAPI;
use Drupal\bos_email\EmailTemplateInterface;
use Drupal\bos_email\EmailTemplateCss;

/**
 * Template class for Postmark API.
 */
class MetrolistInitiationForm extends EmailTemplateCss implements EmailTemplateInterface {

  /**
   * @inheritDoc
   */
  public static function templatePlainText(&$emailFields):void {

    $cobdata = &$emailFields["postmark_data"];

    $plain_text = trim($emailFields["message"]);
    $plain_text = html_entity_decode($plain_text);
    // Replace html line breaks with carriage returns
    $plain_text = str_ireplace(["<br>", "</p>", "</div>"], ["\n", "</p>\n", "</div>\n"], $plain_text);
    $plain_text = strip_tags($plain_text);

    $plain_text = "
Click the link below to submit information about your available property or access past listings.
IMPORTANT: Do not reuse this link. If you need to submit listings for additional properties, please request a new form.\n
Metrolist Listing Form: {$plain_text} \n
Questions? Feel free to email metrolist@boston.gov\n
--------------------------------
This message was requested from " . urldecode($emailFields['url']) . ".
 The request was initiated by {$emailFields['to_address']}.
--------------------------------
";

    $cobdata->setField("TextBody", $plain_text);

  }

  /**
   * @inheritDoc
   */
  public static function templateHtmlText(&$emailFields):void {

    $cobdata = &$emailFields["postmark_data"];

    $html = trim($emailFields["message"]);
    // Replace carriage returns with html line breaks
    $html = str_ireplace(["\n", "\r\n"], ["<br>"], $html);

    // $emailFields["message"] received the link url. We can change it into a
    // button here.
    $html = "<a class=\"button\" href=\"{$html}\" tabindex=\"-1\" target=\"_blank\">
               Launch metrolist listing form
             </a>";
    $form_url = urldecode($emailFields['url']);

    $html = "
<img class='ml-icon' height='34' src='https://assets.boston.gov/icons/metrolist/metrolist-logo_email.png' />\n
<p class='txt'>Click the button below to submit information about your available property or access past listings.</p>\n
<p class='txt'><span class='txt-b'>Important: Do not reuse this link.</span> If you need to submit listings for additional properties, please request a new form.</p>\n
<p class='txt'>{$html}</p>\n
<p class='txt'>Questions? Feel free to email metrolist@boston.gov</p>\n
<p class='txt'><br /><table class='moh-signature' cellpadding='0' cellspacing='0' border='0'><tr>\n
<td><a href='https://content.boston.gov/departments/housing' target='_blank'>
  <img height='34' class='ml-icon' src='https://assets.boston.gov/icons/metrolist/neighborhood_development_logo_email.png' />
  </a></td>\n
<td>Thank you<br /><span class='txt-b'>The Mayor's Office of Housing</span></td>\n
</tr></table></p>\n
<hr>\n
<p class='txt'>This message was requested from the <a target='_blank' href='{$form_url}'>Metrolist Listing</a> service on Boston.gov.</p>\n
<p class='txt'>The request was initiated by {$emailFields['to_address']}.</p>
<hr>\n
";

    // check for complete-ness of html
    if (!str_contains($html, "<html")) {
      $html = "<html>\n<head></head>\n<body>{$html}</body>\n</html>";
    }
    // Add a title
    $html = preg_replace(
      "/\<\/head\>/i",
      "<title>Metrolist Listing Link</title>\n</head>",
      $html);
    // Add in the css
    $css = self::getCss();
    $html = preg_replace(
      "/\<\/head\>/i",
      "{$css}\n</head>",
      $html);

    $cobdata->setField("HtmlBody", $html);

  }

  /**
   * @inheritDoc
   */
  public static function templateFormatEmail(array &$emailFields): void {

    $cobdata = &$emailFields["postmark_data"];
    $cobdata->setField("Tag", "metrolist form initiation");

    $cobdata->setField("postmark_endpoint", $emailFields["postmark_endpoint"] ?: PostmarkAPI::POSTMARK_DEFAULT_ENDPOINT);

    self::templatePlainText($emailFields);
    if (!empty($emailFields["useHtml"])) {
      self::templateHtmlText($emailFields);
    }

    // Create a hash of the original poster's email
    $cobdata->setField("To", $emailFields["to_address"]);
    $cobdata->setField("From", $emailFields["from_address"]);
    !empty($emailFields['cc']) && $cobdata->setField("Cc", $emailFields['cc']);
    !empty($emailFields['bcc']) && $cobdata->setField("Bcc", $emailFields['bcc']);
    $cobdata->setField("Subject", $emailFields["subject"]);
    !empty($emailFields['headers']) && $cobdata->setField("Headers", $emailFields['headers']);

    // Remove redundant fields
    $cobdata->delField("TemplateModel");
    $cobdata->delField("TemplateID");

  }

  /**
   * @inheritDoc
   */
  public static function getCss():string {
    $css = parent::getCss();
    return "<style>
        {$css}
        .ml-icon {
          height: 34px;
        }
        table.moh-signature {
          border: none;
          border-collapse: collapse;
        }
        table.moh-signature tr,
        table.moh-signature td {
          padding: 3px;
          border-collapse: collapse;
        }
      </style>";
  }

  /**
   * @inheritDoc
   */
  public static function honeypot(): string {
    // TODO: Implement honeypot() method.
    return "";
  }

  /**
   * @inheritDoc
   */
  public static function postmarkServer(): string {
    return "metrolist";
  }

  /**
   * @inheritDoc
   */
  public static function incoming(array &$emailFields): void {
    // TODO: Implement incoming() method.
  }

}
