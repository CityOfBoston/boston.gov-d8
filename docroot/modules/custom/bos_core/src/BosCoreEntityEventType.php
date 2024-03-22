<?php

namespace Drupal\bos_core;

use Drupal\entity_events\EntityEventType;

if (class_exists(EntityEventType::class)) {

  class BosCoreEntityEventType extends EntityEventType {
    const LOAD = "event.load";

  }
}
