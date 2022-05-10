<?php

namespace Drupal\bos_remote_search_box\Form;

use Drupal\bos_remote_search_box\RemoteSearchBoxFormInterface;
use Drupal\bos_sql\Controller\SQL;
use Drupal\Core\Form\FormStateInterface;
use Drupal\bos_remote_search_box\Util\RemoteSearchBoxHelper as helper;
use http\Client\Response;
use http\Message\Body;

/**
 * Class StreetSweepingLookup.
 *
 * @package Drupal\bos_remote_search_box\Form
 */
class StreetSweepingLookup extends RemoteSearchBoxFormBase implements RemoteSearchBoxFormInterface {

  /**
   * Const string which is the ConnToken for the DBConnector component used
   * by the SQL Class.
   */
  const connStr = "BE60DC60-2541-4A2F-B6EF-B931D2F9BAB8";

  /**
   * @var string The record ID for an individual  record search.
   */
  private $record_id;

  /**
   * Implements getFormId().
   */
  public function getFormId() {
    return 'street_sweeping_lookup';
  }

  /**
   * Implements buildForm()
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $this->form_title = "Street Sweeping Lookup";

    $form = parent::buildForm($form, $form_state);

    // Identify the step:
    if (NULL === $this->step) {
      foreach($this->user_input as $key=>$value) {
        if (is_string($value) && substr($key, 0,3) == "id_") {
          $this->record_id = explode("_", $key,2)[1];
          break;
        }
      }
      if (!empty($this->record_id)) {
        $this->step = "record";
      }
    }

    $form = helper::buildAddressSearch($form, $this, FALSE);
    // We need a slight different title on the address search textbox.
    $form["search_criteria_wrapper"]["search"]["searchbox"]["#title"] = t("Street to search for");
    $form["search_criteria_wrapper"]["search"]["searchbox"]["#attributes"]['placeholder'] = t('Enter Street Name');

    $criteria = helper:: criteriaNeighborhoodSelect($this,1, FALSE);
    $form = helper::addManualCriteria($form, $criteria);
    $criteria = helper:: criteriaWeekdayCheckbox($this,2, FALSE);
    $form = helper::addManualCriteria($form, $criteria);
    $form = helper::addManualCriteria($form, [
      'ordinal' => [
        '#type' => 'checkboxes',
        '#label' => $this->t('Ordinal Number (Optional)'),
        '#weight' => 3,
        '#required' => FALSE,
        '#attributes' => [
          'class' => [
            'cb-f',
          ],
          'bundle' => $this->getFormId(),
        ],
        '#options' => [
          '1' => $this->t('1st'),
          '2' => $this->t('2nd'),
          '3' => $this->t('3rd'),
          '4' => $this->t('4th'),
          '5' => $this->t('5th'),
        ],
      ]
    ]);
    // Add this in now so that the click event is registered for ajax.
    helper::addSearchResults($form, [
      '#attributes' => [
        'class' => [
          'g',
          'visually-hidden',
        ],
        'bundle' => $this->getFormId(),
      ],
      '#type' => 'container',
      'email' => [
        "#type" => "email",
        "#required" => FALSE,
        "#weight" => 0,
        "#attributes" => [
          "id" => "subscribeEmail",
          "name" => "subscribeEmail",
        ],
      ],
      'time' => [
        '#type' => 'select',
        '#weight' => 1,
        '#title' => t('Select a receipt time'),
        '#attributes' => [
          'class' => [
            'sel-f',
            'sel-f--fw',
          ],
          'bundle' => $this->getFormId(),
        ],
        '#options' => [
          '11' => "11 am",
          '2' => "2 pm",
          '5' => "5 pm",
        ],
        '#empty_option' => 'Select Time',
      ],
      'subscribe' => [
        "#type" => "submit",
        "#value" => "Subscribe",
        "#ajax" => [
          "callback" => [$this, "::subscribeEmail"],
          'disable-refocus' => TRUE,
//          'prevent' => "submit",
          'event' => "click",   // Allows clicking enter key to submit form.
          'progress' => ['type' => 'throbber'],
          'effect' => "fade",
          'wrapper' => $this->getFormId(),
        ],
        '#disabled' => FALSE,
        '#attributes' => [
          'class' => [
            'form__button--bos-remote_search_box',
            'button--submit',
            'rsb'
          ],
          'bundle' => $this->getFormId(),
        ],
      ],
    ], helper::RESULTS_RECORDLIST_FOOTER);

    if (!empty($form_state->getUserInput()) && !empty($form_state->getUserInput()["op"]) && $form_state->getUserInput()["op"] == "Subscribe") {
      // todo check if step is subscribe
      $this->subscribeEmail($form, $form_state);
    }

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    if ($this->step == "subscribe") {
      $form['search_criteria_wrapper']['record']['#dataset'] = NULL;
      $form = $this->subscribeEmail($form, $form_state);
    }

  }

  /**
   * {@inheritDoc}
   */
  public function validateSearch(array &$form, FormStateInterface $form_state) {
    // Not required.
  }

