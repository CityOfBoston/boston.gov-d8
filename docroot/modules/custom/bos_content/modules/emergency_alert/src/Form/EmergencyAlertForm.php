<?php

namespace Drupal\emergency_alert\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the emergency alert entity edit forms.
 */
class EmergencyAlertForm extends ContentEntityForm {

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
      drupal_set_message($this->t('New emergency alert %label has been created.', $message_arguments));
      $this->logger('emergency_alert')->notice('Created new emergency alert %label', $logger_arguments);
    }
    else {
      drupal_set_message($this->t('The emergency alert %label has been updated.', $message_arguments));
      $this->logger('emergency_alert')->notice('Created new emergency alert %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.emergency_alert.canonical', ['emergency_alert' => $entity->id()]);
  }

}
