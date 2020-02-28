<?php

namespace Drupal\bos_mnl\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\node\Entity\Node;
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
  public function beginImport() {
    $testing = TRUE;
    if ($this->checkDomain() == TRUE || $testing == TRUE) :
      // Get POST data.
      $apiKey = $this->request->getCurrentRequest()->get('api_key');
      $token = "Au47a8x38E";
      $request_method = $this->request->getCurrentRequest()->getMethod();
      if ($apiKey !== $token) {
        $response_array = [
          'status' => 'error',
          'response' => 'wrong api key',
        ];

      }
      elseif (!$request_method == "POST") {
        $response_array = [
          'status' => 'error',
          'response' => 'no post data',
        ];

      }
      elseif (!$apiKey == NULL && $request_method == "POST") {
        $data = $this->request->getCurrentRequest()->getContent();
        $data = json_decode(strip_tags($data), TRUE);

        $query = \Drupal::entityQuery('node')->condition('type', 'neighborhood_lookup');
        $nids = $query->execute();
        $exists = FALSE;
        $nodeID = NULL;

        if (json_last_error() === 0) {
          foreach ($data as $items) {
            foreach ($nids as $nid) {
              $node = Node::load($nid);
              $sam_id = $node->field_sam_id->value;
              if ($sam_id == $items['sam_address_id']) {
                $exists = TRUE; $nodeID = $nid;
              }
            }

            if ($exists == TRUE) {
              $this->updateNode($nodeID, $items);
            }
            else {

              $this->createNode($nodeID, $items);
            }

            $exists = FALSE;
          }
          $response_array = [
            'status' => 'procedure complete',
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
