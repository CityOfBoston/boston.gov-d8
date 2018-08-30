<?php

namespace Drupal\procurement_advertisement\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the procurement advertisement entity edit forms.
 */
class ProcurementAdvertisementForm extends ContentEntityForm {

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
      drupal_set_message($this->t('New procurement advertisement %label has been created.', $message_arguments));
      $this->logger('procurement_advertisement')->notice('Created new procurement advertisement %label', $logger_arguments);
    }
    else {
      drupal_set_message($this->t('The procurement advertisement %label has been updated.', $message_arguments));
      $this->logger('procurement_advertisement')->notice('Created new procurement advertisement %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.procurement_advertisement.canonical', ['procurement_advertisement' => $entity->id()]);
  }

}
