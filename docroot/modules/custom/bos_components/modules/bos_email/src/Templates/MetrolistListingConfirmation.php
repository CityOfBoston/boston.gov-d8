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
Property Name: ${vars["property_name"]}\n
Number of Units Updated: ${vars["units_updated"]}\n
Number of Units Added: ${vars["units_added"]}\n\n
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

    $vars = self::_getRequestParams();

    // todo: add the submission link to the email.
    $submission_link = "";
    if (!empty($vars["submission"])) {
//      $submission_link = "<p class='txt'><a class=\"button\" tabindex=\"-1\" href='${output["submission"]}' target='_blank'>View Submission</a></p>\n";
    }

    $html = "
<img class='ml-icon' height='34' src='https://assets.boston.gov/icons/metrolist/metrolist-logo_email.png' />\n
<p class='txt'>Thank you for your submission to Metrolist. We will review your submission and contact you if we have any questions.</p>\n
<p class='txt'><span class='txt-b'>Property Name:</span> ${vars["property_name"]}</p>\n
<p class='txt'><span class='txt-b'>Number of Units Updated:</span> ${vars["units_updated"]}</p>\n
<p class='txt'><span class='txt-b'>Number of Units Added:</span> ${vars["units_added"]}</p>\n
${submission_link}
<p class='txt'><span class='txt-b'>Important:</span> If you need to submit listings for additional properties, please request a new form.</p>\n
<p class='txt'><br /><table id='moh-signature' cellpadding='0' cellspacing='0' border='0'><tr>\n
<td><a href='https://content.boston.gov/departments/housing' target='_blank'>
  <img height='34' class='ml-icon' src='https://assets.boston.gov/icons/metrolist/neighborhood_development_logo_email.png' />
  </a></td>\n
<td>Thank you<br /><span class='txt-b'>The Mayor's Office of Housing</span></td>\n
</tr></table></p>\n
<hr>\n
<p class='txt'>This message was sent after completing the Metrolist Listing form on Boston.gov.</p>\n
<p class='txt'>The form was submitted by ${emailFields['name']} (${emailFields['to_address']}) from the page at " . urldecode($emailFields['url']) . ".</p>
<hr>\n
";
    $emailFields["HtmlBody"] = self::_makeHtml($html,  $emailFields["subject"]);

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
   * Helper function to extract and process request parameters.
   *
   * @return array
   */
  public static function _getRequestParams() {
    $request = \Drupal::request();
    $output = [
      "property_name" => $request->get("property_name",""),
      "new" => ($request->get("select_development", "") == "new"),
      "current_units" => count($request->get("current_units", [])), //before update
      "units" => count($request->get("units", [])), //after update
      "sid" => $request->get("sid",""),
      "serial" => $request->get("serial",""),
    ];

    // Try to establish how many units have been added and/or updated.
    if ($output["current_units"]) {
      // These are the units before the form was submitted. One row per row of
      // the table in the form.
      $output["current_units"] = 0;
      foreach($request->get("current_units") as $unit) {
        if (!empty($unit["unit_count"])) {
          // unit_count field is not set for "New Building"
          $output["current_units"] += intval($unit["unit_count"]);
        }
      }
    }
    if ($output["units"]) {
      // These are the units after the form submission. One row per row of
      // the table in the form.
      $output["units"] = 0;
      foreach($request->get("units") as $unit) {
        $output["units"] += intval($unit["unit_count"]);
      }
    }
    if ($output["new"]) {
      // If this is a New Building, then no units have been updated and the
      // units_added are the sum of unit_count for each unit record.
      $output["units_added"] = $output["units"];
      $output["units_updated"] = 0;
    }
    else {
      // If this is an update to an existing building, then to some extent we
      // are guessing. The sum of the unit_count after the update less the sum
      // before must be additions. We have to assume all other units have been
      // updated because we don't know any better without performing a
      // complicated comparision of each unit row.
      $output["units_added"] = $output["units"] - $output["current_units"];
      $output["units_updated"] = $output["units"] - $output["units_added"];
    }

    if ($request->get("token", FALSE)) {
      $output["submission"] = "/webform/metrolist_listing/submissions/${output["sid"]}?token=" . $request->get("token");
    }

    return $output;
  }

  /**
   * Ensures an html string is top and tailed as a full html message.
   *
   * @param string $html Html message to format
   * @param string $title Title (in head) for the html object
   *
   * @return string
   */
  public static function _makeHtml(string $html, string $title) {
    // check for complete-ness of html
    if (stripos($html, "<html") === FALSE) {
      $output = "<html>\n<head></head>\n<body>${html}</body>\n</html>";
    }

    // Add a title
    $output = preg_replace(
      "/\<\/head\>/i",
      "<title>${title}</title>\n</head>",
      $output);
    // Add in the css
    $css = self::_css();
    $output = preg_replace(
      "/\<\/head\>/i",
      "${css}\n</head>",
      $output);

    return $output;

  }

}
