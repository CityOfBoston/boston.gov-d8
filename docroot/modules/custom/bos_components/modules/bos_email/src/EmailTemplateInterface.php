<?php

namespace Drupal\bos_email;

interface EmailTemplateInterface {

  /**
   * Creates a fully formed email object into $emailFields, using data in
   * $emailFields.
   *
   * @param array $emailFields An array of input email data
   *
   * @return void
   */
  public static function formatOutboundEmail(array &$emailFields): void;

  /**
   * Creates a message body for incoming emails.
   *
   * @param array $emailFields An array containing the fields supplied from a
   *        Postmark webhook callback.
   *
   * @return void
   */
  public static function formatInboundEmail(array &$emailFields): void;

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
   * Returns the payload field which is a honeypot for the form submitted.
   * NOTE: Should return "" if there is no honeypot.
   *
   * @return string The name of the honeypot field on the form (if any).
   *
   */
  public static function getHoneypotField(): string;

  /**
   * Return the correct email service to use to relay the email.
   *
   * @return \Drupal\bos_email\EmailServiceInterface
   */
  public static function getEmailService(): EmailServiceInterface;

  /**
   * Return a group id to use in this email service.
   *
   * @return string The name of the groupid.
   *
   * This is used throughout the app and controls which outbound email server is
   * used in Postmark - There is a token in the ENVAR POSTMARK_SETTINGS
   * ([server]_token) which directs the email to the correct postmark server.
   * Other email services may require a similar concept.
   */
  public static function getGroupID(): string;

}
