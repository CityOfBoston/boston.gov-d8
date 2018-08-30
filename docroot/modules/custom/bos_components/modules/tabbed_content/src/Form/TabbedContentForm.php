<?php

namespace Drupal\tabbed_content\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the tabbed content entity edit forms.
 */
class TabbedContentForm extends ContentEntityForm {

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
      drupal_set_message($this->t('New tabbed content %label has been created.', $message_arguments));
      $this->logger('tabbed_content')->notice('Created new tabbed content %label', $logger_arguments);
    }
    else {
      drupal_set_message($this->t('The tabbed content %label has been updated.', $message_arguments));
      $this->logger('tabbed_content')->notice('Created new tabbed content %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.tabbed_content.canonical', ['tabbed_content' => $entity->id()]);
  }

}
