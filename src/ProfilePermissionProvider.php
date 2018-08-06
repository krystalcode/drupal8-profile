<?php

namespace Drupal\profile;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity\UncacheableEntityPermissionProvider;

/**
 * Providers Profile entity permissions.
 *
 * Extends the Entity API permission provider to support bundle based view
 * permissions.
 */
class ProfilePermissionProvider extends UncacheableEntityPermissionProvider {

  /**
   * {@inheritdoc}
   */
  protected function buildBundlePermissions(EntityTypeInterface $entity_type) {
    $permissions = parent::buildBundlePermissions($entity_type);
    $singular_label = $entity_type->getSingularLabel();
    $entity_type_id = $entity_type->id();
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);

    foreach ($bundles as $bundle_name => $bundle_info) {
      $permissions["publish any {$bundle_name} {$entity_type_id}"] = [
        'title' => $this->t('@bundle: Publish any @type', [
          '@bundle' => $bundle_info['label'],
          '@type' => $singular_label,
        ]),
      ];
      $permissions["publish own {$bundle_name} {$entity_type_id}"] = [
        'title' => $this->t('@bundle: Publish own @type', [
          '@bundle' => $bundle_info['label'],
          '@type' => $singular_label,
        ]),
      ];

      $permissions["unpublish any {$bundle_name} {$entity_type_id}"] = [
        'title' => $this->t('@bundle: Unpublish any @type', [
          '@bundle' => $bundle_info['label'],
          '@type' => $singular_label,
        ]),
      ];
      $permissions["unpublish own {$bundle_name} {$entity_type_id}"] = [
        'title' => $this->t('@bundle: Unpublish own @type', [
          '@bundle' => $bundle_info['label'],
          '@type' => $singular_label,
        ]),
      ];
    }

    return $permissions;
  }

}
