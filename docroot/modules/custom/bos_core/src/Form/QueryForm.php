<?php

namespace Drupal\bos_core\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;

/**
 * Admin Settings form for bos_core.
 *
 * @see https://developers.google.com/analytics/devguides/collection/protocol/v1/reference
 */

/**
 * Class BosCoreSettingsForm.
 *
 * @package Drupal\bos_core\Form
 */
class QueryForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bos_core_query';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ["bos_core.settings"];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('bos_core.settings');

    $form = [
      '#tree' => TRUE,
      'bos_core' => [
        '#type' => 'fieldset',
        '#title' => 'Content Search',
        '#description' => 'Run a deep search across site content components.',
        '#collapsible' => FALSE,

        "query" => [

          "search_text" => [
            '#type' => 'textfield',
            '#required' => TRUE,
          ],
          "search_button" => [
            "#type" => "button",
            '#value' => "Search",
            '#ajax' => [
              'callback' => '::makeSearch',
              'event' => 'click',
              'disable-refocus' => FALSE,
              'wrapper' => "edit-bos-core-query-search-button-results",
              'progress' => [
                'type' => 'throbber',
              ]
          ],
          "results" => [
            "#type" => "fieldset"
          ]
        ],
        ]
      ],
    ];
    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#attributes']=['style'=>['display:none']];
    return $form;
  }

  public function makeSearch(array &$form, FormStateInterface $form_state) {
    $search = $form_state->getValue("bos_core")["query"]["search_text"];
    $search = str_replace("%", "", $search);
    $results = $this->makeQuery($search, []);
    $formatted_results = $this->formatSearchResults($search, $results);
    $form["bos_core"]["query"]["results"] = [
      "#type" => "fieldset",
      "#attributes" => ["id" => "edit-bos-core-query-search-button-results"],
      "markup" => [
        "#markup" => Markup::create($formatted_results)
      ]
    ];
    return $form["bos_core"]["query"]["results"];
  }

  private function makeQuery(string $search, array $exclude) {

    $union = [];
    $query = "CREATE TEMPORARY TABLE COBQUERY \n";

    // Always search the node title
    $union[] = "SELECT nid component_id,  type content_type, 'node.title' component, title result, nid nid
	              FROM node_field_data
	              WHERE title LIKE @search";

    // Always search the node body
    $union[] = "SELECT entity_id component_id,  bundle content_type, 'node.body' component, body_value result, entity_id nid
	              FROM node__body
	              WHERE body_value like @search";

    // Search the node fields
    $node_tables = [
      ["table_name" => "node__field_description", "search_field"=>"field_description_value"],
      ["table_name" => "node__field_intro_text", "search_field"=>"field_intro_text_value"],
      ["table_name" => "node__field_details_link", "search_field"=>"field_details_link_uri"],
      ["table_name" => "node__field_related_links", "search_field"=>"field_related_links_uri"],
    ];
    foreach($node_tables as $table) {
      $type = str_replace("node__field_","", $table["table_name"]);
      $union[] = "
	      SELECT entity_id component_id,  bundle content_type, 'node.{$type}' component, {$table["search_field"]} result, entity_id nid
	      FROM {$table["table_name"]}
	      WHERE {$table["search_field"]} LIKE @search";
    }

    $paragraph_tables = [
      ["table_name" => "paragraph__field_intro_text", "search_field"=>"field_intro_text_value"],
      ["table_name" => "paragraph__field_extra_info", "search_field"=>"field_extra_info_value"],
      ["table_name" => "paragraph__field_left_column", "search_field"=>"field_left_column_value"],
      ["table_name" => "paragraph__field_middle_column", "search_field"=>"field_middle_column_value"],
      ["table_name" => "paragraph__field_right_column", "search_field"=>"field_right_column_value"],
      ["table_name" => "paragraph__field_short_description", "search_field"=>"field_short_description_value"],
      ["table_name" => "paragraph__field_source_url", "search_field"=>"field_source_url_value"],
      ["table_name" => "paragraph__field_external_link", "search_field"=>"field_external_link_uri"],
      ["table_name" => "paragraph__field_internal_link", "search_field"=>"field_internal_link_uri"],
      ["table_name" => "paragraph__field_source_url", "search_field"=>"field_source_url_value"],
    ];
    foreach($paragraph_tables as $para) {
      $find_parent = "IF(para.parent_type = 'node', para.parent_id,
                        IF(para2.parent_type = 'node', para2.parent_id,
                          IF(para3.parent_type = 'node', para3.parent_id,
                            IF(para4.parent_type = 'node', para4.parent_id,
                            para5.parent_id))))";
      $union[] = "
        SELECT DISTINCT
		        entity_id component_id
            , (SELECT type FROM node WHERE nid = $find_parent) content_type
		        , txt.bundle component, txt.{$para["search_field"]} result
		        , {$find_parent} node_id
        FROM {$para["table_name"]} txt
            INNER JOIN paragraphs_item_field_data para ON para.id = txt.entity_id
            LEFT OUTER JOIN paragraphs_item_field_data para2 ON para2.id = para.parent_id
            LEFT OUTER JOIN paragraphs_item_field_data para3 ON para3.id = para2.parent_id
            LEFT OUTER JOIN paragraphs_item_field_data para4 ON para4.id = para3.parent_id
            LEFT OUTER JOIN paragraphs_item_field_data para5 ON para5.id = para4.parent_id
        WHERE txt.{$para["search_field"]} LIKE @search
        HAVING node_id IS NOT NULL";
    }

    $query .= implode("\nUNION \n", $union);

    try {
      $conn = \Drupal::database();
      $conn->query("DROP TABLE IF EXISTS COBQUERY")->execute();
      $conn->query("SET @search = '%{$search}%'")->execute();

      // Do the search
      $qry = $conn->query($query);
    }
    catch (\Exception $e) {
      return FALSE;
    }

    // Set up exclusions
    $exclude_condition = "";
    if (!empty($exclude)) {
      $exclude_condition = implode("','", $exclude);
      $exclude_condition = "AND content_type NOT IN ('{$exclude_condition}') ";
    }

    // Reformat and filter the search results
    $query = "SELECT DISTINCT
	      content_type
	      , (SELECT CONCAT('https://boston.gov/node', alias) FROM path_alias WHERE path =  CONCAT('/node/', nid) AND status = 1) alias
        , (SELECT moderation_state FROM content_moderation_state_field_data WHERE content_entity_id = nid) mod_state
	      , component
	      , REPLACE(REPLACE(result, '\"', ''''), '\r\n', '') result
	      , nid
      FROM COBQUERY outs
      WHERE content_type IS NOT NULL {$exclude_condition}
      ORDER BY content_type, component";
    if ($qry = $conn->query($query)) {
      $result = $qry->fetchAll();
      return $result;
    }
    return FALSE;

  }

  private function formatSearchResults($search, $results) {
    if ($results) {
      $base = \Drupal::request()->getHttpHost();
      if ($base == "boston.gov" || $base = "www.boston.gov") {
        $base = "content.boston.gov";
      }
      $count = count($results);
      $output = "<h3>There were {$count} string matches for '{$search}' on pages in the site</h3><h4>The following list of pages has the search string in one or more components:</h4>";
      $output .= "<table><thead><tr><th>Page</th><th>Content Type</th><th>State</th><th>Actions</th></tr></thead>";
      $dedup = [];
      foreach ($results as $result) {
        if (!in_array($result->nid, $dedup)) {
          $page = ($result->alias != "" ? "{$result->alias} ({$result->nid})" : "NODE {$result->nid}");
          $alias = ($result->mod_state == "published" ? $result->alias : "https://{$base}/node/{$result->nid}");
          $output .= "<tr>";
          $output .= "<td>{$page}</td>
            <td>{$result->content_type} ({$result->component})</td>
            <td>{$result->mod_state}</td>
            <td>
              <a href='{$alias}' class='button'>View</a>
              <a href='https://{$base}/node/{$result->nid}/edit' class='button'>Edit</a>
            </td>";
          $output .= "</tr>";
          $dedup[] = $result->nid;
        }
      }
      $output .= "</table>";
    }
    else {
      $output = "Sorry the string '{$search}' was not found.<br/>Please alter the search and try again.";
    }
    return $output;
  }

}
