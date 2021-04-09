<?php

namespace Drupal\bos_email\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Drupal\bos_email\Templates\Contactform;

/**
 * Create, remove, and get valid session token.
 */
class TokenOps extends ControllerBase {
  
  public $session;

  /**
   * Public construct for Session.
   */
  public function __construct() {
    $this->session = \Drupal::request()->getSession();
  }

  public function tokenCreate() {
    $date_time = \Drupal::time()->getCurrentTime();
    $token_name = 'token_session_'.$date_time;
  
    $this->session->set($token_name, $date_time);
    $response_token =  [
      'token_session' => $date_time
    ];

    return $response_token;
  } 

  public function tokenRemove(string $data) {
    $test = $this->session->remove('token_session_'.$data);
    if ($test !== null) {
      $response_token =  [
        'token_session' => "removed"
      ];
    } else {
      $response_token =  [
        'token_session' => "not found"
      ];
    }


    return $response_token;
  } 

  public function tokenGet(string $data) {
      if ($data !== NULL) {
        if ($this->session->get('token_session_'.$data)) {

          $response_token =  [
            'token_session' => TRUE,
          ];
        } else {
          $response_token =  [
            'token_session' => FALSE,
          ];

        }
      } else {
        $response_token =  [
            'token_session' => NULL,
          ];
      }

      return $response_token;
  }

}
// End TokenOps.
