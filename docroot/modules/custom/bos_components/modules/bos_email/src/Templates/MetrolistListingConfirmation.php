<?php

namespace Drupal\bos_email\Templates;

use Drupal\bos_email\Controller\EmailControllerBase;
use Drupal\Core\Controller\ControllerBase;

/**
 * Template class for Postmark API.
 */
class MetrolistListingConfirmation extends ControllerBase implements EmailControllerBase {

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

    //TODO: remove after testing
    $emailFields["bcc"] = "david.upton@boston.gov, james.duffy@boston.gov";

    $emailFields["tag"] = "metrolist listing";

    $vars = self::_getRequestParams();

    $emailFields["TextBody"] = "
Thank you for your submission to Metrolist. We will review your submission and contact you if we have any questions.\n\n
Property Name: ${property_name}\n
Number of Units Updated: ${units_updated}\n
Number of Units Added: ${units_added}\n\n
IMPORTANT: If you need to submit listings for additional properties, please request a new form.\n\n
Thank you.\n
Mayor's Office of Housing.\n
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

    $request = \Drupal::request();
    $property_name = $request->get("property_name");
    $current_units = $request->get("current_units");
    $units = $request->get("units");
    $units_added = $units - $current_units;
    $units_updated = $current_units - $units_added;

    $html = "
<img class='ml-icon' height='34' src='https://assets.boston.gov/icons/metrolist/metrolist-logo.png' />\n
<p class='txt'>Thank you for your submission to Metrolist. We will review your submission and contact you if we have any questions.</p>\n
<p class='txt'><span class='txt-b'>Property Name:</span> ${property_name}</p>\n
<p class='txt'><span class='txt-b'>Number of Units Updated:</span> ${units_updated}</p>\n
<p class='txt'><span class='txt-b'>Number of Units Added:</span> ${units_added}</p>\n
<p class='txt'><span class='txt-b'>Important:</span> If you need to submit listings for additional properties, please request a new form.</p>\n
<p class='txt'>Thank you.</p>
<p class='txt'>Mayor's Office of Housing.</p>\n
<hr>\n
<p class='txt'>This message was sent after completing the Metrolist Listing form on Boston.gov.</p>\n
<p class='txt'>The form was submitted by ${emailFields['name']} (${emailFields['to_address']}) from the page at " . urldecode($emailFields['url']) . ".</p>
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
