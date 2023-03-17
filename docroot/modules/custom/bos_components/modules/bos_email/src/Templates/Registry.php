<?php

namespace Drupal\bos_email\Templates;

use Drupal\bos_email\EmailTemplateCss;
use Drupal\bos_email\EmailTemplateInterface;

/**
 * Template class for Postmark API.
 */
class Registry extends EmailTemplateCss implements EmailTemplateInterface {

  /**
   * @inheritDoc
   */
  public static function templatePlainText(&$emailFields): void {

    // We do not need to do anything because the registry uses a
    // template at PostMark.

    return;
  }

  /**
   * @inheritDoc
   */
  public static function templateHtmlText(&$emailFields): void {
    // Registry form does not have an html version.
    if (!empty($emailFields["HtmlBody"])) {
      unset ($emailFields["HtmlBody"]);
    }
    $emailFields["useHtml"] = "0";
  }

  /**
   * @inheritDoc
   */
  public static function honeypot(): string {
    return "";
  }

}
