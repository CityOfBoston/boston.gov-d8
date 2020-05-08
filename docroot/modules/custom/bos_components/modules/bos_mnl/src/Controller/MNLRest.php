<?php

namespace Drupal\bos_mnl\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\node\Entity\Node;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Queue\DatabaseQueue;
use Drupal\Core\Queue\QueueWorkerInterface;
use Drupal\Core\Queue\QueueWorkerManagerInterface;

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
   * Update node.
   */
  public function updateNode($nid, $dataJSON) {
    $entity = Node::load($nid);
    $entity->set('field_sam_id', $dataJSON['sam_address_id']);
    $entity->set('field_sam_address', $dataJSON['full_address']);
    $entity->set('field_sam_neighborhood_data', json_encode($dataJSON['data']));
    $entity->save();
  }

  /**
   * Create new node.
   */
  public function createNode($dataJSON) {
    $node = Node::create([
      'type'                        => 'neighborhood_lookup',
      'title'                       => $dataJSON['sam_address_id'],
      'field_sam_id'                => $dataJSON['sam_address_id'],
      'field_sam_address'           => $dataJSON['full_address'],
      'field_sam_neighborhood_data' => json_encode($dataJSON['data']),
    ]);
    $node->save();
  }

  /**
   * Begin import and parse POST data.
   */
  public function beginUpdateImport($operation) {
    $testing = TRUE;
    ini_set('memory_limit', '-1');
    ini_set("max_execution_time", "10800");
    ini_set("post_max_size", "2000M");
    ini_set("upload_max_filesize", "2000M");

    if ($this->checkDomain() == TRUE || $testing == TRUE) :
      // Get POST data.
      $apiKey = $this->request->getCurrentRequest()->get('api_key');
      $token = Settings::get('mnl_key');
      // Get request method.
      $request_method = $this->request->getCurrentRequest()->getMethod();
      // Get POST data and decode in to JSON.
      $data = $this->request->getCurrentRequest()->getContent();
      $data = json_decode(strip_tags($data), TRUE);
      // Get Neighborhood Lookup content type.
      $query = \Drupal::entityQuery('node')->condition('type', 'neighborhood_lookup');
      $nids = $query->execute();

      if ($apiKey !== $token || $apiKey == NULL) {
        $response_array = [
          'status' => 'error',
          'response' => 'wrong api key',
        ];
      }
      /*
      elseif ($request_method == "POST" && $operation == "update") {
      if (json_last_error() === 0) {
      $exists = NULL;
      foreach ($data as $items) {
      foreach ($nids as $nid) {
      $node = Node::load($nid);
      $sam_id = $node->field_sam_id->value;
      if ($sam_id == $items['sam_address_id']) {
      $this->updateNode($nid, $items);
      $exists = TRUE;
      }
      }
      if ($exists == FALSE) {
      $this->createNode($items);
      }
      $exists = FALSE;
      }
      $response_array = [
      'status' => $operation . ' procedure complete',
      'response' => 'authorized'
      ];
      }
      else {
      $response_array = [
      'status' => 'error',
      'response' => 'authorized',
      'error json' => json_last_error()
      ];
      }
      }
       */
      elseif ($request_method == "POST" && ($operation == "import" || $operation == "update")) {
        // Create JSON files in local directory.
        $current = 0;
        foreach (array_chunk($data, 100) as $items) {
          $currentIndex = time() . $current++;
          $filePath = \Drupal::root() . '/sites/default/files/mnl/data_' . $currentIndex . '.json';
          $file = fopen($filePath, "w");
          fwrite($file, "[");
          foreach ($items as $item) {
            fwrite($file, json_encode($item) . ",");
          }
          // Removed last comma.
          $position = fstat($file)['size'] - 1;
          ftruncate($file, $position);
          fseek($file, $position);
          fwrite($file, "]");
          fwrite($file, $data);
          fclose($file);
        }

        $response_array = [
          'status' => $operation . ' procedure complete',
          'response' => 'authorized'
        ];
      }

      elseif ($request_method == "POST" && ($operation == "import-queue" || $operation == "update-queue")) {
        // Get and remove any exisitig MNL related queues.

        if ($operation == "import-queue") {
          $queue = \Drupal::queue('mnl_import');
          $queue->deleteQueue();

          $queueNodes = \Drupal::queue('mnl_nodes');
          $queueNodes->deleteQueue();
        }
        else {
          $queue = \Drupal::queue('mnl_update');
          $queue->deleteQueue();
        }

        // Get local JSON data files for import and create Queue items.
        $path = \Drupal::root() . '/sites/default/files/mnl/';
        $dir = new \DirectoryIterator($path);
        foreach ($dir as $file) {
          if (!$file->isFile()) {
            continue;
          }
          $filename = $file->getFilename();
          $dataImportFile = file_get_contents($path . $filename);
          $dataImportFile = json_decode(strip_tags($dataImportFile), TRUE);

          foreach ($dataImportFile as $items) {
            // Create item to queue.
            $queue->createItem($items);
          }
          unlink($path . $filename);
        }

        $queueTotal = $queue->numberOfItems();
        $response_array = [
          'status' => 'queue complete - ' . $queueTotal,
          'response' => 'authorized'
        ];

      }

      else {
        $response_array = [
          'status' => 'error',
          'response' => 'unknown error',
        ];
      }

    else :
      $response_array = [
        'status' => 'error',
        'response' => 'not authorized',
      ];
    endif;

    $response = new CacheableJsonResponse($response_array);
    return $response;
  }

  // End import.
}

// End MNLRest class.
