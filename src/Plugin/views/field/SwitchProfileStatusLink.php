<?php

namespace Drupal\profile\Plugin\views\field;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to present a link to switch the profile publication status.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("switch_profile_status_link")
 */
class SwitchProfileStatusLink extends LinkBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a LinkBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Access\AccessManagerInterface $access_manager
   *   The access manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    AccessManagerInterface $access_manager,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $access_manager);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('access_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\profile\Entity\ProfileInterface $entity */
    $entity = $this->getEntity($row);

    if (!$entity->isActive()) {
      return Url::fromRoute('entity.profile.publish', [
        'profile' => $entity->id()
      ]);
    }

    return Url::fromRoute('entity.profile.unpublish', [
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

    $profile_type_storage = $this->entityTypeManager->getStorage('profile_type');
    /** @var \Drupal\profile\Entity\ProfileTypeInterface $profile_type */
    $profile_type = $profile_type_storage->load($entity->bundle());

    $title = $profile_type->getUnpublishLabel();
    if (!$entity->isActive()) {
      $title = $profile_type->getPublishLabel();
    }

    $this->options['alter']['attributes'] = ['title' => $title];

    return $title;
  }

}
