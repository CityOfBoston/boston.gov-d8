<?php

namespace Drupal\bos_metrolist;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class MetrolistAMIPathSubscriber implements InboundPathProcessorInterface, EventSubscriberInterface
{

  public static function amiEstimatorUrl()
  {
    return '/metrolist/ami-estimator';
  }

  /**
   * Processes the inbound path.
   *
   * @param string $path
   *   The path to process, with a leading slash.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HttpRequest object representing the current request.
   *
   * @return string
   *   The processed path.
   */
  public function processInbound($path, Request $request)
  {
    if (self::stringStartsWith(self::amiEstimatorUrl(), strtolower($path))) {
      return \Drupal::service('path.alias_manager')->getPathByAlias(self::amiEstimatorUrl());
    }

    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents()
  {
    // register our event right before the redirect module redirects
    $events[ KernelEvents::REQUEST ][] = ['stopRedirectIfMetrolistAMI', 31];

    return $events;
  }

  /**
   * ------------------------------------
   * From the redirect module (redirect/src/EventSubscriber/RouteNormalizerRequestSubscriber.php):
   *
   * The normalization can be disabled by setting the "_disable_route_normalizer"
   * request parameter to TRUE. However, this should be done before
   * onKernelRequestRedirect() method is executed.
   * ------------------------------------
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event to process.
   */
  public static function stopRedirectIfMetrolistAMI(GetResponseEvent $event) {
    $path = $event->getRequest()->getPathInfo();

    if (self::stringStartsWith(self::amiEstimatorUrl(), strtolower($path))) {
      $event->getRequest()->attributes->set('_disable_route_normalizer', true);
    }
  }

  public static function stringStartsWith($needle, $haystack)
  {
    return (substr($haystack, 0, strlen($needle)) === $needle);
  }

}
