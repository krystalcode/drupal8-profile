<?php

namespace Drupal\profile\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a confirmation form for un-publishing a profile entity.
 */
class ProfileUnpublishForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to un-publish the profile %label?', [
      '%label' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Unpublish');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->entity->toUrl('collection');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = $this->entity;

    if (!$profile->isActive()) {
      return $this->messenger()->addWarning($this->t('The profile %label is already unpublished.', [
        '%label' => $profile->label(),
      ]));
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
    $profile->setActive(FALSE);
    $profile->save();

    $this->messenger()->addMessage($this->t('The profile %label has been un-published.', [
      '%label' => $profile->label(),
    ]));
    $form_state->setRedirectUrl($profile->toUrl('collection'));
  }

}
