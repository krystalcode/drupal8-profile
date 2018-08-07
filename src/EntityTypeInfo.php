<?php

namespace Drupal\profile;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Manipulates entity type information.
 *
 * This class contains primarily bridged hooks for compile-time or
 * cache-clear-time hooks. Runtime hooks should be placed in EntityOperations.
 */
class EntityTypeInfo implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * EntityTypeInfo constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Adds publish/unpublish operations on profile entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which to define an operation.
   *
   * @return array
   *   An array of operation definitions.
   *
   * @see hook_entity_operation()
   */
  public function entityOperation(EntityInterface $entity) {
    $operations = [];

    /** @var \Drupal\profile\Entity\ProfileInterface $entity */

    $profile_type_storage = $this->entityTypeManager->getStorage('profile_type');
    /** @var \Drupal\profile\Entity\ProfileTypeInterface $profile_type */
    $profile_type = $profile_type_storage->load($entity->bundle());

    // If the profile is published, add an unpublish operation.
    if ($entity->isActive()) {
      if (!$entity->isDefault()) {
        $operations['set_default'] = [
          'title' => $this->t('Mark as default'),
          'url' => $entity->toUrl('set-default'),
          'parameter' => $entity,
          'weight' => 50,
        ];
      }

      // Display an unpublish button.
      if ($entity->access('unpublish')) {
        $operations['unpublish'] = [
          'title' => $profile_type->getUnpublishLabel(),
          'url' => Url::fromRoute('entity.profile.unpublish', [
            'profile' => $entity->id()
          ]),
          'weight' => 51,
        ];
      }
    }
    else {
      if ($entity->access('publish')) {
        // Display a publish button.
        $operations['publish'] = [
          'title' => $profile_type->getPublishLabel(),
          'url' => Url::fromRoute('entity.profile.publish', [
            'profile' => $entity->id()
          ]),
          'weight' => 51,
        ];
      }
    }

    return $operations;
  }

}
