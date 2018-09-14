<?php

namespace Drupal\how_to\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the how-to entity edit forms.
 */
class HowToForm extends ContentEntityForm {

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
      drupal_set_message($this->t('New how-to %label has been created.', $message_arguments));
      $this->logger('how_to')->notice('Created new how-to %label', $logger_arguments);
    }
    else {
      drupal_set_message($this->t('The how-to %label has been updated.', $message_arguments));
      $this->logger('how_to')->notice('Created new how-to %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.how_to.canonical', ['how_to' => $entity->id()]);
  }

}
