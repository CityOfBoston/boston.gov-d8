<?php

namespace Drupal\bos_email\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\bos_email\Templates\Contactform;
use Drupal\bos_email\Controller\PostmarkVars;
use Drupal\Core\Cache\CacheableJsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal;

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
  public function sendEmail(array $emailFields, string $server) {

    $postmark_env = new PostmarkVars();
    $auth = ($_SERVER['HTTP_AUTHORIZATION'] == "Token " . $postmark_env->varsPostmark()["auth"] ? TRUE : FALSE);
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

    if ($auth == TRUE) :
      $data["server"] = $server;
      // Add email data to queue in case of Postmark failure.
      $queue_item = $this->addQueueItem($data);

      $database = \Drupal::database();
      $query = $database->query("SELECT data FROM queue WHERE item_id = $queue_item AND expire = 0");
      $result = $query->fetchAll();
      if ($result) {

        $time = time() + 30;
        $database->query("UPDATE queue SET expire = $time WHERE item_id = $queue_item AND expire = 0");

        $queue_factory = \Drupal::service('queue');
        $queue_manager = \Drupal::service('plugin.manager.queue_worker');
        $queue_worker = $queue_manager->createInstance('email_contactform');
        $queue = $queue_factory->get('email_contactform');

        $data_process = unserialize($result[0]->data);
        $process_item = $queue_worker->processItem($data_process);

        if ($process_item) {
          $database->query("DELETE FROM queue WHERE item_id = $queue_item");
        }
        else {
          $database->query("UPDATE queue SET expire = 0 WHERE item_id = $queue_item");
        }
      }

      $response_array = [
        'status' => 'success',
        'response' => $process_item,
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
          if ($value == "") {
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
      return $this->sendEmail($emailFields, $server);
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
  public function begin(string $server) {
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
