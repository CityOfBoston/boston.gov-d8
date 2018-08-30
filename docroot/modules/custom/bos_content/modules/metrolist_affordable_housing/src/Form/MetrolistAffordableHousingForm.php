<?php

namespace Drupal\metrolist_affordable_housing\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the metrolist affordable housing entity edit forms.
 */
class MetrolistAffordableHousingForm extends ContentEntityForm {

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
      drupal_set_message($this->t('New metrolist affordable housing %label has been created.', $message_arguments));
      $this->logger('metrolist_affordable_housing')->notice('Created new metrolist affordable housing %label', $logger_arguments);
    }
    else {
      drupal_set_message($this->t('The metrolist affordable housing %label has been updated.', $message_arguments));
      $this->logger('metrolist_affordable_housing')->notice('Created new metrolist affordable housing %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.metrolist_affordable_housing.canonical', ['metrolist_affordable_housing' => $entity->id()]);
  }

}
