<?php

namespace Drupal\bos_mnl\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * MNLRest class for endpoint.
 *
 * All POSTS to endpoint must have format:
 *   /rest/mnl/YYY?api_key=XXX
 *   Where YYYY is one of 'update', 'import' or 'manual'.
 *   Where api_key XXX is the token defined at /admin/config/services/mnl.
 *
 * Note: 'import' and 'update' endpoint usage must include a json string in the
 *   body in the format:
 *   [
 *     {"sam_address_id":123,"full_address":"address","data": jsonstring"},
 *     {"sam_address_id":123,"full_address":"address","data": jsonstring"}
 *   ]
 *   The 'jsonstring' must be a valid json object in string form and can contain
 *   any data provided it represents a single record and can be decoded by
 *   PHP's json_decode function.
 *
 * Note: provide a querystring for "manual" endpoint in format:
 *   ?api_key=XXXXX&path=/path-on-server/file.json&limit=N&mode=import
 *   Required:
 *     'path' is path on remote server (or local container) to import file.
 *   Optional:
 *     'limit' process the first N records in the import file (defaults all).
 *     'mode' can be either 'import' or 'update' (defaults to 'update').
 *
 * Note:
 *  - UPDATE updates "full address" and "data" for SAM records in DB with
 *    "full address" and "data" from records with a matchin "sam_address_id"
 *    from the json payload. New records will be created if an existing
 *    "sam_address_id" is not found in the DB. If there are duplicate
 *    "sam_address_id"s in the import, only the first of the duplicate records
 *    will be processed. No records will be deleted or marked for deletion.
 *  - IMPORT also updates "full address" and "data" for SAM records in DB with
 *    "full address" and "data" from records with a matchin "sam_address_id"
 *    from the json payload. New records will also be created if an existing
 *    "sam_address_id" is not found in the DB. If there are duplicate
 *    "sam_address_id"s in the import, only the first of the duplicate records
 *    will be processed.  If the database contains a record (ie. a
 *    "sam_address_id") which is not in the import file, then after the import
 *    completes, those "orphaned" records will be cleaned, by removing them
 *    from the database.
 *
 * Note: Actual updating and deleting (when done) will be handled by queues that
 *   are processed overnight by a scheduled task, so the endpoint only queues
 *   tasks for later execution, it does not actually change data itself.
 *
 * Benchmarks 16 May 2020:
 *   - Acquia/AWS Uploaded 1.4GB file with 400,000 records in 12 secs.
 *   - Drupal: 400,000 records queued in less than 00:03:30 (3.5 mins).
 *   - mnl_import queue processes (local) so acquia will be 5-10x faster:
 *        - processed 395,037 records with 5,000 updates in 10 mins
 *        - processed 395,037 records with 395,037 updates in 15 mins
 *        - processed 395,037 records with 395,037 inserts (new nodes) - 58 min
 *        - processed 50 deletes in 10sec
 */
class MNLRest extends ControllerBase {

  /* Civis API Key
  https://api.civisanalytics.com
  z_pUmFpEyRm1h_qRBOq2ZFc8oxOkh9LoyyCe1MnqAGw
  */

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
    ini_set("post_max_size", "2048M");
    ini_set("upload_max_filesize", "2048M");

    \Drupal::logger("bos_mnl")
      ->info("[0] REST $operation Import initialized.");

    $apiKey = $this->request->getCurrentRequest()->get('api_key');
    $token = \Drupal::config("bos_mnl.settings")->get("auth_token");

    // Get request method.
    $request_method = $this->request->getCurrentRequest()->getMethod();

    // Get POST data and decode in to JSON.
    if ($operation != "manual") {
      $data = $this->request->getCurrentRequest()->getContent();
      $data = json_decode(strip_tags($data), TRUE);
    }
    else {
      \Drupal::queue('mnl_cleanup')->deleteQueue();
      $path = \Drupal::request()->get("path", FALSE);
      $limit = \Drupal::request()->get("limit", FALSE);
      $operation = (\Drupal::request()->get("mode", FALSE) ?: "update");
      if ($path && file_exists($path)) {
        $data = file_get_contents($path);
        $data = json_decode($data);
        if ($limit) {
          $data = array_slice($data, 0, $limit);
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

      elseif ($operation == "import") {
        $queue_name = 'mnl_import';
        $queue = \Drupal::queue($queue_name);
      }

      elseif ($operation == "update") {
        $queue_name = 'mnl_update';
        $queue = \Drupal::queue($queue_name);
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
        \Drupal::logger("bos_mnl")
          ->info("REST payload contained " . number_format(count($data), 0) . " SAM records. <br>Loaded " . number_format($queue->numberOfItems()) . " records with unique SAM ID's into queue $queue_name");

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
