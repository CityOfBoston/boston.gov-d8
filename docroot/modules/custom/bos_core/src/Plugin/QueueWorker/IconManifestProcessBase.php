<?php

namespace Drupal\bos_core\Plugin\QueueWorker;

use Drupal\bos_core\BosCoreSyncIconManifestService;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides base functionality for the Manifest Queue Workers.
 */
abstract class IconManifestProcessBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {
  /**
   * The file storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $fileStorage;
  /**
   * The file storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $mediaStorage;

  /**
   * Creates a new NodePublishBase object.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $file_storage
   *   File entity object for injection.
   * @param \Drupal\Core\Entity\EntityStorageInterface $media_storage
   *   Media entity object for injection.
   */
  public function __construct(EntityStorageInterface $file_storage, EntityStorageInterface $media_storage) {
    $this->fileStorage = $file_storage;
    $this->mediaStorage = $media_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity.manager')->getStorage('file'),
      $container->get('entity.manager')->getStorage('media')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {

    // Load the manifest cache.
    $manifest_cache = \Drupal::state()->get("bos_core.icon_library.manifest", []);

    // Just use the next available fid/vid for any file entities created.
    $last = ["fid" => 0, "vid" => 0];

    if (!empty($manifest_cache) && in_array($data, $manifest_cache)) {
      // Item has already been processed (b/c it's in the cache).
      return TRUE;
    }
    try {
      BosCoreSyncIconManifestService::processFileUri($data, $last);
      $manifest_cache[] = $data;
    }
    catch (\Exception $e) {
      return FALSE;
    }

    // Save cache for next time.
    \Drupal::state()->set("bos_core.icon_library.manifest", $manifest_cache);

    return TRUE;
  }

}
