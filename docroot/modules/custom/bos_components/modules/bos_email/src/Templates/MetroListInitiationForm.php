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
Click the link below to submit information about your available property or access past listings.\n
IMPORTANT: Do not reuse this link. If you need to submit listings for additional properties, please request a new form.\n
Metrolist Listing Form: ${plain_text} \n\n
Questions? Feel free to email metrolist@boston.gov\n
-------------------------------- \n
This message was sent using the Metrolist Listing form on Boston.gov.\n
 The request was initiated by ${emailFields['name']} from ${emailFields['to_address']} from the page at " . urldecode($emailFields['url']) . ".\n\n
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

    $icon = "<img class='ml-icon' height='34' src='https://assets.boston.gov/icons/metrolist/metrolist-logo.png'></img>";

    // $emailFields["message"] received is a url. So we can change it into a
    // button here.
    $html = "<a class=\"button\" href=\"${html}\" tabindex=\"-1\" target=\"_blank\">
               Launch metrolist listing form
             </a>";

    $html = "
${icon}
<p class='txt'>Click the button below to submit information about your available property or access past listings.</p>\n
<p class='txt'><span class='txt-b'>Important: Do not reuse this link.</span> If you need to submit listings for additional properties, please request a new form.</p>\n
<p class='txt'>${html}</p>\n
<p class='txt'>Questions? Feel free to email metrolist@boston.gov</p>\n
<hr>\n
<p class='txt'>This message was sent using the Metrolist Listing form on Boston.gov.</p>\n
<p class='txt'>The request was initiated by ${emailFields['name']} from ${emailFields['to_address']} from the page at " . urldecode($emailFields['url']) . ".</p>
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
    return "<style type='text/css'>
        ${css}
        .ml-icon {
          height: 34px;
        }
      </style>";
  }

}
