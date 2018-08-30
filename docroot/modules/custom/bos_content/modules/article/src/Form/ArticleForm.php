<?php

namespace Drupal\article\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the article entity edit forms.
 */
class ArticleForm extends ContentEntityForm {

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
      drupal_set_message($this->t('New article %label has been created.', $message_arguments));
      $this->logger('article')->notice('Created new article %label', $logger_arguments);
    }
    else {
      drupal_set_message($this->t('The article %label has been updated.', $message_arguments));
      $this->logger('article')->notice('Created new article %label.', $logger_arguments);
    }

    $form_state->setRedirect('entity.article.canonical', ['article' => $entity->id()]);
  }

}
