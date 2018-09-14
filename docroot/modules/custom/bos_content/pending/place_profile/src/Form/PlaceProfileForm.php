<?php

namespace Drupal\place_profile\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the place profile entity edit forms.
 */
class PlaceProfileForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $entity = $this->getEntity();
    $result = $entity->save();
    $link = $entity->toLink($this->t('View'))->toRenderable();

    $message_arguments = ['%label' => $this->entity->label()];
    $logger_arguments = $message_arguments + ['link' => render($link)];

    if ($result == SAVED_NEW) {
      drupal_set_message($this->t('New place profile %label has been created.', $message_arguments));
      $this->logger('place_profile')->notice('Created new place profile %label', $logger_arguments);
    }
    else {
      drupal_set_message($this->t('The place profile %label has been updated.', $message_arguments));
      $this->logger('place_profile')->notice('Created new place profile %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.place_profile.canonical', ['place_profile' => $entity->id()]);
  }

}
