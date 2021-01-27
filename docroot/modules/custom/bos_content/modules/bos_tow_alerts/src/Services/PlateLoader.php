<?php

namespace Drupal\bos_tow_alerts\Services;

use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class to manage loading of plates into the MSSQL Towing database.
 */

class PlateLoader extends ControllerBase {

  /**
   * The current request object for the class.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected $request;

  private $pdo;
  private $host = '10.241.250.26';
  private $port = '1433';
  private $dbname = 'Towing';
  private $username = 'con01579@web.cob';
  private $password = 'Top10Cities!';

  /**
   * Loads Plates into the database.
   */
  public function __construct(RequestStack $requestStack) {
    $this->request = $requestStack->getCurrentRequest();
  }

  /**
   * CityscoreRest create.
   *
   * @inheritdoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack')
    );
  }

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

  public function api($action) {
    if (in_array($this->request->getMethod(), ["POST", "GET"])) {
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
    }
    else {
      $response_array = [
        'status' => 'error',
        'error_message' => 'Unsupported HTTP Method',
      ];
    }
    $response = new CacheableJsonResponse($response_array);
    return $response;
  }

  /**
   *
   */
  private function ConnectDB() {
    $connOpts = [
      "database" => $this->dbname,
      "uid" => $this->username,
      "pwd" => $this->password,
    ];
    $pdo = sqlsrv_connect($this->host, $connOpts);
//      new MssqlPDO($this->host, $this->port, $this->dbname, $this->username, $this->password);
    if (!$pdo) {
      $this->pdo = sqlsrv_errors();
      return FALSE;
    }
    $this->pdo = $pdo;
    return $pdo;
  }

  /**
   *
   */
  public function LoadPlate($plate, $email, $update = TRUE) {
    if ($this->ConnectDB()) {
      $sql = "INSERT INTO [towed_emails] (subscriber_email, subscriber_plate, subscriber_state, subscriber_html) VALUES ('" + Sanitize(strEmailAddress) + "','" + strPlate + "','" + strState + "','" + strHtml + "')";
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

/**
 * Class MssqlPDO.
 */

class MssqlPDO {
  /**
   * Helper class to interract with Database using PDO.
   *
   * @author Johan Kasselman <johankasselman@live.com>
   * @since 2015-09-28    V1
   */

  private $db;
  private $cTransID;
  private $childTrans = [];

  /**
   *
   */
  public function __construct($hostname, $port, $dbname, $username, $pwd) {

    $this->hostname = $hostname;
    $this->port = $port;
    $this->dbname = $dbname;
    $this->username = $username;
    $this->pwd = $pwd;

    $this->connect();

  }

  /**
   *
   */
  public function beginTransaction() {

    $cAlphanum = "AaBbCc0Dd1EeF2fG3gH4hI5iJ6jK7kLlM8mN9nOoPpQqRrSsTtUuVvWwXxYyZz";
    $this->cTransID = "T" . substr(str_shuffle($cAlphanum), 0, 7);

    array_unshift($this->childTrans, $this->cTransID);

    $stmt = $this->db->prepare("BEGIN TRAN [$this->cTransID];");
    return $stmt->execute();

  }

  /**
   *
   */
  public function rollBack() {

    while (count($this->childTrans) > 0) {
      $cTmp = array_shift($this->childTrans);
      $stmt = $this->db->prepare("ROLLBACK TRAN [$cTmp];");
      $stmt->execute();
    }

    return $stmt;
  }

  /**
   *
   */
  public function commit() {

    while (count($this->childTrans) > 0) {
      $cTmp = array_shift($this->childTrans);
      $stmt = $this->db->prepare("COMMIT TRAN [$cTmp];");
      $stmt->execute();
    }

    return $stmt;
  }

  /**
   *
   */
  public function close() {
    $this->db = NULL;
  }

  /**
   *
   */
  public function connect() {

    try {
      $this->db = new \PDO("dblib:host=$this->hostname:$this->port;dbname=$this->dbname", "$this->username", "$this->pwd");

    }
    catch (\PDOException $e) {
      $this->logsys .= "Failed to get DB handle: " . $e->getMessage() . "\n";
    }

  }

}
