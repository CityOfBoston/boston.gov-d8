<?php

namespace Drupal\node_rollcall\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Cache\CacheableJsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Uploader class for endpoint.
 *
 */
class Uploader extends ControllerBase {

  /**
   * Class var.
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
  public static function create(ContainerInterface $container): Uploader|static {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('request_stack')
    );
  }

  public function upload(): CacheableJsonResponse {
    $output = $this->validateToken();
    if ($output->getStatusCode() != 200) {
      return $output;
    }

    $payload = $this->request->getCurrentRequest()->getContent();

    return $output;
  }

  /**
   * Expect a token to be sent in as part of the querystring, or part of the header.
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *
   */
  private function validateToken() {
    if ($apikey = $this->request->getCurrentRequest()->headers->get("authorization")) {
      $apikey = explode(" ", $apikey)[1];
    }
    else {
      $apiKey = $this->request->getCurrentRequest()->get('api_key');
    }
    $token = \Drupal::config("node_rollcall.settings")->get("auth_token");
    //    \Drupal::configFactory()->getEditable('node_rollcall.settings')->set("auth_token", "abc")->save();
    if ($apiKey !== $token || $apiKey == NULL) {
      return new CacheableJsonResponse([
        'status' => 'error',
        'response' => 'Could not authenticate',
      ], 401);
    }
    return new CacheableJsonResponse([], 200, [], false);
  }

}
