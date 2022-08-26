<?php

namespace Drupal\bos_email\Templates;

use Drupal\bos_email\Controller\EmailControllerBase;
use Drupal\Core\Controller\ControllerBase;

/**
 * Template class for Postmark API.
 */
class MetroListInitiationForm extends ControllerBase implements EmailControllerBase {

  /**
   * Template for plain text message.
   *
   * @param string $message_txt
   *   The message sent by the user.
   * @param string $name
   *   The name supllied by the user.
   * @param string $from_address
   *   The from address supplied by the user.
   * @param string $url
   *   The page url from where form was submitted.
   */
  public static function templatePlainText(&$emailFields) {

    $plain_text = trim($emailFields["message"]);
    $plain_text = html_entity_decode($plain_text);
    // Replace html line breaks with carriage returns
    $plain_text = str_ireplace(["<br>", "</p>", "</div>"], ["\n", "</p>\n", "</div>\n"], $plain_text);
    $plain_text = strip_tags($plain_text);

    $emailFields["TextBody"] = "
-- REPLY ABOVE THIS LINE -- \n\n
${plain_text} \n\n
-------------------------------- \n
This message was sent using the Metrolist Listing form on Boston.gov.\n
 It was sent by ${emailFields['name']} from ${emailFields['from_address']}.\n
 It was initiated on the page at " . urldecode($emailFields['url']) . ".\n\n
-------------------------------- \n
";
  }

  /**
   * Template for html message.
   *
   * @param string $message_html
   *   The HTML message sent by the user.
   * @param string $name
   *   The name supllied by the user.
   * @param string $from_address
   *   The from address supplied by the user.
   * @param string $url
   *   The page url from where form was submitted.
   */
  public static function templateHtmlText(&$emailFields) {

    $html = trim($emailFields["message"]);
    // Replace carriage returns with html line breaks
    $html = str_ireplace(["\n", "\r\n"], ["<br>"], $html);

    $html = "
<hr>REPLY ABOVE THIS LINE<br>\n
${html}<br>\n
<hr>\n
This message was sent using the contact form on Boston.gov.<br>\n
It was sent by ${emailFields['name']} from ${emailFields['from_address']}.<br>\n
It was sent from ${emailFields['url']} <br>\n
<hr>\n
";

    // check for complete-ness of html
    if (stripos($html, "<html") === FALSE) {
      $emailFields["HtmlBody"] = "<html>\n<head></head>\n<body>${html}</body>\n</html>";
    }
    // Add a title
    $emailFields["HtmlBody"] = preg_replace(
      "/\<\/head\>/i",
      "<title>Metrolist Listing Link</title>\n</head>",
      $emailFields["HtmlBody"]);
    // Add in the css
    $css = self::_css();
    $emailFields["HtmlBody"] = preg_replace(
      "/\<\/head\>/i",
      "${css}\n</head>",
      $emailFields["HtmlBody"]);

  }

  /**
   * Fetch the default email css (can extend it here if needed.
   *
   * @return string
   */
  public static function _css() {
    $css = EmailTemplateCss::getCss();
    return "<style type='text/css'>${css}</style>";
  }

}
