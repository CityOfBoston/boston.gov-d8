<?php

namespace Drupal\advpoll\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the advanced poll entity edit forms.
 */
class AdvancedPollForm extends ContentEntityForm {

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
      drupal_set_message($this->t('New advanced poll %label has been created.', $message_arguments));
      $this->logger('advpoll')->notice('Created new advanced poll %label', $logger_arguments);
    }
    else {
      drupal_set_message($this->t('The advanced poll %label has been updated.', $message_arguments));
      $this->logger('advpoll')->notice('Created new advanced poll %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.advpoll.canonical', ['advpoll' => $entity->id()]);
  }

}
