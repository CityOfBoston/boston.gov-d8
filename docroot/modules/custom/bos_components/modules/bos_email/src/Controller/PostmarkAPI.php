<?php

namespace Drupal\bos_email\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\bos_email\Templates\Contactform;
use Drupal\bos_email\Controller\TokenOps;
use Drupal\Core\Cache\CacheableJsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Postmark class for API.
 */
class PostmarkAPI extends ControllerBase {

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
    /*$session = \Drupal::request()->getSession();
    if ($operation == "create") {
      $date_time = \Drupal::time()->getCurrentTime();
      $token_name = 'token_session_'.$date_time;
    
      $session->set($token_name, $date_time);
      $response_token =  [
        'token_session' => $date_time
      ];
    } elseif ($operation == "remove") {
      $data = $this->request->getCurrentRequest()->get('data');
      $session->remove('token_session_'.$data);
      $response_token =  [
        'token_session' => "removed"
      ];
    } else {
      $data = $this->request->getCurrentRequest()->get('data');
        if ($data !== NULL) {
          if ($session->get('token_session_'.$data)) {

            $response_token =  [
              'token_session' => TRUE,
            ];
          } else {
            $response_token =  [
              'token_session' => FALSE,
            ];

          }
        }
    }*/
    $data = $this->request->getCurrentRequest()->get('data');
    $token = new tokenOps();
    
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
   * Perform Drupal Queue tasks.
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
   * Send email via Postmark API.
   *
   * @param array $emailFields
   *   The array containing Postmark API needed fieds.
   * @param string $server
   *   The server being called via the endpoint uri.
   */
  public function formatData(array $emailFields, string $server) {

    $postmark_auth = new PostmarkOps();
    $auth = $postmark_auth->checkAuth($_SERVER['HTTP_AUTHORIZATION']);
    
    $from_address = (isset($emailFields["sender"]) ? $emailFields["sender"] . "<" . $emailFields["from_address"] . ">" : $emailFields["from_address"]);

    if (isset($emailFields["template_id"])) {
      $data = [
        "To" => $emailFields["to_address"],
        "From" => $from_address,
        "TemplateID" => $emailFields["template_id"],
        "TemplateModel" => [
          "subject" => $emailFields["subject"],
          "TextBody" => $emailFields["message"],
          "ReplyTo" => $emailFields["from_address"]
        ],
      ];
      $data["postmark_endpoint"] = "https://api.postmarkapp.com/email/withTemplate";
    }
    elseif ($server == "contactform") {
      $env = ($_ENV['AH_SITE_ENVIRONMENT'] !== 'prod' ? '-staging' : '');
      $rand = substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, 12);
      $message = new Contactform();
      $message_template = $message->templatePlainText(
                  $emailFields["message"],
                  $emailFields["name"],
                  $emailFields["from_address"],
                  $emailFields["url"]);
      $from_contactform_rand = "Boston.gov Contact Form <" . $rand . "@contactform" . $env . ".boston.gov>";

      $data = [
        "To" => $emailFields["to_address"],
        "From" => $from_contactform_rand,
        "subject" => $emailFields["subject"],
        "TextBody" => $message_template,
        "ReplyTo" => $emailFields["name"] . "<" . $emailFields["from_address"] . ">," . $from,
      ];
      $data["postmark_endpoint"] = "https://api.postmarkapp.com/email";
    }
    else {

      $data = [
        "To" => $emailFields["to_address"],
        "From" => $from_address,
        "subject" => $emailFields["subject"],
        "TextBody" => $emailFields["message"],
        "ReplyTo" => $emailFields["from_address"]
      ];
      $data["postmark_endpoint"] = "https://api.postmarkapp.com/email";
    }

    if ($auth == TRUE && $emailFields["honey"] == "") :
      $data["server"] = $server;

      $postmark_ops = new PostmarkOps();
      $postmark_send = $postmark_ops->sendEmail($data);

      if (!$postmark_send) {
        // Add email data to queue because of Postmark failure.
        $this->addQueueItem($data);
        $response_message = 'Message sent to queue.';

      }
      else {
        // Message was sent successfully to sender via Postmark.
        $response_message = 'Message sent to sender.';
      }

      $response_array = [
        'status' => 'success',
        'response' => $response_message,
      ];

    else :

      $response_array = [
        'status' => 'error',
        'response' => 'wrong token could not authenticate',
      ];

    endif;

    return $response_array;

  }

  /**
   * Validate email params.
   *
   * @param array $emailFields
   *   The array containing Postmark API needed fieds.
   * @param string $server
   *   The server being called via the endpoint uri.
   */
  public function validateParams(array $emailFields, string $server) {
    $error = NULL;
    $required_fields = ['to_address', 'from_address', 'subject', 'message'];
    $check_fields = 0;

    foreach ($emailFields as $key => $value) {
      if (in_array($key, $required_fields)) {
        $check_fields++;
      }
    }

    if ($check_fields == 4) {
      foreach ($emailFields as $key => $value) {
        // Validate emails.
        if ($key == "to_address" || $key == "from_address") {
          $validate_email = preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $value);
          if (!$validate_email) {
            $error = "To and From email fields must be properly formatted.";
            break;
          }

        }
        else {
          // Check for blank fields.
          if ($value == "" && ($key !== "honey" && $key !== "token_session")) {
            $error = "Subject and Message fields must have values.";
            break;
          }
        }
      }

    }
    else {
      $error = "Missing required field params.";
    }

    if ($error == NULL) {
      return $this->formatData($emailFields, $server);
    }
    else {
      $response_array = [
        'status' => 'error',
        'response' => $error,
      ];
      return $response_array;
    }

  }

  /**
   * Begin script and API operations.
   *
   * @param string $server
   *   The server being called via the endpoint uri.
   */
  public function begin(string $server = 'contactform') {
    // Get POST data and check auth.
    $this->server = $server;

    $request_method = $this->request->getCurrentRequest()->getMethod();
    if ($request_method == "POST") :
      $data = $this->request->getCurrentRequest()->get('email');
      $response_array = $this->validateParams($data, $server);

    else :

      $response_array = [
        'status' => 'error',
        'response' => 'no post data',
      ];

    endif;

    $response = new CacheableJsonResponse($response_array);
    return $response;
  }

}

// End PostmarkAPI class.
