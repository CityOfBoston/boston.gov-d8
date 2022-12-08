<?php
/**
 * @file
 * Contains class ConfigEventsSFJWT
 */
namespace Drupal\bos_metrolist\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Config\StorageTransformEvent;
use Drupal\Core\Render\Markup;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ConfigEventsSFJWT.
 *
 * This class is an event subscriber which listens for Config::SAVE events and
 * then checks to see if the configuration being saved is from the salesforce
 * module (specifically salesforce.salesforce_auth).
 * If it is, then the authentication token is refreshed.
 *
 * The service (bos_metrolist.config.update.oauth) is defined in
 * bos_metrolist.services.yml.
 *
 * @package Drupal\bos_metrolist
 */
class ConfigEventsSFJWT implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ConfigEvents::SAVE => 'CheckOauthAuthorization',
      ConfigEvents::STORAGE_TRANSFORM_IMPORT => 'UpdateConsumerSecret',
    ];
  }

  /**
   * Force the configuration to read the consumer_key token from a key set in
   * the systeme.
   *
   * Note: If the key has not been changed, then the config won't be
   *      updated (this is handled by drupal config operations).
   * Note: If the user has modified the consumer_key using the UI, then this
   *      will ALWAYS overwrite the settings made on that form. The correct
   *      way to change the consumer_key is to update the key and then
   *      run a drush cim.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *
   * @return void
   */
  public function UpdateConsumerSecret(StorageTransformEvent $event) {
    $keyval = "";
    $provider = $event->getStorage()
      ->read("salesforce.salesforce_auth.salesforcejwt");

    if ($key = \Drupal::service("key.repository")->getKey("salesforce_consumer_key")) {
      // Prefer to read the key.
      $keyval = $key->getKeyValue();
      if ($keyval !== $provider["provider_settings"]["consumer_key"]) {
        \Drupal::logger("salesforce_jwt")
          ->warning("The salesforce consumer key is being overwritten by the value in the 'salesforce consumer key' key.");
      }
    }
    else {
      \Drupal::logger("salesforce_jwt")
        ->warning("The salesforce consumer key is being managed locally by 'salesforce.salesforce_auth.salesforcejwt' or the GUI.");
    }

    if (!empty($keyval) && $keyval !== $provider["provider_settings"]["consumer_key"]) {
      $provider["provider_settings"]["consumer_key"] = $keyval;
      $event->getStorage()
        ->write("salesforce.salesforce_auth.salesforcejwt", $provider);
    }
  }

    /**
   * Refresh the token for a salsforce authentication provider.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event The subscribed event.
   *
   * @return void
   */
  public function CheckOauthAuthorization(ConfigCrudEvent $event) {

    if (str_contains(\Drupal::request()->getPathInfo(), "/admin/config/salesforce/authorize/edit")) {
      // Exit if this has been initiated from updating the settings form.
      return;
    }

    if (!str_contains($event->getConfig()->getName(), "salesforce.salesforce_auth")) {
      // Exit if the config being imported is not a salesforce_auth yml.
      return;
    }

    if ($new_config = $event->getConfig()->getRawData()) {
      $oauth_provider = $new_config["id"];
    }
    // Leverage the code in the salesforce drush command
    //     salesforce:refresh-token
    // Use this strategy in preference to writing our own refresh process
    // b/c the drush command is likely to be maintained by the contributor
    // group.
    try {
      if (isset($oauth_provider)) {
        $drush = \Drupal::service("salesforce.commands");
        $result = $drush->refreshToken($oauth_provider);
        \Drupal::logger("salesforce_oauth")->info($result);
      }
    }
    catch (\Exception $e) {
      // Warn the operator, and try to revoke the token.
      //  - Except if it's the default oauth, then don't worry so much.
      if ($oauth_provider == "oauth_default") {
        $txt = Markup::create(
          "Refresh Token for {$oauth_provider} failed.<br/>
          It is likely this is not an issue, unless you are using the
          default_oauth for salesforce sync. If you think this is the case then
          you should check the Salesforce Provider settings at
          admin/config/salesforce/authorize/list<br/><hr>
          {$e->getMessage()}
        ");
        \Drupal::logger("salesforce_oauth")->warning($txt);
      }

      else {
        $txt = Markup::create("Attempting to Refresh Token for {$oauth_provider}.<br/>{$e->getMessage()}");
        \Drupal::logger("salesforce_oauth")->error($txt);
        \Drupal::messenger()
          ->addError("Salesforce Oauth Token refresh for {$oauth_provider} failed. Check syslog.");

        try {
          // Revoke the token. If we could not refresh it, then we
          // have to suspect it's wrong.
          // Again, use drush b/c it's likely to be maintained.
          if (isset($drush)) {
            $drush->revokeToken($oauth_provider);
          }
        }
        catch (\Exception $e) {
          // Do nothing
        }
      }

    }
  }

}
