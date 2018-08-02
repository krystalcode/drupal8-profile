<?php

namespace Drupal\profile\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Activates/publishes a profile.
 *
 * @Action(
 *   id = "profile_activate_action",
 *   label = @Translation("Activate selected profile"),
 *   type = "profile"
 * )
 */
class ActivateProfile extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    /** @var \Drupal\profile\Entity\ProfileInterface $entity */
    $entity->setActive(TRUE);
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\profile\Entity\ProfileInterface $object */
    $result = $object->access('activate/deactivate', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $result : $result->isAllowed();
  }

}
