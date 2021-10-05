<?php

namespace Drupal\bos_vbo_actions\Plugin\Action;

use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use functional\Append;

/**
 * Action description.
 *
 * @Action(
 *   id = "bos_vbo_actions_remove_url_alias",
 *   label = @Translation("Remove URL Alias"),
 *   type = "",
 *   confirm = FALSE
 * )
 */
class BosVboActionsRemoveUrlAlias extends ViewsBulkOperationsActionBase
{

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL): \Drupal\Core\StringTranslation\TranslatableMarkup
  {
    // Do some processing..
    try {
      $path = $entity->path ?? null;

      if ($path && $this->hasPathAliasSet($path)) {
        $path->delete();
        return $this->t("URL Alias have been removed.");
      }
    } catch (\Exception $exception) {
      \Drupal::logger('vbo')->error($exception->getMessage());
      return $this->t("Error with removing the URL Alias from @entityID", ['@entityID' => $entity->id()]);
    }

    return $this->t("No URL Alias to remove");
  }

  /**
   * Check if a node has a Path Alias set of if it is just the default that is set
   *
   * @param $path
   *  A URL Path Alias field value
   *
   * @return bool
   *  If the path alias exists or not (default)
   */
  public function hasPathAliasSet($path): bool
  {

    if ($path->get(0)
      && count($path->get(0)->getValue()) != 1
      && array_key_exists('alias', $path->get(0)->getValue())
    ) {
      return true;
    }

    return false;
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE)
  {
    // Only allow administrators access to this VBO Action
    if (in_array('administrator', $account->getRoles())) {
      return true;
    }

    return false;
  }

}
