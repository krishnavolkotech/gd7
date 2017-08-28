<?php

namespace Drupal\hzd_customizations\Form;

use Drupal\Component\DependencyInjection\Container;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Description of DowntimesMailTemplate
 *
 * @author sandeep
 */
class GroupContentMailTemplatesForm extends ConfigFormBase {
  
  //put your code here
  
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'), $container->get('current_route_match'));
  }
  
  public function __construct(ConfigFactoryInterface $config_factory, $routeMatch) {
    parent::__construct($config_factory);
    $this->routeMatch = $routeMatch;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    $formId = 'group_content_mail_template';
//        echo $formId;exit;
    return $formId;
  }
  
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hzd_customizations.mailtemplates'];
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hzd_customizations.mailtemplates');
    $type = $this->routeMatch->getParameter('type');
    $types = [
      'group' => $this->t('Group'),
      'group_content' => $this->t('Group Content')
    ];
    $data = $config->get($type);
    $form['#title'] = t('Mail Template for @type', ['@type' => $types[$type]]);
    $form['subject'] = array(
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#default_value' => $data['subject'] ?: NULL,
    );
    $form['mail_content'] = array(
      '#type' => 'textarea',
      '#title' => t('Content'),
      '#default_value' => $data['mail_content'] ?: NULL,
      '#disabled' => TRUE,
      '#value' => $this->t('This template uses node view.')
    );
    
    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['token_tree'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => 'all',
        '#show_restricted' => TRUE,
      ];
    }
    return parent::buildForm($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $type = $this->routeMatch->getParameter('type');
    parent::submitForm($form, $form_state);
    $data = [
      'subject' => $form_state->getValue('subject'),
      'mail_content' => $form_state->getValue('mail_content')
    ];
    $this->config('hzd_customizations.mailtemplates')
      ->set($type, $data)
      ->save();
  }
  
}
