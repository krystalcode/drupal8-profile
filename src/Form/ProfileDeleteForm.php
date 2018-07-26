<?php

namespace Drupal\profile\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a confirmation form for deleting a profile entity.
 */
class ProfileDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %label?', [
      '%label' => $this->getEntity()->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    /** @var \Drupal\profile\Entity\ProfileInterface $entity */
    $entity = $this->entity;
    if ($entity->getOwnerId()) {
      return Url::fromRoute('entity.user.canonical', [
        'user' => $entity->getOwnerId(),
      ]);
    }
    return Url::fromRoute('entity.profile.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Instead of permanently deleting the profile, let's just set it to
    // disabled so that we preserve the order references.
    /** @var \Drupal\Profile\Entity\ProfileInterface $entity */
    $entity = $this->getEntity();

    $entity->setActive(FALSE);
    $entity->save();
    $form_state->setRedirectUrl($this->getRedirectUrl());

    drupal_set_message($this->getDeletionMessage());
    $this->logDeletionMessage();
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    $entity = $this->getEntity();
    return $this->t('%label has been deleted.', [
      '%label' => $entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getRedirectUrl() {
    return $this->getCancelUrl();
  }

}
