<?php

namespace Drupal\slackposter\Commands;

use Drupal\slackposter\Integrate\SlackAttachment;
use Drupal\slackposter\Integrate\SlackPost;
use Drush\Commands\DrushCommands;

/**
 * Class SlackposterCommands.
 *
 * Drush CommandFile.
 *
 * @package Drupal\slackposter\Commands
 */
class SlackposterCommands extends DrushCommands {

  /**
   * Manually posts a simple message to Slack.
   *
   * @param string $title
   *   The title for the message.
   * @param string $message
   *   The message to post to slack (can used md).
   * @param string $channel
   *   The channel to send to (prefix with "#").
   * @param string $username
   *   Attribute post to a username.
   * @param string $color
   *   Color for attachment bar (good/warning/danger/#hexnum).
   *
   * @return string
   *   Message to stdout in console.
   *
   * @validate-module-enabled slackposter
   *
   * @command slackposter:post
   * @aliases sppost
   */
  public function post($title, $message, $channel, $username, $color = "") {
    $slack = new SlackPost();
    $slack->icon();

    $slackAttachment = new SlackAttachment();
    $slackAttachment->authorIcon = "images/drupal.png";
    $slackAttachment->title = $title;
    $slackAttachment->text = $message;
    if (!empty($color)) {
      $slackAttachment->color = $color;
    }
    $slack->attachment($slackAttachment);

    $slack->channel = $channel;
    $slack->username = $username;
    $slack->comment = "";
    $out = $slack->post();

    if ($out['result'] == "OK") {
      return "[success] Message posted OK to " . $out['message']['channel'];
    }
    else {
      return "Error: " . $out['error'];
    }
  }

}
