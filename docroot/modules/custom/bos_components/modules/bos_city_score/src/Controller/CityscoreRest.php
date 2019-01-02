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
    $headers = ['Content-Type' => 'application/json'];

    if (in_array(\Drupal::request()->getMethod(), [
      "POST",
      "DELETE",
      "PUT",
    ])) {
      // Check IP.
      if (($ip_whitelist = $this->config('ip_whitelist')) && !empty($ip_whitelist)) {
        $ip_whitelist = explode("\r\n", $ip_whitelist);
        if (!in_array(\Drupal::request()->getClientIp(), $ip_whitelist)) {
          $response = new Response(
            $this->jsonError("error ip not recognised"),
            Response::HTTP_UNAUTHORIZED,
            $headers
          );
          return $response;
        }
      }
      // Check token.
      if (($token = $this->config('auth_token')) && empty($token)) {
        $response = new Response(
          $this->jsonError("error missing token"),
          Response::HTTP_NO_CONTENT,
          $headers
        );
        return $response;
      }
      elseif ($_REQUEST["api-key"] != $token) {
        $response = new Response(
          $this->jsonError("error bad token"),
          Response::HTTP_UNAUTHORIZED,
          $headers
        );
        return $response;
      }
      // Check payload.
      if (!\Drupal::request()->get('payload', FALSE)) {
        $response = new Response(
          $this->jsonError("error no payload"),
          Response::HTTP_NO_CONTENT,
          $headers
        );
        return $response;
      }
      try {
        $payload = $this->cleanup(\Drupal::request()->get('payload', FALSE));
        if (!($payload = json_decode($payload))) {
          $response = new Response(
            $this->jsonError("bad json in payload"),
            Response::HTTP_NO_CONTENT,
            $headers
          );
          return $response;
        }
      }
      catch (Error $e) {
        $response = new Response(
          $this->jsonError("bad json in payload"),
          Response::HTTP_NO_CONTENT,
          $headers
        );
        return $response;
      }
      $response = new Response(
        $this->$action($payload),
        Response::HTTP_OK,
        $headers
      );
      return $response;
    }

    $response = new Response(
      $this->$action(),
      Response::HTTP_OK,
      $headers
    );
    return $response;
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
      return $this->jsonError('error no payload');
    }
    // Process payload into taxonomy.
    $result = [];
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('cityscore_metrics');
    foreach ($terms as $term) {
      $termm = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($term->tid);
      $termm->field_current = 0;
      $termm->save();
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
      return $this->jsonError("Not all records saved");
    }

    return json_encode([
      "status" => "success",
      "message" => "cityscore updated",
    ]);
  }

  /**
   * Helper: Formats a standardised error as a json string.
   *
   * @param string $error
   *   Error message to JSON'ify.
   *
   * @return string
   *   JSON formatted error message.
   */
  private function jsonError($error) {
    $json = [
      'status' => 'error',
      'message' => $error,
    ];
    return json_encode($json);
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
        '#title' => "About Slack Poster",
        '#markup' => "<p>Cityscore module defines a Taxonomy Vocab, a paragraph component and some API endpoints.<ul>
                      <li>Cityscore_metrics taxonomy with customised field to store the current cityscore metrics values.</li>
                      <li>Paragraph component which can be used to add Cityscore table to pages.</li>
                      <li>REST API endpoints to allow external updating and retrieval of cityscore metrics.</li>
                      </ul></p>
                      <h3>RESTful endpoints.</h3>
                      <p>The API endpoints can be accessed from " . \Drupal::request()->getSchemeAndHttpHost() ."/{endpoint} where {endpoint} is: 
                      <ul>
                      <li>'rest/cityscore/load' - Allows someone with a token and correctly formatted payload to update cityscore taxonomy items.  Note: requires a token and must come from a registered IPAddress.</li>
                      <li>'rest/views/cityscore/metrics/latest' - Provides a JSON string which is an array of objects. Each object is a current metric.</li>
                      <li>'rest/views/cityscore/html-table' - Same data as above, but as an HTML table with CoB themeing.</li>
                      <li>'rest/views/cityscore/totals/latest' - Provides a JSON string which an object containing the cityscore value for today.</li>
                      </ul></p>",
      ],
    ];
  }
}