  /**
   * {@inheritDoc}
   */
  public function submitToRemote(array &$form, FormStateInterface $form_state) {
    try {
      $sql = new SQL();
      $appname = $this->getFormId();
      $tokens = $sql->getToken($appname);
      if ($tokens) {
        $auth_token = $tokens[SQL::AUTH_TOKEN];
        $conn_token = $tokens[SQL::CONN_TOKEN];

        $street = trim($this->user_input["searchbox"]);
        $street = preg_replace("/[0-9]{1,4}[A-Ma-m]?\s([A-Za-z])/","$1", $street);
        $street = helper::santizeStreetName($street);

        $sql_statement = "SELECT *\n  FROM PwdSweeping";
        $sql_statement .= "\nWHERE (St_name LIKE '${street}%' OR St_name LIKE '% ${street}%') ";
        if (!empty($this->user_input["day"])) {
          foreach ((array) $this->user_input["day"] as $day){
            $sql_statement .= "\n  AND [" . helper::weekdayConvert($day) . "] = 1";
          }
        }
        if (!empty($this->user_input["ordinal"])) {
          foreach ((array) $this->user_input["ordinal"] as $week){
            $sql_statement .= "\n  AND [week" . ((int)$week + 1) . "] = 1";
          }
        }
//        if (!empty(trim($this->user_input["neighborhood"]))) {
//          $district = trim(ucwords($this->user_input["neighborhood"]));
//          $sql_statement .= "\n  AND PwdSweeping.DistName = '${district}'";
//        }

        $sql_statement .= "\nORDER BY DistName, StartTime, St_name";
        $results = $sql->runQuery($auth_token, $conn_token, $sql_statement);

        // Write results dataset into the buildInfo object of $form_state so it
        // can be used elsewhere.
        $build_info = $form_state->getBuildInfo();
        $build_info["dataset"] = (array) $results;
        $form_state->setBuildInfo($build_info);
      }
      else {
        if ($sql->getErrors()) {
          $this->errors = $sql->getErrors();
        }
      }
    }
    catch (\Exception $e) {
      $this->errors[] = $e->getMessage();
    }

  }

