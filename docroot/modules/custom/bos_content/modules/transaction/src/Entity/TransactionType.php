<?php

namespace Drupal\transaction\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Transaction type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "transaction_type",
 *   label = @Translation("Transaction type"),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\transaction\Form\TransactionTypeForm",
 *       "edit" = "Drupal\transaction\Form\TransactionTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\transaction\TransactionTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   admin_permission = "administer transaction types",
 *   bundle_of = "transaction",
 *   config_prefix = "transaction_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/transaction_types/add",
 *     "edit-form" = "/admin/structure/transaction_types/manage/{transaction_type}",
 *     "delete-form" = "/admin/structure/transaction_types/manage/{transaction _type}/delete",
 *     "collection" = "/admin/structure/transaction_types"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   }
 * )
 */
class TransactionType extends ConfigEntityBundleBase {

  /**
   * The machine name of this transaction type.
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable name of the transaction type.
   *
   * @var string
   */
  protected $label;

}
