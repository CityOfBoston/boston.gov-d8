<?php

namespace Drupal\program_initiative_profile\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the program initiative profile entity edit forms.
 */
class ProgramInitiativeProfileForm extends ContentEntityForm {

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
      drupal_set_message($this->t('New program initiative profile %label has been created.', $message_arguments));
      $this->logger('program_initiative_profile')->notice('Created new program initiative profile %label', $logger_arguments);
    }
    else {
      drupal_set_message($this->t('The program initiative profile %label has been updated.', $message_arguments));
      $this->logger('program_initiative_profile')->notice('Created new program initiative profile %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.program_initiative_profile.canonical', ['program_initiative_profile' => $entity->id()]);
  }

}
