<?php

namespace Drupal\bos_contactform\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Site\Settings;
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
   * Send email via Postmark API.
   *
   * @param array $emailFields
   *   The array containing Postmark API needed fieds.
   */
  public function sendEmail(array $emailFields) {

    if (isset($_ENV['POSTMARK_SETTINGS'])) {
      $postmark_env = [];
      $get_vars = explode(",", $_ENV['POSTMARK_SETTINGS']);
      foreach ($get_vars as $item) {
        $json = explode(":", $item);
        $postmark_env[$json[0]] = $json[1];
      }
    }
    else {
      $postmark_env = [
        "token" => Settings::get('postmark_token'),
        "auth" => Settings::get('postmark_auth'),
        "domain" => Settings::get('postmark_domain'),
      ];
    }

    $postmark_env = json_decode(json_encode($postmark_env));
    $rand = substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, 12);
    $htmlMessage = strpos($emailFields["message"], "<div");
    $auth = ($_SERVER['HTTP_AUTHORIZATION'] == "Token " . $postmark_env->auth ? TRUE : FALSE);
    $data = [
      "TemplateID" => 20439969,
      "TemplateModel" => [
        "subject_custom" => $emailFields["subject"],
        "TextBody" => $emailFields["message"],
      ],
      "To" => $emailFields["to_address"],
      "From" => "Boston.gov Contact Form <" . $rand . "@" . $postmark_env->domain . ">",
      "ReplyTo" => $emailFields["from_address"],
    ];

    if ($auth == TRUE) :

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, "https://api.postmarkapp.com/email/withTemplate");
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($ch, CURLOPT_HEADER, FALSE);
      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
      curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Accept: application/json",
        "Content-Type: application/json",
        "X-Postmark-Server-Token: " . $postmark_env->token,
      ]);
      $response = curl_exec($ch);

      $response_array = [
        'status' => 'success',
        'response' => $response,
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
   */
  public function validateParams(array $emailFields) {
    $error = NULL;

    if (count($emailFields) == 4) {
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
      $error = "Missing field params.";
    }

    if ($error == NULL) {
      return $this->sendEmail($emailFields);
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
   */
  public function begin() {
    // Get POST data and check auth.

    $request_method = $this->request->getCurrentRequest()->getMethod();
    if ($request_method == "POST") :
      $data = $this->request->getCurrentRequest()->get('email');
      $response_array = $this->validateParams($data);

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
