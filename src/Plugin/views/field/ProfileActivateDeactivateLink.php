<?php

namespace Drupal\profile\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to activate/deactivate a profile.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("profile_activate_deactivate_link")
 */
class ProfileActivateDeactivateLink extends LinkBase {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\profile\Entity\ProfileInterface $entity */
    $entity = $this->getEntity($row);

    if (!$entity->isActive()) {
      return Url::fromRoute('entity.profile.activate', [
        'profile' => $entity->id()
      ]);
    }

    return Url::fromRoute('entity.profile.deactivate', [
      'profile' => $entity->id()
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink(ResultRow $row) {
    /** @var \Drupal\profile\Entity\ProfileInterface $entity */
    $entity = $this->getEntity($row);

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['url'] = $this->getUrlInfo($row);

    $profile_type_storage = \Drupal::entityTypeManager()->getStorage('profile_type');
    /** @var \Drupal\profile\Entity\ProfileTypeInterface $profile_type */
    $profile_type = $profile_type_storage->load($entity->bundle());

    $title = $profile_type->getDeactivateProfileButtonLabel();
    if (!$entity->isActive()) {
      $title = $profile_type->getActivateProfileButtonLabel();
    }

    $this->options['alter']['attributes'] = ['title' => $title];

    return $title;
  }

}
