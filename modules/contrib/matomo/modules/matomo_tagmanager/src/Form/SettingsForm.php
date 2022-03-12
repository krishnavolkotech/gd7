<?php

declare(strict_types = 1);

namespace Drupal\matomo_tagmanager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Matomo Tag Manager settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Machine name of the config.
   */
  public const CONFIG_NAME = 'matomo_tagmanager.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'matomo_tagmanager_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [self::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_NAME);

    $form['container_location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Container file location'),
      '#description' => $this->t('In case the JS file to load in a subfolder, keep trailing slash. If you use Matomo cloud, it should be left empty. If you use your own instance, it should be "js/".'),
      '#default_value' => $config->get('container_location'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(self::CONFIG_NAME);
    $config->set('container_location', $form_state->getValue('container_location'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
