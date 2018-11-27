<?php

namespace Drupal\profile\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a confirmation form for publishing a profile entity.
 */
class ProfilePublishForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to publish the profile %label?', [
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Publish');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $this->entity;

    return Url::fromRoute('entity.profile.type.user_profile_form', [
      'user' => $profile->getOwnerId(),
      'profile_type' => $profile->bundle(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $this->entity;

    if ($profile->isActive()) {
      $this->messenger()->addWarning($this->t('The profile %label is already published.', [
        '%label' => $profile->label(),
      ]));
      return;
    }

    $form['#title'] = $this->getQuestion();

    $form['#attributes']['class'][] = 'confirmation';
    $form['description'] = ['#markup' => $this->getDescription()];
    $form[$this->getFormName()] = ['#type' => 'hidden', '#value' => 1];

    // By default, render the form using theme_confirm_form().
    if (!isset($form['#theme'])) {
      $form['#theme'] = 'confirm_form';
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $this->entity;
    $profile->setActive(TRUE);
    $profile->save();

    $this->messenger()->addMessage($this->t('The profile %label has been published.', [
      '%label' => $profile->label(),
    ]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