  /**
   * {@inheritDoc}
   */
  public function buildSearchResults(array &$form, FormStateInterface $form_state) {
    // Provide a summary message.
    if (empty($form_state->getBuildInfo()['dataset'])) {
      return;
    }

    // Place the results into the form.
    if (!empty($form_state->getBuildInfo()['dataset'])) {

      $results = (array) $form_state->getBuildInfo()["dataset"];

      // Creating a form element will allow callbacks so deep links to records
      // can be created
      $fm = [];
      foreach ($results as $key => $record) {
        $fm += [
/*          "search_${key}" => [
            '#type' => 'submit',
            '#value' => $record->St_name,
            '#name' => "id_" . $record->MainID,
            "#prefix" => "<div class='g--2 m-b300'><span class='t t--b t--s400'>",
            "#suffix" => "</span></div>",
            '#ajax' => [
              'progress' => ['type' => 'throbber'],
              'callback' => [$this, "buildRecord"],
              'disable-refocus' => TRUE,
              'prevent' => 'submit',
              'effect' => "fade",
              'wrapper' => $this->getFormId(),
            ],
            '#attributes' => [
              'class' => ["rsb btn--txt use-ajax-submit"],
            ],
          ],*/
          $record->St_name . "_${key}" => [
            "#type" => "markup",
            "#markup" => "<div class='g--2 m-b300'><span class='t t--b t--bold t--s400'>" . $record->St_name . "</span></div>",
            ],
          $record->DistName . "_${key}" => [
            "#type" => "markup",
            "#markup" => "<div class='g--2 m-b300'><span class='t t--b t--s400'>" . $record->DistName . "</span></div>",
            ],
          $record->Side . "_${key}" => [
            "#type" => "markup",
            "#markup" => "<div class='g--2 m-b300'><span class='t d-b ta--c t--b t--s400'>" . ($record->Side ?: "Both") . "</span></div>",
            ],
          $record->from . "_${key}" => [
            "#type" => "markup",
            "#markup" => "<div class='g--2 m-b300'><span class='t t--b t--s400'>" . $record->from . " - " . $record->to . "</span></div>",
            ],
          $record->trashday . "_${key}" => [
            "#type" => "markup",
            "#markup" => "<div class='g--2 m-b300'><span class='t t--b t--s400'>" . $this::determineSchedule($record) . "</span></div>",
            ],
          "checkbox_${key}" => [
            "#type" => "checkbox",
            '#attributes' => [
              "id" => "cb_" . $record->MainID,
              "value" => $record->MainID,
              "name" => $record->St_name,
              "class" => ["cb-f"],
              "bundle" => $this->getFormId(),
            ],
            "#parents" => ["search_criteria_wrapper", "results"],
            "#prefix" => "<div class='ta--c g--2 m-b300'>",
            "#suffix" => "</div>",
            ],
        ];
      }
      $fm = array_merge($fm, [
          "#prefix" => "<div class='g'>",
          "#suffix" => "</div>",]);
//          'email' => [
//            "#type" => "email",
//            "#required" => TRUE,
//            "#weight" => 0,
//            "#attributes" => [
//              "id" => "subscribeEmail",
//              "name" => "subscribeEmail",
//            ],
//          ],
//          'time' => [
//            '#type' => 'select',
//            '#weight' => 1,
//            '#title' => t('Select a receipt time'),
//            '#attributes' => [
//              'class' => [
//                'sel-f',
//                'sel-f--fw',
//              ],
//              'bundle' => $this->getFormId(),
//            ],
//            '#options' => [
//              '11' => "11 am",
//              '2' => "2 pm",
//              '5' => "5 pm",
//            ],
//            '#empty_option' => 'Select Time',
//          ],
//          'subscribe' => [
//            "#type" => "button",
//            "#value" => "Subscribe",
//            "#ajax" => [
//              "callback" => [$this, "subscribeEmail"],
//              'disable-refocus' => TRUE,
//              'event' => "click",   // Allows clicking enter key to submit form.
////              "prevent" => 'click',
//              'progress' => ['type' => 'throbber'],
//              'effect' => "fade",
//              'wrapper' => $this->getFormId(),
//            ],
//            '#disabled' => FALSE,
//            '#attributes' => [
//              'class' => [
//                'form__button--bos-remote_search_box',
//                'button--submit',
//                'rsb',
////                'use-ajax-submit',
//              ],
//              'bundle' => $this->getFormId(),
//            ],
//          ],
//        ]
//      );
      helper::addSearchResults($form, $fm, helper::RESULTS_RECORDLIST_FORM);
      $form["search_criteria_wrapper"]["results"]["output"]["recordlistingfooter"]["#attributes"]['class'] = ["g"];

    }

    // Check for any errors
    if (!empty($this->errors)) {

      // Adding in the $results array will make the results available to the
      // twig template container--street-sweeping-lookup--errors.html.twig.
      helper::addSearchResults($form, $this->errors, helper::RESULTS_ERROR);
    }

    $form_state->setRebuild(TRUE);

  }

  /**
   * {@inheritDoc}
   */
  public function buildRecord(array &$form, FormStateInterface $form_state) {
    // Not required for this class.
    return;
  }

