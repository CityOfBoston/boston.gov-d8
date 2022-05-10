<?php

namespace Drupal\bos_remote_search_box\Controller;

class RemoteSearchBoxCallback {

  /**
   * This class function is defined in the routing file -
   *    bos_remote_search_box.routing.yml
   * and used to process requests to https://boston.gov/ajax/rsb/{app}/{action}
   * The $app is used to identify the class to callback to, and the $action is
   * passed so that the class can process multiple action types.
   *
   * @param $app string The Classname for the custom form controller
   * @param $action string The action as passed from the url called.
   *
   * @return \http\Client\Response a response to return to caller
   */
  public function processor($app, $action) {
    $form_controller = '\Drupal\bos_remote_search_box\Form\\' . $app;
    $obj = new $form_controller;
    // linkCallBack is actually processed in RemopteSearchBoxFormBase class and
    // then redirects on to the custom form class endpoint() function.
    return $obj->linkCallback($action);
  }

}
