<?php

namespace Drupal\test_component_page\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the test component page entity edit forms.
 */
class TestComponentPageForm extends ContentEntityForm {

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
      drupal_set_message($this->t('New test component page %label has been created.', $message_arguments));
      $this->logger('test_component_page')->notice('Created new test component page %label', $logger_arguments);
    }
    else {
      drupal_set_message($this->t('The test component page %label has been updated.', $message_arguments));
      $this->logger('test_component_page')->notice('Created new test component page %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.test_component_page.canonical', ['test_component_page' => $entity->id()]);
  }

}
