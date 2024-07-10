<?php

namespace Drupal\bos_aws_services\Services;

use Drupal\bos_core\Controllers\Curl\BosCurlControllerBase;
use Drupal\bos_google_cloud\Services\GcServiceInterface;
use Drupal\Core\Form\FormStateInterface;

class AwsKendraService extends BosCurlControllerBase implements GcServiceInterface {

  /**
   * @inheritDoc
   */
  public static function id(): string {
    // TODO: Implement id() method.
  }

  /**
   * @inheritDoc
   */
  public function execute(array $parameters = []): string {
    // TODO: Implement execute() method.
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array &$form): void {
    // TODO: Implement buildForm() method.
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array $form, FormStateInterface $form_state): void {
    // TODO: Implement submitForm() method.
  }

  /**
   * @inheritDoc
   */
  public function validateForm(array $form, FormStateInterface &$form_state): void {
    // TODO: Implement validateForm() method.
  }

  /**
   * @inheritDoc
   */
  public function setServiceAccount(string $service_account): GcServiceInterface {
    // TODO: Implement setServiceAccount() method.
  }

}
