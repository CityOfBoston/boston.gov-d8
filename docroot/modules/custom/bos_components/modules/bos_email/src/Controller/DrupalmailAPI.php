<?php

namespace Drupal\bos_email\Controller;

use Boston;
use Drupal\bos_email\CobEmail;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Postmark class for API.
 */
class DrupalmailAPI extends ControllerBase {

  const MESSAGE_SENT = 'Message sent.';

  const MESSAGE_QUEUED = 'Message queued.';

  /** @var \Drupal\bos_email\EmailTemplateInterface */
  private $template_class;

  /**
   * Current request object for this class.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  public $request;

  /**
   * Server hosted / mapped to Postmark.
   *
   * @var string
   */
  public $service;

  /**
   * Public construct for Request.
   */
  public function __construct(RequestStack $request) {
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
    // Load the service required to construct this class.
      $container->get('request_stack')
    );
  }

  /**
   * Check / set valid session token.
   *
   */
  public function token(string $operation) {
    $data = $this->request->getCurrentRequest()->get('data');
    $token = new TokenOps();

    if ($operation == "create") {
      $response_token = $token->tokenCreate();

    }
    elseif ($operation == "remove") {
      $response_token = $token->tokenRemove($data);

    }
    else {
      $response_token = $token->tokenGet($data);

    }

    $response = new CacheableJsonResponse($response_token);
    return $response;
  }

  /**
   * Load an email into the queue for later dispatch.
   *
   * @param array $data
   *   The array containing the email POST data.
   */
  public function addQueueItem(array $data) {
    $queue_name = 'email_contactform';
    $queue = \Drupal::queue($queue_name);
    $queue_item_id = $queue->createItem($data);

    return $queue_item_id;
  }

  /**
   * Check the authentication key sent in the header is valid.
   *
   * @return bool
   */
  private function authenticate() {
    $postmark_auth = new PostmarkOps();
    return $postmark_auth->checkAuth($this->request->getCurrentRequest()->headers->get("authorization"));
  }

  /**
   * Send the email via Postmark.
   *
   * @param \Drupal\bos_email\CobEmail $mailobj The email object
   *
   * @return array
   */
  private function sendEmail(CobEmail $email) {

    /**
     * @var $mailobj CobEmail
     */

    // Extract the email object, and validate.
    try {
      $mailobj = $email->data();
    }
    catch (\Exception $e) {}

    if ($email->hasValidationErrors()) {
      return [
        'status' => 'failed',
        'response' => implode(":", $email->getValidationErrors()),
      ];

    }

    /**
     * @var \Drupal\Core\Mail\MailManager $mailManager
     */
    try {

      // Send the email.
      $mailobj["_error_message"] = "";
      $key = "{$this->service}.{$mailobj["Tag"]}";

      $mailManager = \Drupal::service('plugin.manager.mail');

      $sent = $mailManager->mail("bos_email", $key , $mailobj["To"], "en", $mailobj, NULL, TRUE);

      if (!$sent || !$sent["result"]) {
        if (!empty($params["_error_message"])) {
          throw new \Exception($params["_error_message"]);
        }
        else {
          throw new \Exception("Error sending email.");
        }
      }

      $response_message = self::MESSAGE_SENT;

    }
    catch (\Exception $e) {
      try {
        $this->addQueueItem($mailobj);
      }
      catch (\Exception $ee) {
        \Drupal::logger("bos_email:DrupalmailAPI")->info("Failed to queued mail item in {$email->getField("server")}");
        return [
          'status' => 'error',
          'response' => "Error sending message {$e->getMessage()}, then error queueing item {$ee->getMessage()}.",
        ];
      }

      if (Boston::is_local()) {
        \Drupal::logger("bos_email:DrupalmailAPI")->info("Queued {$email->getField("server")}");
      }

      $response_message = self::MESSAGE_QUEUED;
    }


    return [
      'status' => 'success',
      'response' => $response_message,
    ];

  }


  /**
   * Begin script and API operations.
   *
   * @param string $service
   *   The server being called via the endpoint uri.
   *
   * @return CacheableJsonResponse
   *   The json response to send to the endpoint caller.
   */
  public function begin(string $service, string $tag) {

    if (Boston::is_local()) {
      \Drupal::logger("bos_email:DrupalmailAPI")
        ->info("Starts {$service} (callback)");
    }

    $this->service = $service;

    if ($this->authenticate()) {

      if ($this->request->getCurrentRequest()->getMethod() == "POST") {

        // Get the request payload.
        $emailFields = $this->request->getCurrentRequest()->getContent();
        $emailFields = (array) json_decode($emailFields);

        // Format the email message.
        if (class_exists("Drupal\\bos_email\\Templates\\{$service}") === TRUE) {

          $this->template_class = "Drupal\\bos_email\\Templates\\{$service}";

          $emailFields["drupal_data"] = new CobEmail([
            "server" => $this->template_class::getServerID(),
            "endpoint" => Boston::current_environment(),
            "Tag" => $tag
          ]);

          // Logging
          if (Boston::is_local()) {
            \Drupal::logger("bos_email:DrupalmailAPI")
              ->info("Set data {$service}:<br/>" . json_encode($emailFields));
          }

          $this->template_class::formatOutboundEmail($emailFields);
          $response_array = $this->sendEmail($emailFields["drupal_data"]);

          if (Boston::is_local()) {
            \Drupal::logger("bos_email:DrupalmailAPI")
              ->info("Finished {$service}: " . json_encode($response_array));
          }

        }

        if (!empty($response_array)) {
          return new CacheableJsonResponse($response_array, Response::HTTP_OK);
        }
        else {
          return new CacheableJsonResponse(["error" => "Unknown"], Response::HTTP_BAD_REQUEST);
        }

      }

    }
  }

}
