<?php

namespace Drupal\script_page\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the script page entity edit forms.
 */
class ScriptPageForm extends ContentEntityForm {

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
      drupal_set_message($this->t('New script page %label has been created.', $message_arguments));
      $this->logger('script_page')->notice('Created new script page %label', $logger_arguments);
    }
    else {
      drupal_set_message($this->t('The script page %label has been updated.', $message_arguments));
      $this->logger('script_page')->notice('Created new script page %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.script_page.canonical', ['script_page' => $entity->id()]);
  }

}
