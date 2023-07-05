<?php

namespace Drupal\bos_email\Templates;

use Drupal\bos_email\CobEmail;
use Drupal\bos_email\Controller\DrupalmailAPI;
use Drupal\bos_email\EmailTemplateBase;
use Drupal\bos_email\EmailTemplateInterface;

/**
 * Template class for Postmark API.
 */
class TestDrupalmail extends EmailTemplateBase implements EmailTemplateInterface {

  /**
   * @inheritDoc
   */
  public static function formatOutboundEmail(array &$emailFields): void {
    /**
     * @var CobEmail $data
     */
    $data = $emailFields["drupal_data"];
    $data->setField("To", $emailFields["To"]);
    $data->setField("From", "Drupal {$data->getField("endpoint")} Test <test@boston.gov>");
    $data->setField("Subject", $emailFields["Subject"]);
    $data->setField("TextBody", $emailFields["TextBody"]);

    if ($emailFields["htmlBody"]) {
      $data->setField("HtmlBody", $emailFields["HtmlBody"]);
    }
    else{
      $data->delField("HtmlBody");
    }

    $data->delField("TemplateID");
    $data->delField("TemplateModel");
  }

  /**
   * @inheritDoc
   */
  public static function templatePlainText(&$emailFields): void { }

  /**
   * @inheritDoc
   */
  public static function templateHtmlText(&$emailFields): void {  }

  /**
   * @inheritDoc
   */
  public static function formatInboundEmail(array &$emailFields): void {  }

  /**
   * @inheritDoc
   */
  public static function getHoneypotField(): string {
    return "";
  }

  /**
   * @inheritDoc
   */
  public static function getServerID(): string {
    return "drupal_mail";
  }

}
