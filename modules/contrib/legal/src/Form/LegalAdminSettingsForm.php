<?php

namespace Drupal\legal\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LegalAdminSettingsForm.
 *
 * @package Drupal\legal\Form
 */
class LegalAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'legal_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'legal.settings',
    ];
  }

  /**
   * Module settings form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('legal.settings');

    $form['description'] = [
      '#markup' => '<p>' . $this->t('Configuration options for display of Terms & Conditions.') . '</p>',
    ];

    $form['except_legal'] = [
      '#type'        => 'fieldset',
      '#title'       => $this->t('Exempt User Roles'),
      '#description' => $this->t('Users with the selected roles will never be shown T&C.'),
      '#collapsible' => TRUE,
      '#collapsed'   => TRUE,
    ];

    $role_options = user_role_names(TRUE);
    unset($role_options['authenticated']);

    $form['except_legal']['except_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Exempt user roles'),
      '#options' => $role_options,
      '#default_value' => $config->get('except_roles'),
      '#description' => $this->t('Do not display Terms and Conditions check box for the selected user roles.'),
    ];

    $form['user_profile_display'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show T&Cs on user profile edit pages'),
      '#default_value' => $config->get('user_profile_display'),
    ];

    $form['accept_every_login'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Ask to accept T&Cs on every login'),
      '#default_value' => $config->get('accept_every_login'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValues();

    $this->configFactory->getEditable('legal.settings')
      ->set('except_roles', $values['except_roles'])
      ->set('user_profile_display', $values['user_profile_display'])
      ->set('accept_every_login', $values['accept_every_login'])
      ->save();

    $this->messenger()->addMessage($this->t('Configuration changes have been saved.'));

    parent::submitForm($form, $form_state);

    // @todo flush only the cache elements that need to be flushed.
    drupal_flush_all_caches();
  }

}
