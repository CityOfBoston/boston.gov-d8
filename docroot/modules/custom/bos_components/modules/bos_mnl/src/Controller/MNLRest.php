<?php

namespace Drupal\bos_mnl\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * MNLRest class for endpoint.
 */
class MNLRest extends ControllerBase {

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
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('request_stack')
    );
  }

  /**
   * Checks allowed domains to access endpoint.
   */
  public function checkDomain() {
    $allowed = [
      'https://www.boston.gov',
    ];

    if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Load the payload from the rest endpoint into the appropriate queue.
   *
   * @param string $operation
   *   The operation from the endpoint call.
   *
   * @return \Drupal\Core\Cache\CacheableJsonResponse
   *   A json response to send back to the caller.
   */
  public function beginUpdateImport(string $operation) {
    ini_set('memory_limit', '-1');
    ini_set("max_execution_time", "10800");
    ini_set("post_max_size", "2000M");
    ini_set("upload_max_filesize", "2000M");

    $apiKey = $this->request->getCurrentRequest()->get('api_key');
    $token = Settings::get('mnl_key');

    // Get request method.
    $request_method = $this->request->getCurrentRequest()->getMethod();

    // Get POST data and decode in to JSON.
    if ($operation != "manual") {
      $data = $this->request->getCurrentRequest()->getContent();
      $data = json_decode(strip_tags($data), TRUE);
    }
    else {
      \Drupal::queue('mnl_import')->deleteQueue();
      \Drupal::queue('mnl_cleanup')->deleteQueue();
      $path = \Drupal::request()->get("path");
      $limit = \Drupal::request()->get("limit");
      if (NULL != $path && file_exists($path)) {
        $data = file_get_contents($path);
        $data = json_decode($data);
        if (NULL != $limit) {
          foreach ($data as $key => $dataitem) {
            $data2[$key] = $dataitem;
            if (count($data2) == $limit) {
              unset($data);
              $data = $data2;
              break;
            }
          }
        }
      }
      else {
        $response_array = [
          'status' => 'error',
          'response' => 'file not found at path',
        ];
      }
    }

    // Test and load into queue.
    if (isset($data)) {
      if ($apiKey !== $token || $apiKey == NULL) {
        $response_array = [
          'status' => 'error',
          'response' => 'wrong api key',
        ];
      }

      elseif ($request_method != "POST") {
        $response_array = [
          'status' => 'error',
          'response' => 'request must be POST',
        ];
      }

      elseif ($operation == "import" || $operation == "manual") {
        \Drupal::queue('mnl_cleanup')->deleteQueue();
        $queue = \Drupal::queue('mnl_import');
      }

      elseif ($operation == "update") {
        $queue = \Drupal::queue('mnl_update');
      }

      else {
        $response_array = [
          'status' => 'error',
          'response' => 'unknown endpoint requested',
        ];
      }

      // Finally.
      if (isset($queue)) {
        foreach ($data as $items) {
          // Add item to queue.
          $queue->createItem($items);
        }
        \Drupal::logger("mnl import")
          ->info("[0] REST $operation Import loaded "  . count($data) . " SAM records into mnl_import queue.");
        \Drupal::logger("mnl import")
          ->info("[0] mnl_import queue now contains "  . $queue->numberOfItems() . " SAM records to be processed.");

        $response_array = [
          'status' => $operation . ' complete - ' . count($data) . ' items queued',
          'response' => 'authorized'
        ];
      }

    }

    $response = new CacheableJsonResponse($response_array);
    return $response;
  }

}
