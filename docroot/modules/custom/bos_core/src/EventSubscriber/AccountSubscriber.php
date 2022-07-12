<?php

namespace Drupal\bos_core\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountSetEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Session\AccountEvents;

/**
 * Class AccountSubscriber.
 *
 * @package Drupal\bos_core\EventSubscriber
 */

class AccountSubscriber implements EventSubscriberInterface {

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $currentUser;

  /**
   * TimeZoneResolver constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(AccountInterface $current_user, ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
  }

  /**
   * @inheritDoc
   */
  public static function getSubscribedEvents() {
    return [
      AccountEvents::SET_USER => 'setuser',
    ];
  }

  public function setuser(AccountSetEvent $user) {
    // If the user has not defined a preferred back-end language, then set to en.
    if (!$user->getAccount()->isAnonymous()) {
      if (empty($user->getAccount()->getPreferredAdminLangcode(FALSE))) {
        $account = \Drupal::entityTypeManager()
          ->getStorage('user')
          ->load($user->getAccount()->id());
        $account->set("preferred_admin_langcode", "en");
        $account->save();
      }
    }
  }

}
