<?php

namespace Drupal\slackposter\Integrate;

/**
 * Proves some public calls to make slack postings from custom code.
 */
class SlackAPI extends SlackPost {

  /**
   * This will import and translate the payload into the class and validate it.
   *
   * @param string $payload
   *   The message to post in a slack message format.
   * @param string $format
   *   The format of the payload.
   *
   * @return bool
   *   Whether post was OK or not.
   *
   * @throws \Exception
   */
  public function setPayload($payload, $format) {

    switch ($format) {
      case 'json':
      case 'hal_json':
        $payload = (array) json_decode($payload);
        break;

      case 'xml':
        $payload = (array) simplexml_load_string($payload);
    }

    if ($payload['text']) {
      $this->comment = $payload['text'];
    }
    if ($payload['channel']) {
      $this->channel = $payload['channel'];
    }
    if ($payload['username']) {
      $this->username = $payload['username'];
    }
    // if($payload['icon_url']) $this->icon_url = $payload['icon_url'];.
    if ($payload['url']) {
      $this->url = $payload['url'];
    }

    if (!$this->validatePayload()) {
      throw new \Exception('Malformed Payload');
    }

    return TRUE;

  }

  /**
   * Verify the uploaded payload is valid.
   *
   * @return bool
   *   Does it verify?
   */
  private function validatePayload() {

    // Must have, at a minimum the text to be published.
    if (!empty($this->comment)) {
      return TRUE;
    }

    // Make sure the chennel is propoerly prefixed.
    $this->channel = '#' . trim($this->channel, '#');

    return FALSE;
  }

}
