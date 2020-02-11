<?php

namespace Drupal\slackposter\Plugin\rest\resource;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\slackposter\Integrate\SlackAPI;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Psr\Log\LoggerInterface;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "slack_resource",
 *   label = @Translation("Slack poster"),
 *   uri_paths = {
 *     "canonical" = "/ws/slack/post"
 *   }
 * )
 */
class SlackResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The current format being used.
   *
   * @var string
   */
  protected $currentformat;

  /**
   * Constructs a new SlackResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, array $serializer_formats, LoggerInterface $logger, AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
    if (isset($_REQUEST['_format'])) {
      $this->currentformat = $_REQUEST['_format'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('slackposter'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to POST requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function post() {
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('rest slack')) {
      throw new AccessDeniedHttpException();
    }
    if (!in_array($this->currentformat, $this->serializerFormats)) {
      throw new AccessDeniedHttpException();
    }

    return new ResourceResponse("Implement REST State POST!");
  }

  /**
   * Responds to GET requests.
   *
   * Returns a list of bundles for specified entity.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get() {
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('rest slack')) {
      throw new AccessDeniedHttpException('Error: No Permission');
    }
    if (!in_array($this->currentformat, $this->serializerFormats)) {
      throw new AccessDeniedHttpException('Error: Unsupported or missing format');
    }
    if (!$_REQUEST['payload']) {
      throw new AccessDeniedHttpException('Error: No Payload');
    }

    try {
      $api = new SlackAPI();
      if ($api->setPayload($_REQUEST['payload'], $this->currentformat)) {
        $response = $api->post();
      }
    }
    catch (\Exception $e) {
      throw new AccessDeniedHttpException($e->getMessage());
    }

    // Disable cache.
    $cacheSettings = [
      '#cache' => [
        'max-age' => 0,
      ],
    ];
    return (new ResourceResponse($response))->addCacheableDependency($cacheSettings);
  }

}
