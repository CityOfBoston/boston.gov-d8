<?php
namespace Drupal\bos_email\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\DrupalTestBrowser;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Tests bos_email.send route.
 */
class EmailControllerTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['bos_email'];

  /**
   * @var \GuzzleHttp\Client
   */
  protected $client;

  public function __construct(?string $name = null, array $data = [], $dataName = '') {
    parent::__construct($name, $data, $dataName);
    $this->client = new Client();
  }

  /**
   * Tests bos_email.send route.
   */
  public function testSendEmail() {

    $endpoint = "rest/email/sanitation";
    $data = [];

    $response = $this->client->post($endpoint, [
      "headers"=>['Content-type' => 'application/json'],
      "body" => json_encode($data),
    ]);

    // Assert the response status and other necessary aspects.
    $this->assertEquals(200, $response->getStatusCode());

    // Additional assertions.
  }

  /**
   * Send a GET request to an external API.
   */
  public function getDataFromApi($endpoint) {
    try {
      $response = $this->client->get($endpoint);
      $data = Json::decode($response->getBody());
      return $data;
    }
    catch (RequestException $e) {
      watchdog_exception('my_module', $e);
    }

    return [];
  }
}
