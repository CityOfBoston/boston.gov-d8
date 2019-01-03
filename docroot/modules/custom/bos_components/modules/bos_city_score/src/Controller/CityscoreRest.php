<?php

namespace Drupal\bos_city_score\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class CityscoreRest.
 *
 * @package Drupal\city_score\Controller
 */
class CityscoreRest extends ControllerBase {

  protected $action;

  /**
   * Magic function.  Catches calls to endpoints that dont exist.
   *
   * @param string $name
   *   Name.
   * @param mixed $arguments
   *   Arguments.
   */
  public function __call($name, $arguments) {
    throw new NotFoundHttpException();
  }

  /**
   * Extends the config function.
   *
   * @param string $name
   *   The config(setting) to be managed.
   *
   * @return array|\Drupal\Core\Config\Config|mixed|null
   *   The value of the setting being managed.
   */
  public function config($name) {
    $config = parent::config("cityscore.settings");
    if (isset($name)) {
      return $config->get($name);
    }
    return $config->getRawData();
  }

  /**
   * The main entrypoint for the controller.
   *
   * @param string $action
   *   The action being called via the endpoint uri.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The JSON output string.
   */
  public function api($action) {
    // Note: This allows any GET requests to run without any verification.
    $this->action = $action;
    if (in_array(\Drupal::request()->getMethod(), [
      "POST",
      "DELETE",
      "PUT",
    ])) {
      // Check IP.
      if (($ip_whitelist = $this->config('ip_whitelist')) && !empty($ip_whitelist)) {
        $ip_whitelist = explode("\r\n", $ip_whitelist);
        if (!in_array(\Drupal::request()->getClientIp(), $ip_whitelist)) {
          return $this->responseOutput("error ip not recognised", Response::HTTP_UNAUTHORIZED);
        }
      }
      // Check token.
      if (($token = $this->config('auth_token')) && empty(\Drupal::request()->get("api-key"))) {
        return $this->responseOutput("error missing token", Response::HTTP_UNAUTHORIZED);
      }
      elseif (\Drupal::request()->get("api-key") != $token) {
        return $this->responseOutput("error bad token", Response::HTTP_UNAUTHORIZED);
      }
      // Check payload.
      if (!\Drupal::request()->get('payload', FALSE)) {
        return $this->responseOutput("error no payload", Response::HTTP_BAD_REQUEST);
      }
      try {
        $payload = $this->cleanup(\Drupal::request()->get('payload', FALSE));
        if (!($payload = json_decode($payload))) {
          return $this->responseOutput("bad json in payload", Response::HTTP_BAD_REQUEST);
        }
      }
      catch (Error $e) {
        return $this->responseOutput("bad json in payload", Response::HTTP_BAD_REQUEST);
      }

      return $this->$action($payload);
    }
    return $this->$action();
  }

  /**
   * Cleans non-printing/reserved chars from a JSOn string.
   *
   * @param string $payload
   *   A json string to be tidied up before conversion.
   *
   * @return string
   *   The clean string which should now convert to JSON.
   */
  private function cleanup($payload) {
    $replacements = [
      '/[\n\t]/' => "",
      '/\"\s|\s\"/' => '"',
    ];

    foreach ($replacements as $regex_search => $replace) {
      $payload = preg_replace($regex_search, $replace, $payload);
    }

    $pos = min(strpos($payload, "["), strpos($payload, "{"));
    if ($pos !== FALSE && $pos > 0) {
      $payload = substr($payload, $pos);
    }

    return trim($payload);
  }

