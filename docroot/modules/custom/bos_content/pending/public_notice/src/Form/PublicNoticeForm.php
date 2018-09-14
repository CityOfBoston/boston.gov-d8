<?php

namespace Drupal\public_notice\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the public notice entity edit forms.
 */
class PublicNoticeForm extends ContentEntityForm {

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
      drupal_set_message($this->t('New public notice %label has been created.', $message_arguments));
      $this->logger('public_notice')->notice('Created new public notice %label', $logger_arguments);
    }
    else {
      drupal_set_message($this->t('The public notice %label has been updated.', $message_arguments));
      $this->logger('public_notice')->notice('Created new public notice %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.public_notice.canonical', ['public_notice' => $entity->id()]);
  }

}
