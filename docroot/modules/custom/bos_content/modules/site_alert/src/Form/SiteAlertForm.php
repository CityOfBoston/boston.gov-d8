<?php

namespace Drupal\site_alert\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the site alert entity edit forms.
 */
class SiteAlertForm extends ContentEntityForm {

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
      drupal_set_message($this->t('New site alert %label has been created.', $message_arguments));
      $this->logger('site_alert')->notice('Created new site alert %label', $logger_arguments);
    }
    else {
      drupal_set_message($this->t('The site alert %label has been updated.', $message_arguments));
      $this->logger('site_alert')->notice('Created new site alert %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.site_alert.canonical', ['site_alert' => $entity->id()]);
  }

}