  /**
   * Processes the "load" action on the endpoint.
   *
   * @param array|object $payload
   *   The payload converted from a json string.
   *
   * @return string
   *   A JSON string which is returned to the caller.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function load($payload = []) {
    if (empty($payload)) {
      return $this->responseOutput("error no payload", Response::HTTP_BAD_REQUEST);
    }
    // Process payload into taxonomy.
    $result = [];
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('cityscore_metrics');
    foreach ($terms as $term) {
      $taxTerm = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid);
      $taxTerm->field_current = 0;
      $taxTerm->save();
    }
    foreach ($payload as $row) {
      $tax = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $row->metric_name, 'vid' => 'cityscore_metrics']);
      $result['count']++;
      if (empty($tax)) {
        // Create the record.
        $tax = [
          'vid' => "cityscore_metrics",
          'name' => $row->metric_name,
        ];
        $tax = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->create($tax);
        $result = $tax->save();
        if ($result != SAVED_NEW) {
          // Continue for now.  May fail later.
        }
      }
      else {
        $tax = array_values($tax)[0];
      }
      // Update the taxonomy term.
      if (isset($tax)) {
        $tax->field_calc_timestamp = strtotime($row->score_calculated_ts);
        $tax->field_table_timestamp = strtotime($row->score_final_table_ts);
        $tax->field_day = $row->score_day_name;
        $tax->field_previous_quarter = $row->previous_quarter_score;
        $tax->field_previous_month = $row->previous_month_score;
        $tax->field_previous_week = $row->previous_week_score;
        $tax->field_previous_day = $row->previous_day_score;
        $tax->field_cs_weight = $result['count'];
        $tax->field_current = 1;
        if ($tax->save() == SAVED_UPDATED) {
          $result['saved']++;
        }
      }
    }
    if ($result['saved'] != $result['count']) {
      return $this->responseOutput("Not all records saved", Response::HTTP_NON_AUTHORITATIVE_INFORMATION);
    }

    return $this->responseOutput("cityscore updated", Response::HTTP_CREATED);

  }

  /**
   * Helper: Formats a standardised Respose object.
   *
   * @param string $message
   *   Message to JSON'ify.
   * @param int $type
   *   Response constant for the HTTP Status code returned.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Full Response object to be returned to caller.
   */
  private function responseOutput($message, $type) {
    $json = [
      'status' => 'error',
      'message' => $message,
    ];
    $response = new Response(
      NULL,
      $type,
      [
        'Content-Type' => 'application/json',
        'Cache-Control' => 'must-revalidate, no-cache, private',
        'X-Generator-Origin' => 'City of Boston (https://www.boston.gov)',
        'X-COB-Cityscore' => $this->action,
        'Content-Language' => 'en',
      ]
    );
    switch ($type) {
      case Response::HTTP_CREATED:
      case Response::HTTP_OK:
      case Response::HTTP_NON_AUTHORITATIVE_INFORMATION:
        $json['status'] = 'success';
        $response->setContent(json_encode($json));
        break;

      case Response::HTTP_UNAUTHORIZED:
      case Response::HTTP_NO_CONTENT:
      case Response::HTTP_FORBIDDEN:
      case Response::HTTP_BAD_REQUEST:
      case Response::HTTP_METHOD_NOT_ALLOWED:
      case Response::HTTP_INTERNAL_SERVER_ERROR:
        $json['status'] = 'error';
        $response->setContent(json_encode($json));
        break;
    }
    return $response;
  }

  /**
   * Provides the help page for the module.
   *
   * @return array
   *   Array to be rendered as the help page.
   */
  public static function helpPage() {
    return [
      'help_page' => [
        '#tree' => TRUE,
        '#type' => 'fieldset',
        '#title' => "About CityScore Component",
        '#markup' => "<p>Cityscore Drupal 8 module defines a Taxonomy Vocabulary, a Paragraph component, Permissions and some API endpoints.<ul>
                      <li><i>Cityscore_metrics</i> <b>taxonomy</b> with customised field to store the current cityscore metrics values.</li>
                      <li><b>Paragraph</b> component which can be used to add Cityscore table to pages.</li>
                      <li>REST <b>API</b> endpoints to allow external updating and retrieval of cityscore metrics.</li>
                      </ul></p>
                      <h3>Taxonomy.</h3>
                      <p>Cityscore data is stored in the <b>cityscore_metrics</b> customized Taxonomy. Data may be added/updated using the <b>load</b> API endpoint, and users with \"<i>administer cityscore</i>\" permissions can add/edit data in the Taxonomy via the GUI.</p>
                      <h3>Paragraph.</h3>
                      <p>Cityscore Paragraph can be enabled in any \"components\" field on any page.  It is a large table with a graphical metric display and hence is usually the only component added to a page.<br>
                      Content is controlled by a Cityscore <b>View</b>.</p>
                      <h3>RESTful endpoints.</h3>
                      <p>API Endpoints are used so that external entities can update or retrieve the latest CityScore data.  Updating is controlled to registered users, but data retrieval is unsecured.</p>
                      <p>The API endpoints can be accessed from <b>" . \Drupal::request()->getSchemeAndHttpHost() . "/{endpoint}</b> where <b>{endpoint}</b> is: 
                      <ul>
                      <li><b>rest/cityscore/load</b>|<i>(secured|POST)</i> - Allows someone with a token and correctly formatted payload to update cityscore taxonomy items.<br/>
                      <span style='margin-left: 25px'>Requires a correctly formatted POST message with an <b>api-key</b> string and <b>payload</b> JSON string.<br/>
                      Returns JSON message and uses HTTP response codes in header.<br/>
                      Note: requires an api-key and request must come from a registered IPAddress (click Admin Pages link below to configure).</span></li>
                      <li><b>rest/views/cityscore/metrics/latest</b>|<i>(public/unsecured|GET)</i> - Returns a JSON string which is an array of objects. Each object is a current metric.</li>
                      <li><b>rest/views/cityscore/html-table</b>|<i>(public/unsecured|GET)</i> - Returns same data as above, but as an HTML table with CoB themeing.</li>
                      <li><b>rest/views/cityscore/totals/latest</b>|<i>(public/unsecured|GET)</i> - Returns a JSON string which an object containing the cityscore value for today.</li>
                      </ul></p>
                      <p>Endpoint content is controlled by a Cityscore <b>View</b>.</p>",
      ],
    ];
  }

}
