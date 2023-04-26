<?php

namespace Drupal\bos_email\Controller;

use Drupal\bos_email\CobEmail;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Cache\CacheableJsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Postmark class for API.
 */
class PostmarkAPI extends ControllerBase {

  const MESSAGE_SENT = 'Message sent.';
  const MESSAGE_QUEUED = 'Message queued.';

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
  public $server;

  /**
   * @var boolean
   */
  public $debug;

  private string $error = "";

  /** @var \Drupal\bos_email\EmailTemplateInterface */
  private $template_class;
  private string $honeypot;

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

    } elseif ($operation == "remove") {
      $response_token = $token->tokenRemove($data);

    } else {
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
   * Send email via Postmark API.
   *
   * @param array $emailFields
   *   The array containing Postmark API needed fieds.
   * @param string $server
   *   The server being called via the endpoint uri.
   */
  private function formatEmail(array &$emailFields, string $server) {

    // Create a nicer sender address if possible.
    $emailFields["modified_from_address"] = $emailFields["from_address"];
    if (isset($emailFields["sender"])) {
      $emailFields["modified_from_address"]  = "{$emailFields["sender"]}<{$emailFields["from_address"]}>";
    }

    $cobdata = new CobEmail();

    if (isset($this->template_class)) {

      // This allows us to inject custom templates to reformat the email.
      $this->template_class::templatePlainText($emailFields);
      if (!empty($emailFields["useHtml"])) {
        $this->template_class::templateHtmlText($emailFields);
      }

      $cobdata->setField("server", strtolower($server));
      $cobdata->setField("To", $emailFields["to_address"]);
      $cobdata->setField("From", $emailFields["modified_from_address"]);
      $cobdata->setField("Subject", $emailFields["subject"]);
      $cobdata->setField("TextBody", $emailFields["TextBody"]);

      if (empty($emailFields["postmark_endpoint"])) {
        $emailFields["postmark_endpoint"] = "https://api.postmarkapp.com/email";
      }
      $cobdata->setField("postmark_endpoint", $emailFields["postmark_endpoint"]);

      // If the template has set a specific ReplyTo address then use that,
      // otherwise just use the sender as ReplyTo.
      $cobdata->setField("ReplyTo", $emailFields["ReplyTo"] ?? $emailFields["from_address"]);
      if (!empty($emailFields['useHtml'])) {
        $cobdata->setField("HtmlBody", $emailFields["HtmlBody"]);
      }
      if (!empty($emailFields['cc'])) {
        $cobdata->setField("Cc", $emailFields['cc']);
      }
      if (!empty($emailFields['bcc'])) {
        $cobdata->setField("Bcc", $emailFields['bcc']);
      }
      if (!empty($emailFields['TemplateID'])) {
        $cobdata->setField("TemplateID", $emailFields['TemplateID']);
        $cobdata->setField("TemplateModel", $emailFields['TemplateModel']);
          $cobdata->delField("TextBody");
          $cobdata->delField("Subject");
      }
      else {
        $cobdata->delField("TemplateID");
        $cobdata->delField("TemplateModel");
      }

      if (!empty($emailFields['headers'])) {
        $cobdata->setField("Headers", $emailFields['headers']);
      }
      if (!empty($emailFields['metadata'])) {
        $cobdata->setField("Metadata", $emailFields['metadata']);
      }

    }

    else {

      $cobdata->setField("postmark_endpoint", "https://api.postmarkapp.com/email");
      $cobdata->setField("server", strtolower($server));
      $cobdata->setField("To", $emailFields["to_address"]);
      $cobdata->setField("From", $emailFields["modified_from_address"]);

      if (isset($emailFields["template_id"])) {
        $cobdata->setField("postmark_endpoint", "https://api.postmarkapp.com/email/withTemplate");
        $cobdata->setField("TemplateID", $emailFields["template_id"]);
        $cobdata->setField("TemplateModel", [
          "Subject" => $emailFields["subject"],
          "TextBody" => $emailFields["message"],
          "ReplyTo" => $emailFields["from_address"]
        ]);
        $cobdata->delField("ReplyTo");
        $cobdata->delField("Subject");
        $cobdata->delField("TextBody");
      }

      else {
        $cobdata->setField("Subject", $emailFields["subject"]);
        $cobdata->setField("TextBody", $emailFields["message"]);
        $cobdata->setField("ReplyTo", $emailFields["from_address"]);
        $cobdata->delField("TemplateID");
        $cobdata->delField("TemplateModel");
      }
    }

    if (!empty($emailFields['tag'])) {
      $cobdata->setField("Tag", $emailFields['tag']);
    }

    if ($this->debug) {
      \Drupal::logger("bos_email:PostmarkAPI")
        ->info("Email prepped {$server}:<br>" . json_encode($cobdata->data()));
    }

    // Validate the email data
    $cobdata->validate();
    if ($cobdata->hasValidationErrors()) {
      $this->error = implode(", ", $cobdata->getValidationErrors());
      return FALSE;
    }

    $emailFields["postmark_data"] = $cobdata;

    return TRUE;

  }

  /**
   * Send the email via Postmark.
   *
   * @param \Drupal\bos_email\CobEmail $email The email object
   * @param $server The type of email being sent
   *
   * @return string[]
   * @throws \Exception
   */
  private function sendEmail(CobEmail $email, $server) {

      $postmark_ops = new PostmarkOps();
      $postmark_send = $postmark_ops->sendEmail($email->data());

      if (!$postmark_send) {
        // Add email data to queue because of Postmark failure.
        $email->addField("postmark_error", CobEmail::FIELD_STRING, $postmark_ops->error);
        $this->addQueueItem($email->data());

        if ($this->debug) {
          \Drupal::logger("bos_email:PostmarkAPI")->info("Queued {$server}");
        }

        $response_message = self::MESSAGE_QUEUED;

      }
      else {
        // Message was sent successfully to sender via Postmark.
        $response_message = self::MESSAGE_SENT;
      }

      return [
        'status' => 'success',
        'response' => $response_message,
      ];

  }

  /**
   * Begin script and API operations when a session object has been secured.
   *
   * @param string $server
   *   The server being called via the endpoint uri.
   * @return CacheableJsonResponse
   *   The json response to send to the endpoint caller.
   */
  public function beginSession(string $server) {
    $token = new TokenOps();
    $data = $this->request->getCurrentRequest()->get('email');
    $data_token = $token->tokenGet($data["token_session"]);

    if ($data_token["token_session"]) {
      // remove token session from DB to prevent reuse
      $token->tokenRemove($data["token_session"]);
      // begin normal email submission
      return $this->begin($server);
    }
    else {
      PostmarkOps::alertHandler($data,[],"",[],"sessiontoken");
      return new CacheableJsonResponse([
        'status' => 'error',
        'response' => 'invalid token',
      ], Response::HTTP_FORBIDDEN);
    }
  }

  /**
   * Begin script and API operations.
   *
   * @param string $server
   *   The server being called via the endpoint uri.
   * @return CacheableJsonResponse
   *   The json response to send to the endpoint caller.
   */
  public function begin(string $server = 'contactform') {

    $this->debug = str_contains($this->request->getCurrentRequest()->getHttpHost(), "lndo.site");
    $response_array = [];

    if (in_array($server, ["contactform", "registry"])) {
      // This is done for legacy reasons (endpoint already in production and
      // in lowercase)
      $server = ucwords($server);
    }

    $this->server = $server;
    if (class_exists("Drupal\\bos_email\\Templates\\{$server}") === TRUE) {
      $this->template_class = "Drupal\\bos_email\\Templates\\{$server}";
      $this->server = $this->template_class::postmarkServer();
      $this->honeypot = $this->template_class::honeypot() ?: "";
    }

    if ($this->debug) {
      \Drupal::logger("bos_email:PostmarkAPI")->info("Starts {$server}");
    }

    if ($this->request->getCurrentRequest()->getMethod() == "POST") {

      // Get the request payload.
      $payload = $this->request->getCurrentRequest()->get('email');

      // Check the honeypot if there is one.
      if (!empty($this->honeypot) && !empty($payload[$this->honeypot])) {
        PostmarkOps::alertHandler($payload,[],"",[],"honeypot");
        return new CacheableJsonResponse([
          'status' => 'success',
          'response' => str_replace(".", "!", self::MESSAGE_SENT),
        ], Response::HTTP_OK);
      }

      // Logging
      if ($this->debug) {
        \Drupal::logger("bos_email:PostmarkAPI")
          ->info("Set data {$server}:<br/>" . json_encode($payload));
      }

      // cleanup the session tokens.
      if (!empty($payload["token_session"])) {
        unset($payload["token_session"]);
      }

      if ($this->authenticate()) {
        // Format and validate the message body.
        if ($this->formatEmail($payload, $this->server)) {
          // Send email.
          $response_array = $this->sendEmail($payload["postmark_data"], $this->server);
        }
        else {
          PostmarkOps::alertHandler($payload, [], "", [], $this->error);
          return new CacheableJsonResponse([
            'status' => 'error',
            'response' => $this->error,
          ], Response::HTTP_BAD_REQUEST);
        }
      }

      else {
        PostmarkOps::alertHandler($payload,[],"",[],"authtoken");
        return new CacheableJsonResponse([
          'status' => 'error',
          'response' => 'could not authenticate',
        ], Response::HTTP_UNAUTHORIZED );
      }

    }
    else {
      return new CacheableJsonResponse([
        'status' => 'error',
        'response' => 'no post data',
      ], Response::HTTP_BAD_REQUEST);
    };

    // Logging
    if ($this->debug) {
      \Drupal::logger("bos_email:PostmarkAPI")
        ->info("Finished {$server}: " . json_encode($response_array));
    }

    if (!empty($response_array)) {
      return new CacheableJsonResponse($response_array, Response::HTTP_OK);
    }
    else {
      return new CacheableJsonResponse(["error" => "Unknown"], Response::HTTP_BAD_REQUEST);
    }
  }

}
