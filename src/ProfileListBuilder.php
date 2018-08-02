<?php

namespace Drupal\profile;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * List controller for profiles.
 *
 * @see \Drupal\profile\Entity\Profile
 */
class ProfileListBuilder extends EntityListBuilder {

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The redirect destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ProfileListController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    DateFormatter $date_formatter,
    RendererInterface $renderer,
    RedirectDestinationInterface $redirect_destination,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    parent::__construct($entity_type, $storage);

    $this->dateFormatter = $date_formatter;
    $this->renderer = $renderer;
    $this->redirectDestination = $redirect_destination;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('redirect.destination'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'label' => $this->t('Label'),
      'type' => [
        'data' => $this->t('Type'),
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      'owner' => [
        'data' => $this->t('Owner'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'status' => $this->t('Status'),
      'is_default' => $this->t('Default'),
      'changed' => [
        'data' => $this->t('Updated'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];
    if (\Drupal::languageManager()->isMultilingual()) {
      $header['language_name'] = [
        'data' => $this->t('Language'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ];
    }
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\profile\Entity\ProfileInterface $entity */
    $langcode = $entity->language()->getId();
    $uri = $entity->toUrl();
    $options = $uri->getOptions();
    $options += ($langcode != LanguageInterface::LANGCODE_NOT_SPECIFIED && isset($languages[$langcode]) ? ['language' => $languages[$langcode]] : []);
    $uri->setOptions($options);
    $row['label'] = $entity->toLink();
    $row['type'] = $entity->bundle();
    $row['owner']['data'] = [
      '#theme' => 'username',
      '#account' => $entity->getOwner(),
    ];
    $row['status'] = $entity->isActive() ? $this->t('active') : $this->t('not active');
    $row['is_default'] = $entity->isDefault() ? $this->t('default') : $this->t('not default');
    $row['changed'] = $this->dateFormatter->format($entity->getChangedTime(), 'short');
    $language_manager = \Drupal::languageManager();
    if ($language_manager->isMultilingual()) {
      $row['language_name'] = $language_manager->getLanguageName($langcode);
    }

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    /** @var \Drupal\profile\Entity\ProfileInterface $entity */
    $operations = parent::getOperations($entity);

    $destination = $this->redirectDestination->getAsArray();
    foreach ($operations as $key => $operation) {
      $operations[$key]['query'] = $destination;
    }

    $profile_type_storage = $this->entityTypeManager->getStorage('profile_type');
    /** @var \Drupal\profile\Entity\ProfileTypeInterface $profile_type */
    $profile_type = $profile_type_storage->load($entity->bundle());
    /** @var \Drupal\Core\Session\AccountInterface $account */
    $account = \Drupal::currentUser();
    $own_any = ($account->id() === $entity->getOwnerId()) ? 'own' : 'any';
    if ($account->hasPermission("activate/deactivate $own_any {$profile_type->id()} profile")) {
      // If we the profile is enabled.
      if ($entity->isActive()) {
        // Display a deactivate button.
        $operations['deactivate'] = [
          'title' => $profile_type->getDeactivateProfileButtonLabel(),
          'url' => $entity->toUrl('deactivate'),
          'parameter' => $entity,
        ];

        if (!$entity->isDefault()) {
          $operations['set_default'] = [
            'title' => $this->t('Mark as default'),
            'url' => $entity->toUrl('set-default'),
            'parameter' => $entity,
          ];
        }
      }
      // Else, if the profile is not enabled.
      else {
        // Display an activate button.
        $operations['activate'] = [
          'title' => $profile_type->getActivateProfileButtonLabel(),
          'url' => $entity->toUrl('activate'),
          'parameter' => $entity,
        ];
      }
    }

    return $operations;
  }

}
