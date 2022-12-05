<?php
/**
 * @file
 * Contains class ConfigEventsSFJWT
 */
namespace Drupal\bos_metrolist\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
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
    ];
  }

  /**
   * Refresh the token for a salsforce authentication provider.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event The subscribed event.
   *
   * @return void
   */
  public function CheckOauthAuthorization(ConfigCrudEvent $event) {
    if (str_contains($event->getConfig()->getName(), "salesforce.salesforce_auth")) {
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

}
