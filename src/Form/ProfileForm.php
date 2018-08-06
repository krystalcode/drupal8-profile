<?php

namespace Drupal\profile\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form controller for profile forms.
 */
class ProfileForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $element = parent::actions($form, $form_state);
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $this->entity;

    $profile_type_storage = $this->entityTypeManager->getStorage('profile_type');
    /** @var \Drupal\profile\Entity\ProfileTypeInterface $profile_type */
    $profile_type = $profile_type_storage->load($profile->bundle());

    // Allow the profile to be saved as default if type supports multiple.
    if ($profile_type->getMultiple() && !$profile->isDefault()) {
      // Add a "make default" button.
      $element['set_default'] = $element['submit'];
      $element['set_default']['#value'] = $this->t('Save and make default');
      $element['set_default']['#weight'] = 10;
      array_unshift($element['set_default']['#submit'], [$this, 'setDefault']);
    }

    // Add an unpublish button if the profile is active.
    /** @var \Drupal\Core\Session\AccountInterface $account */
    if (!$profile->isNew()) {
      $account = \Drupal::currentUser();
      $is_owner = $account->id() === $profile->getOwnerId();

      if ($profile->isActive()) {
        if (($account->hasPermission("unpublish any {$profile_type->id()} profile"))
          || ($account->hasPermission("unpublish own {$profile_type->id()} profile") && $is_owner)) {
          $element['unpublish'] = [
            '#type' => 'link',
            '#title' => $profile_type->getUnpublishLabel(),
            '#url' => Url::fromRoute('entity.profile.unpublish', [
              'profile' => $profile->id()
            ]),
            '#weight' => '10',
            '#attributes' => [
              'class' => ['button'],
            ],
          ];
        }
      }
      // Else, if the profile is in-active, add a publish button.
      else {
        if (($account->hasPermission("publish any {$profile_type->id()} profile"))
          || ($account->hasPermission("publish own {$profile_type->id()} profile") && $is_owner)) {
          $element['publish'] = [
            '#type' => 'link',
            '#title' => $profile_type->getPublishLabel(),
            '#url' => Url::fromRoute('entity.profile.publish', [
              'profile' => $profile->id()
            ]),
            '#weight' => '10',
            '#attributes' => [
              'class' => ['button'],
            ],
          ];
        }
      }
    }

    return $element;
  }

  /**
   * Form submission handler for the 'set_default' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   A reference to a keyed array containing the current state of the form.
   */
  public function setDefault(array $form, FormStateInterface $form_state) {
    $form_state->setValue('is_default', TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\profile\Entity\ProfileInterface $entity */
    $entity = parent::buildEntity($form, $form_state);

    // Mark a new revision if the profile type enforces revisions.
    /** @var \Drupal\profile\Entity\ProfileTypeInterface $profile_type */
    $profile_type = $this->entityTypeManager->getStorage('profile_type')
      ->load($entity->bundle());
    $entity->setNewRevision($profile_type->shouldCreateNewRevision());

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    switch ($this->entity->save()) {
      case SAVED_NEW:
        drupal_set_message($this->t('%label has been created.',
          ['%label' => $this->entity->label()]));
        break;

      case SAVED_UPDATED:
        drupal_set_message($this->t('%label has been updated.',
          ['%label' => $this->entity->label()]));
        break;
    }

    if ($this->entity->getOwnerId()) {
      $form_state->setRedirect('entity.user.canonical', [
        'user' => $this->entity->getOwnerId(),
      ]);
    }
    else {
      $form_state->setRedirect('entity.profile.collection');
    }

  }

}
