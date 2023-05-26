<?php

namespace Drupal\bos_email;

interface EmailTemplateInterface {

  /**
   * Creates a message body for plain text message.
   * Adds/Updates field "TextBody" with formatted msg body to supplied array.
   *
   * @param array $emailFields An array containing the fields supplied from a
   *    form or the calling function needed by the template.
   * @return void
   */
  public static function templatePlainText(array &$emailFields): void;

  /**
   * Creates a message body for html message.
   * Adds/Updates field "HtmlBody" with formatted msg body to supplied array.
   *
   * @param array $emailFields An array containing the fields supplied from a
   *    form or the calling function needed by the template.
   * @return void
   */
  public static function templateHtmlText(array &$emailFields): void;

  /**
   * Creates a fully formed email object into $emailFields, using data in
   * $emailFields.
   *
   * @param array $emailFields An array of input email data
   *
   * @return void
   */
  public static function templateFormatEmail(array &$emailFields): void;

  /**
   * Creates a message body for incoming emails.
   *
   * @param array $emailFields An array containing the fields supplied from a
   *        Postmark webhook callback.
   *
   * @return void
   */
  public static function incoming(array &$emailFields): void;

  /**
   * @return string The name of the honeypot field on the form (if any).
   *     NOTE: Should return "" if there is no honeypot.
   */
  public static function honeypot(): string;

  /**
   * @return string The name of the server. This is used throughout the app and
   *  controls which server is used in Postmark.  There is a token in the ENVAR
   *  POSTMARK_SETTINGS ([server]_token) which directs the email to the correct
   *  postmark server.
   */
  public static function postmarkServer(): string;

}
