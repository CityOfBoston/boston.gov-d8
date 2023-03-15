<?php

namespace Drupal\bos_email\Templates;

use Drupal\bos_email\EmailTemplateCss;
use Drupal\bos_email\EmailTemplateInterface;

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
    $env = (getenv('AH_SITE_ENVIRONMENT') !== 'prod' ? '-staging' : '');
    $emailFields["modified_from_address"] = "Boston.gov Contact Form <{$rand}@contactform{$env}.boston.gov>";

    // Create the plain text body.
    $emailFields["TextBody"] = "-- REPLY ABOVE THIS LINE -- \n\n";
    $emailFields["TextBody"] .= "{$emailFields["message"]}\n\n";
    $emailFields["TextBody"] .= "-------------------------------- \n";
    $emailFields["TextBody"] .= "This message was sent using the contact form on Boston.gov.";
    $emailFields["TextBody"] .= " It was sent by {$emailFields["name"]} from {$emailFields["from_address"]}.";
    $emailFields["TextBody"] .= " It was sent from {$emailFields["url"]}.\n\n";
    $emailFields["TextBody"] .= "-------------------------------- \n";
  }

  /**
   * @inheritDoc
   */
  public static function templateHtmlText(&$emailFields): void {
    // Contact form does not have an html version.
    if (!empty($emailFields["HtmlBody"])) {
      unset ($emailFields["HtmlBody"]);
    }
    $emailFields["useHtml"] = "0";
  }

  /**
   * @inheritDoc
   */
  public static function honeypot(): string {
    return "contact";
  }

}
