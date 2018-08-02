<?php

namespace Drupal\profile\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Deactivates/unpublishes a profile.
 *
 * @Action(
 *   id = "profile_deactivate_action",
 *   label = @Translation("Deactivate selected profile"),
 *   type = "profile"
 * )
 */
class DeactivateProfile extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\profile\Entity\ProfileInterface $entity */
    $entity->setActive(FALSE);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\profile\Entity\ProfileInterface $object */
    $access = $object->access('activate/deactivate', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
