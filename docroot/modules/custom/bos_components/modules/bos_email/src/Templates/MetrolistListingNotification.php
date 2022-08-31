<?php

namespace Drupal\bos_email\Templates;

use Drupal\bos_email\Controller\EmailControllerBase;
use Drupal\Core\Controller\ControllerBase;

/**
 * Template class for Postmark API.
 */
class MetrolistListingNotification extends ControllerBase implements EmailControllerBase {

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
A new listing has been submitted to Metrolist:\n
Submitted on: ${completed}\n
Submitted By: ${contact_name}\n
Listing Contact Company: ${contact_company}\n
Contact Email: ${contact_email}\n
Contact Phone: ${contact_phone}\n\n
Property Name: ${property_name}\n
Property Address: ${street_address}, ${city}, ${zip}\n
View Pending Development Units: https://boston-dnd.lightning.force.com/lightning/o/Development_Unit__c/list?filterName=${developmentsfid}\n\n
-------------------------------- \n
This message was sent using the Metrolist Listing form on Boston.gov " . urldecode($emailFields['url']) . ".\n\n
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
    $completed = $request->get("completed");
    $contact_name = $request->get("contact_name");
    $contact_company = $request->get("contact_company");
    $contact_email = $request->get("contact_email");
    $contact_phone = $request->get("contact_phone");
    $property_name = $request->get("property_name");
    $street_address = $request->get("street_address");
    $city = $request->get("city");
    $zip = $request->get("zip");
    $developmentsfid = $request->get("developmentsfid");

    $html = "
<img class='ml-icon' height='34' src='https://assets.boston.gov/icons/metrolist/metrolist-logo.png' />\n
<p class='txt'>A new listing has been submitted to Metrolist:</p>\n
<p class='txt'><span class='txt-b'>Submitted on:</span> ${completed}</p>\n
<p class='txt'><span class='txt-b'>Submitted By:</span> ${contact_name}</p>\n
<p class='txt'><span class='txt-b'>Listing Contact Company:</span> ${contact_company}</p>\n
<p class='txt'><span class='txt-b'>Contact Email:</span> ${contact_email}</p>\n
<p class='txt'><span class='txt-b'>Contact Phone:</span> ${contact_phone}</p>\n
<p class='txt'><span class='txt-b'>Property Name:</span> ${property_name}</p>\n
<p class='txt'><span class='txt-b'>Property Address:</span> ${street_address}, ${city}, ${zip}</p>\n
<p class='txt'><a href='https://boston-dnd.lightning.force.com/lightning/o/Development_Unit__c/list?filterName=${developmentsfid}'>View Pending Development Units</a></p>\n
<hr>
<p class='txt'>This message was sent using the <a href='${emailFields['url']}'>Metrolist Listing Form</a> on Boston.gov.</p>\n\n
<hr>\n
";

    // check for complete-ness of html
    if (stripos($html, "<html") === FALSE) {
      $emailFields["HtmlBody"] = "<html>\n<head></head>\n<body>${html}</body>\n</html>";
    }
    // Add a title
    $emailFields["HtmlBody"] = preg_replace(
      "/\<\/head\>/i",
      "<title>Metrolist Listing Notification</title>\n</head>",
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
