<?php

namespace Drupal\bos_email\Controller;

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

  private string $template_class;
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

  private function authenticate() {
    $postmark_auth = new PostmarkOps();
    return $postmark_auth->checkAuth($this->request->getCurrentRequest()->headers->get("authorization"));
  }

  /**
   * Validate email params.
   *
   * @param array $emailFields
   *   The array containing Postmark API needed fieds.
   * @param string $server
   *   The server being called via the endpoint uri.
   */
  private function validateParams(array $emailFields) {
    $required_fields = ['to_address', 'from_address', 'subject'];

    if (count(array_intersect(array_keys($emailFields), $required_fields)) == count($required_fields)) {

      foreach ($emailFields as $key => $value) {
        // Validate emails.
        if (in_array($key, ["to_address", "from_address"])) {
          $validate_email = preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $value);
          if (!$validate_email) {
            $this->error = "To and From email fields must be properly formatted.";
            break;
          }

        }
        elseif ($key == "subject" && $value == "") {
          // Check for blank fields.
          $this->error = "Subject field must have values.";
          break;
        }
        elseif (!empty($this->honeypot) && $key == $this->honeypot && $value !== "") {
          // Check the honeypot
          $this->error = "honeypot.";
          break;
        }
      }

    }
    else {
      $this->error = "Missing required field params.";
    }

    return empty($this->error);

  }

  /**
   * Send email via Postmark API.
   *
   * @param array $emailFields
   *   The array containing Postmark API needed fieds.
   * @param string $server
   *   The server being called via the endpoint uri.
   */
  private function formatEmail(array $emailFields, string $server) {

    // Create a nicer sender address if possible.
    $emailFields["modified_from_address"] = $emailFields["from_address"];
    if (isset($emailFields["sender"])) {
      $emailFields["modified_from_address"]  = "{$emailFields["sender"]}<{$emailFields["from_address"]}>";
    }

    if (isset($this->template_class)) {

      // This allows us to inject custom templates to reformat the email.
      $this->template_class::templatePlainText($emailFields);
      if (!empty($emailFields["useHtml"])) {
        $this->template_class::templateHtmlText($emailFields);
      }

      $data = [
        "server" => strtolower($server),
        "To" => $emailFields["to_address"],
        "From" => $emailFields["modified_from_address"],
        "Subject" => $emailFields["subject"],
        "TextBody" => $emailFields["TextBody"],
        "ReplyTo" => $emailFields["from_address"]
      ];

      if (!empty($emailFields["postmark_endpoint"])) {
        $data["postmark_endpoint"] = $emailFields["postmark_endpoint"];
      }
      else {
        $data["postmark_endpoint"] = "https://api.postmarkapp.com/email";
      }
      if (!empty($emailFields["ReplyTo"] )) {
        $data["ReplyTo"] = $emailFields["ReplyTo"];
      }
      if (!empty($emailFields['useHtml'])) {
        $data["HtmlBody"] = $emailFields["HtmlBody"];
      }
      if (!empty($emailFields['cc'])) {
        $data["Cc"] = $emailFields['cc'];
      }
      if (!empty($emailFields['bcc'])) {
        $data["Bcc"] = $emailFields['bcc'];
      }
      if (!empty($emailFields['TemplateID'])) {
        $data["TemplateID"] = $emailFields['TemplateID'];
        $data["TemplateModel"] = $emailFields['TemplateModel'];
        if (isset($data["TextBody"])) {
          unset($data["TextBody"]);
        }
        if (isset($data["Subject"])) {
          unset($data["Subject"]);
        }
      }

      if ($this->debug) {
        \Drupal::logger("bos_email:PostmarkAPI")
          ->info("Email prepped {$server}:<br>" . json_encode($data));
      }

    }

    elseif (isset($emailFields["template_id"])) {
      $data = [
        "postmark_endpoint" => "https://api.postmarkapp.com/email/withTemplate",
        "server" => strtolower($server),
        "To" => $emailFields["to_address"],
        "From" => $emailFields["modified_from_address"],
        "TemplateID" => $emailFields["template_id"],
        "TemplateModel" => [
          "subject" => $emailFields["subject"],
          "TextBody" => $emailFields["message"],
          "ReplyTo" => $emailFields["from_address"]
        ],
      ];
    }

    else {

      $data = [
        "postmark_endpoint" => "https://api.postmarkapp.com/email",
        "server" => strtolower($server),
        "To" => $emailFields["to_address"],
        "From" => $emailFields["modified_from_address"],
        "subject" => $emailFields["subject"],
        "TextBody" => $emailFields["message"],
        "ReplyTo" => $emailFields["from_address"]
      ];
    }

    if (!empty($emailFields['tag'])) {
      $data["Tag"] = $emailFields['tag'];
    }
    return $data;

  }

  private function sendEmail($data, $server) {

      $postmark_ops = new PostmarkOps();
      $postmark_send = $postmark_ops->sendEmail($data);

      if (!$postmark_send) {
        // Add email data to queue because of Postmark failure.
        $data["postmark_error"] = $postmark_ops->error;
        $this->addQueueItem($data);

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
   * Begin script and API operations.
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
      $data = $this->request->getCurrentRequest()->get('email');
      // Check the honeypot if there is one.
      if (empty($this->honeypot) || empty($data[$this->honeypot])) {
        if ($this->debug) {
          \Drupal::logger("bos_email:PostmarkAPI")
            ->info("Set data {$server}:<br/>" . json_encode($data));
        }
        if (!empty($data["token_session"])) {
          unset($data["token_session"]);
        }
        if ($this->authenticate()) {
          // Validate that all the necessary fields are provided.
          if ($this->validateParams($data)) {
            // Format the message body.
            $data = $this->formatEmail($data, $this->server);

            // Send email.
            $response_array = $this->sendEmail($data, $this->server);

          }
          else {
            PostmarkOps::alertHandler($data, [], "", [], $this->error);
            if ($this->error == "honeypot") {
              return new CacheableJsonResponse([
                'status' => 'success',
                'response' => str_replace(".", "!", self::MESSAGE_SENT),
              ], Response::HTTP_OK);
            }
            else {
              return new CacheableJsonResponse([
                'status' => 'error',
                'response' => $this->error,
              ], Response::HTTP_BAD_REQUEST);
            }
          }
        }
        else {
          PostmarkOps::alertHandler($data,[],"",[],"authtoken");
          return new CacheableJsonResponse([
            'status' => 'error',
            'response' => 'could not authenticate',
          ], Response::HTTP_UNAUTHORIZED );
        }
      }
      elseif (!empty($this->honeypot) && !empty($data[$this->honeypot])) {
        PostmarkOps::alertHandler($data,[],"",[],"honeypot");
        return new CacheableJsonResponse([
          'status' => 'success',
          'response' => str_replace(".", "!", self::MESSAGE_SENT),
        ], Response::HTTP_OK);
      }
    }
    else {
      return new CacheableJsonResponse([
        'status' => 'error',
        'response' => 'no post data',
      ], Response::HTTP_BAD_REQUEST);
    };

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