  /**
   * {@inheritDoc}
   */
  public function endpoint(string $action, array $payload) {
    // This is used when the listserv looks up and validates users to send to
    // At 11am, 2pm and 5pm each day.
    // we need to redirect https://www.cityofboston.gov/publicworks/sweeping/lyris.asp?email=<emailaddress>[&date=today|YYYY-MM-DD]
    // to https://boston.gov//ajax/rsb/StreetSweepingLookup/recipient?email=<emailaddress>[&date=today|YYYY-MM-DD]
    if ($action == "recipient") {
      // Create a fully qualified HTML message to use an email.
      $body = new Body();
      $body->append();
      $response = new Response();
      $response->addHeaders([
        "Content-Type=text/html"
      ], TRUE);
      $response->addBody($body);

      return $response;
    }
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  private function subscribeEmail(array &$form, FormStateInterface $form_state) {
    // This is the second call to SQL using same creds etc, so there should
    // be no errors.
    // NOTE: Important to do the database update before Lyris.
    helper::addSearchResults($form, ["Email Updated"], helper::RESULTS_MESSAGE);
//    $form_state->setRebuild(TRUE);
//    \Drupal::messenger()->deleteAll();
    return $form;

    if ($this->subscribeDatabase("subscribe", $this->record_id, $this->user_input["email"])) {
      if (FALSE === $this->subscribeLyris("subscribe", $this->user_input)) {
        // Oops, rollback.
        $this->subscribeDatabase("unsubscribe", $this->record_id, $this->user_input["email"]);
      }
      else {
        //todo broadcast success.
      }
    }

  }

  /**
   * Add or removes the email/Street subscription to the Towing Database
   *
   * @param string $mode "subscribe" or "unsubscribe"
   *
   * @return bool TRUE if success, FALSE if failed.
   */
  private function subscribeDatabase(string $mode) {

    $sql = new SQL();
    $appname = $this->getFormId();
    $tokens = $sql->getToken($appname);

    if ($tokens) {
      $auth_token = $tokens[SQL::AUTH_TOKEN];
      $conn_token = $tokens[SQL::CONN_TOKEN];

      switch ($mode) {

        case "subscribe":
          $sql_string = "INSERT INTO PwdSweepingEmails (EmailAddr_ , StreetID) \n";
          $sql_string .= "VALUES ( '" . $this->user_input["email"] . "', " . $this->record_id . ");";
          break;

        case "unsubscribe":
          $sql_string = "DELETE FROM PwdSweepingEmails \n";
          $sql_string .= "  WHERE [EmailAddr_] = '" . $this->user_input["email"] . "'";
          $sql_string .= "  AND [StreetID] = " . $this->record_id . ";";
          break;

        default:
          return FALSE;
      }
      $results = $sql->runQuery($auth_token, $conn_token, $sql_string);

      // TODO: determine sucess or failure.
      return TRUE;
    }
  }

  /**
   * Adds or removes the email recipient from Lyris no-tow list.
   *
   * @param string $mode "subscribe" or "unsubscribe"
   *
   * @return bool TRUE if success, FALSE if failed.
   */
  private function subscribeLyris(string $mode) {
    if ($mode == "subscribe") {
      // Check if this email is already registered in Lyris.
      if ($this->isLyrisSubscribed($this->user_input["email"])) {
        // Email is registered, so just update the time preference directly in
        // Lyris DB.
        $sql = new SQL();
        $appname = $this->getFormId();
        $lyristokens = $sql->getToken($appname . "_lyris");

        if ($lyristokens) {
          $auth_token = $lyristokens[SQL::AUTH_TOKEN];
          $conn_token = $lyristokens[SQL::CONN_TOKEN];
          $sql_string = "UPDATE members_ \n";
          $sql_string .= "SET NoTowTimePreference = " . $this->user_input["time"] ."\n";
          $sql_string .= "WHERE EmailAddr_ = '" . $this->user_input["email"] . "'\n";
          $sql_string .= "  AND List_ = 'no-tow'; ";
          $results = $sql->runQuery($auth_token, $conn_token, $sql_string);
        }
      }
      else {
        // Email is not in Lyris so subscribe using the Lyris endpoint.
        $strURL = "http://listserv.cityofboston.gov/subscribe/subscribe.tml";
        $strURL .= $strURL & "?email=" . $this->user_input["email"];
        $strURL .= $strURL & "&NoTowTimePreference=" . $this->user_input["email"];
        $strURL .= $strURL & "&list=no-tow";
        $strURL .= $strURL & "&confirm=one";
        $strURL .= $strURL & "&showconfirm=T";
        $strURL .= $strURL & "&demographics=NoTowTimePreference";
        $strURL .= $strURL & "&url=http://www.cityofboston.gov/publicworks/sweeping";

        // Make the request and return the response.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $strURL);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
          "Cache-Control: no-cache",
        ]);
        $info = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

      }
    }
    elseif ($mode == "unsubscribe") {
      // Check if the email address is in the Towing DB.
      $sql = new SQL();
      $appname = $this->getFormId();
      $tokens = $sql->getToken($appname);
      if ($tokens) {
        $auth_token = $tokens[SQL::AUTH_TOKEN];
        $conn_token = $tokens[SQL::CONN_TOKEN];
        $sql_string = "SELECT EmailAddr_ \n";
        $sql_string .= "  FROM PwdSweepingEmails \n";
        $sql_string .= "WHERE EmailAddr_ = '" . $this->user_input["email"] . "';";
        $results = $sql->runQuery($auth_token, $conn_token, $sql_string);

        if (count($results) == 0) {
          // The email doesn't exist in the Towing DB list, so it is safe to
          // delete the subscription from Lyris using Lyris API endpoint.
          $strURL = "http://listserv.cityofboston.gov/subscribe/unsubscribe.tml";
          $strURL .= $strURL & "?email=" . $this->user_input["email"];
          $strURL .= $strURL & "&lists=no-tow";
          $strURL .= $strURL & "&email_notification=F";
          $strURL .= $strURL & "&confirm_first=F";
          $strURL .= $strURL & "&showconfirm=T";
          $strURL .= $strURL & "&demographics=NoTowTimePreference";
          $strURL .= $strURL & "&url=http://www.cityofboston.gov/publicworks/sweeping";

          // Make the request and return the response.
          $ch = curl_init();
          curl_setopt($ch, CURLOPT_URL, $strURL);
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
          curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Cache-Control: no-cache",
          ]);
          $info = curl_exec($ch);
          $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        }
      }
    }

  }

  /**
   * Calls a stored procedure on Lyris to determine if the siupplied emils is in
   * fact a registered subscriber.
   *
   * @param string $email
   *
   * @return bool|void
   */
  private function isLyrisSubscribed(string $email) {
    $sql = new SQL();
    $appname = $this->getFormId();
    $lyristokens = $sql->getToken($appname . "_lyris");

    if ($lyristokens) {
      $auth_token = $lyristokens[SQL::AUTH_TOKEN];
      $conn_token = $lyristokens[SQL::CONN_TOKEN];

      $sql_string = "SELECT * FROM members_\n ";
      $sql_string .= "WHERE _list = 'no-tow' \n";
      $sql_string .= "  AND MemberType_ = 'normal'\n";
      $sql_string .= "  AND EmailAddr_ = '". $this->user_input["email"] . "'";
      $results = $sql->runQuery($auth_token, $conn_token, $sql_string);

      return (count($results) >= 1 ? TRUE : FALSE);
    }
  }

  /**
   * Makes a human readable scheduling statement from the table values.
   *
   * @param $record
   *
   * @return string
   */
  static function determineSchedule($record) {
    if ($record->Monday && $record->Tuesday && $record->Wednesday && $record->Thursday && $record->Friday && $record->Saturday && $record->Sunday) {
      $days = "day ";
    }
    elseif ($record->Monday && $record->Tuesday && $record->Wednesday && $record->Thursday && $record->Friday) {
      $days = "weekday ";
    }
    elseif ($record->Saturday && $record->Sunday) {
      $days = "weekend ";
    }
    else {
      $days = "";
      if ($record->Monday) {
        $days .= " Monday,";
      }
      if ($record->Tuesday) {
        $days .= " Tuesday,";
      }
      if ($record->Wednesday) {
        $days .= " Wednesday,";
      }
      if ($record->Thursday) {
        $days .= " Thursday,";
      }
      if ($record->Friday) {
        $days .= " Friday,";
      }
      if ($record->Saturday) {
        $days = " Saturday,";
      }
      if ($record->Sunday) {
        $days .= " Sunday";
      }
      $days = trim($days, ",\t\n\r\0\xoB");
    }

    if ($record->week1 && $record->week2 && $record->week3 && $record->week4 && $record->week5) {
      $weeks = "each ";
    }
    else {
      if ($record->week1) {$weeks = "of the first ";}
      if ($record->week2) {$weeks = "of the second ";}
      if ($record->week3) {$weeks = "of the third ";}
      if ($record->week4) {$weeks = "of the fourth ";}
      if ($record->week5) {$weeks = "of the fifth ";}
    }

    $season = "all year";
    if (! $record->Yearround) {
      if ($record->northendpilot) {
        $season = "01 March thru 31 December";
      }
      else {
        $season = "01 April thru 30 November";
      }
    }
    return "Every ${days}, ${weeks} week of the month, ${season}.";

  }
}
