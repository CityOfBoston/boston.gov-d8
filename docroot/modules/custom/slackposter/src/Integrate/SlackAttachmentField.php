<?php

namespace Drupal\slackposter\Integrate;

/**
 * Class SlackAttachmentField.
 *
 * @package Drupal\slackposter\Integrate
 */
class SlackAttachmentField {

  /**
   * The attachment-field's title.
   *
   * Shown as a bold heading displayed in the field object.
   * It cannot contain markup and will be escaped for you.
   *
   * @var string
   */
  public $title;

  /**
   * The attachment-field's value.
   *
   * The text value displayed in the field object. It can be formatted as
   * plain text, or with mrkdwn by using the mrkdwn_in option above.
   *
   * @var string
   */
  public $value;

  /**
   * Use field columns.
   *
   * Indicates whether the field object is short enough to be displayed
   * side-by-side with other field objects. Defaults to false.
   *
   * @var string
   */
  public $short;

  /**
   * SlackAttachmentField constructor.
   *
   * @param string $title
   *   The attachment title.
   * @param string $value
   *   The attachment body.
   * @param string $short
   *   Use columns.
   *
   * @return array
   *   The attachment-field as an array.
   */
  public function __construct(string $title = "", string $value = "", string $short = "") {
    if (!empty($title)) {
      $this->title = $title;
    }
    if (!empty($value)) {
      $this->value = $value;
    }
    if (!empty($short)) {
      $this->short = $short;
    }
    return $this->toArray();
  }

  /**
   * Create an array from this class object.
   *
   * @return array
   *   The attachment-field as an array.
   */
  public function toArray() {
    return [
      'title' => $this->title,
      'value' => $this->value,
      'short' => $this->short,
    ];
  }

}
