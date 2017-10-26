<?php

/**
 * @file
 * Contains \Drupal\flood_control\Form\SettingsForm.
 */

namespace Drupal\flood_control\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Class SettingsForm.
 *
 * @package Drupal\flood_control\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $flood_config = $this->config('user.flood');
    $contact_config = $this->config('contact.settings');


    $form['user'] = array(
        '#type' => 'fieldset',
        '#title' => t('Login Flooding'),
        '#access' => \Drupal::currentUser()->hasPermission('administer users'),
    );
    $form['user']['ip_limit'] = array(
        '#type' => 'select',
        '#title' => t('Failed login (IP) limit'),
        '#options' => array_combine(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 20, 30, 40, 50, 75, 100, 125, 150, 200,
            250, 500),
            array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 20, 30, 40, 50, 75, 100, 125, 150, 200, 250, 500)),
        '#default_value' => $flood_config->get('ip_limit', 50),
    );
    $form['user']['ip_window'] = array(
        '#type' => 'select',
        '#title' => $this->t('Failed login (IP) window'),
        '#options' =>  array_combine(array(60, 180, 300, 600, 900, 1800, 2700, 3600, 10800, 21600, 32400, 43200, 86400),
                array(60, 180, 300, 600, 900, 1800, 2700, 3600, 10800, 21600, 32400, 43200, 86400)),
        '#default_value' => $flood_config->get('ip_window', 3600),
    );
    $form['user']['user_limit'] = array(
        '#type' => 'select',
        '#title' => t('Failed login (username) limit'),
        '#options' => array_combine(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 20, 30, 40, 50, 75, 100, 125, 150, 200, 250,
            500),
            array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 20, 30, 40, 50, 75, 100, 125, 150, 200, 250, 500)),
        '#default_value' => $flood_config->get('user_limit', 5),
    );
    $form['user']['user_window'] = array(
        '#type' => 'select',
        '#title' => t('Failed login (username) window'),
        '#options' =>  array_combine(array(60, 180, 300, 600, 900, 1800, 2700, 3600, 10800, 21600, 32400, 43200, 86400),
                array(60, 180, 300, 600, 900, 1800, 2700, 3600, 10800, 21600, 32400, 43200, 86400)),
        '#default_value' => $flood_config->get('user_window', 21600),
    );

    // Contact module flood events.
    $form['contact'] = array(
        '#type' => 'fieldset',
        '#title' => t('Contact Forms Flooding'),
        '#access' => \Drupal::currentUser()->hasPermission('administer contact forms'),
    );
    $form['contact']['flood']['limit'] = array(
        '#type' => 'select',
        '#title' => t('Emails sent limit'),
        '#options' => array_combine(array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 20, 30, 40, 50, 75, 100, 125, 150, 200, 250,
            500),
            array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 20, 30, 40, 50, 75, 100, 125, 150, 200, 250, 500)),
        '#default_value' => $contact_config->get('flood.limit', 5),
    );
    $form['contact']['flood']['interval'] = array(
        '#type' => 'select',
        '#title' => t('Emails sent window'),
        '#options' => array_combine(array(60, 180, 300, 600, 900, 1800, 2700, 3600, 10800, 21600, 32400, 43200, 86400),
                array(60, 180, 300, 600, 900, 1800, 2700, 3600, 10800, 21600, 32400, 43200, 86400)),
        '#default_value' => $contact_config->get('flood.interval', 3600),
    );

    // Show a message if the user does not have any access to any options.
    if (!Element::getVisibleChildren($form)) {
      $form['nothing'] = array(
          '#markup' => '<p>' . t('Sorry, there are no flood control options for you to configure.') . '</p>',
      );
      return $form;
    }
    else {
      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('user.flood')
      ->set('ip_limit', $form_state->getValue('ip_limit'))
      ->set('ip_window', $form_state->getValue('ip_window'))
      ->set('user_limit', $form_state->getValue('user_limit'))
      ->set('user_window', $form_state->getValue('user_window'))
      ->save();
    $this->config('contact.settings')
      ->set('flood.limit', $form_state->getValue('limit'))
      ->set('flood.interval', $form_state->getValue('interval'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'user.flood',
      'contact.settings',
    ];
  }

}
