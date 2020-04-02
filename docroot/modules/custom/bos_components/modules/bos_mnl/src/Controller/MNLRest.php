<?php

namespace Drupal\bos_mnl\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\node\Entity\Node;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Bibblio class for API.
 */
class MNLRest extends ControllerBase {

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
  public function createNode($nid, $dataJSON) {
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

      if ($apiKey !== $token) {
        $response_array = [
          'status' => 'error',
          'response' => 'wrong api key',
        ];

      }
      elseif (!$apiKey == NULL && $request_method == "POST" && $operation == "update") {
        ini_set('memory_limit', '-1');

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
              $this->createNode($nid, $items);
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
      elseif (!$apiKey == NULL && $request_method == "POST" && $operation == "import") {
        ini_set('memory_limit', '-1');
        // Delete all nodes of content type neightborhood_lookup.
        /*foreach ($nids as $nid) {
        $node = Node::load($nid);
        $node->delete();
        }

        $dataImportPath = \Drupal::root() . '/modules/custom/bos_components/modules/bos_mnl/data/data.json';
        $dataImportFile = file_get_contents($dataImportPath);
        $dataImportFile = json_decode(strip_tags($dataImportFile), TRUE);

        foreach ($dataImportFile as $items) {
        $this->createNode($nid, $items);
        }*/

        $filePath = \Drupal::root() . '/sites/default/files/data_matt.json';
        $file = fopen($filePath, "w");
        fwrite($file, "[");
        foreach ($data as $items) {
          fwrite($file, json_encode($items) . ",");
        }

        // Removed last comma.
        $position = fstat($file)['size'] - 1;
        ftruncate($file, $position);
        fseek($file, $position);
        fwrite($file, "]");
        fwrite($file, $data);
        fclose($file);

        $response_array = [
          'status' => $operation . ' procedure complete',
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
