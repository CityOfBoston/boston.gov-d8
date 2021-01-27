<?php

use Drupal\Core\Cache\CacheableJsonResponse;

$loader = new \PlateLoader();
$loader->LoadPlate("12sb123", "david@the-uptons.com", "MA");


class PlateLoader {

  /**
   * The current request object for the class.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected $request;

  private $pdo;
  private $host = 'tcp:zpdmzsql01, 1433';
  private $port = '1433';
  private $dbname = 'Towing';
  private $username = 'web.cob\con01579';
  private $password = 'Top10Cities!';

  public function api($action) {
    switch ($action) {
      case "single":
        if ($this->request->getMethod() == "GET") {
          $payload = $this->request->getQueryString();
          if ($payload) {
            $payload = explode("=", urldecode($payload));
            $payload = json_decode($payload[1]);
          }
        }
        $response_array = $this->LoadPlate($payload->plate, $payload->email, TRUE);
        $processed = 1;
        break;

      case "multi":
        $processed = 99;
        break;

      case "file":
        $processed = 99;
        break;

    }
    $response = new CacheableJsonResponse($response_array);
    return $response;
  }

  /**
   *
   */
  private function ConnectDB() {
    $connOpts = [
      "Database" => $this->dbname,
      "Driver" => "ODBC Driver 17 for SQL Server",
      "Authentication" => "SqlPassword",
      "UID" => $this->username,
      "PWD" => $this->password,
    ];
    $pdo = sqlsrv_connect($this->host, $connOpts);
    if (!$pdo) {
      foreach (sqlsrv_errors() as $e) {
        $this->pdo .= " || " . $e["message"];
      }
      return FALSE;
    }
    $this->pdo = $pdo;
    return $pdo;
  }

  /**
   *   Admin@TotalFleet.Us
   */
  public function LoadPlate($plate, $email, $state, $update = TRUE) {
    if ($this->ConnectDB()) {
      $sql = "INSERT INTO [towed_emails] (
                subscriber_email,
                subscriber_plate,
                subscriber_state,
                subscriber_html
              ) VALUES (
              '" + Sanitize(strEmailAddress) + "',
              '" + strPlate + "',
              '" + strState + "',
              '1')";
      try {
        $query = $this->pdo->prepare($sql);
        $query->execute();
      }
      catch (\Exception $e) {
        return [
          'status' => "error",
          'error_message' => "{$e->getMessage()}",
        ];
      }
      unset($query);
      return [
        'status' => "ok",
        'message' => 'Processed single record',
      ];
    }
    else {
      return [
        'status' => "error",
        'error_message' => "{$this->pdo}",
      ];

    }
  }

  /**
   *
   */
  public function LoadMultiplePlates() {
    $this->ConnectDB();
  }

  /**
   *
   */
  public function LoadPlatesFromFile() {}


  private function ValidateEmail($email) {
    $pattern = "[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?";
    return TRUE;
  }

  private function ValidatePlate($plate) {
    $pattern = "^([0-9A-Z]{1,8})$";
    return TRUE;
  }
}
