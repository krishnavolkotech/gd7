<?php
/**
 * Created by PhpStorm.
 * User: sandeep
 * Date: 27/6/18
 * Time: 12:19 PM
 */

namespace Drupal\mass_contact\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class FooterConfigForm extends ConfigFormBase {

  public function getEditableConfigNames() {
    return ['mass_contact.settings'];
  }

  public function getFormId() {
    return 'newsletter_footer_config';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $footer = $this->config('mass_contact.settings')
      ->get('footer',['value'=>'','format'=>'full_html']);
    $form['mail_footer'] = array(
      '#type' => 'text_format',
      '#title' => $this->t('Newsletter footer'),
      '#description' => $this->t('Footer of the Newsletter'),
      '#format' => $footer['format'],
      '#default_value' => $footer['value'],
    );
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('mass_contact.settings')
      ->set('footer',$form_state->getValue('mail_footer'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}