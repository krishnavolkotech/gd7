<?php

namespace Drupal\hzd_customizations\Form;

use Drupal\Component\DependencyInjection\Container;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Description of DowntimesMailTemplate
 *
 * @author sandeep
 */
class MailTemplatesForm extends ConfigFormBase {
  
  //put your code here
  
  protected $mailType = NULL;
  
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'), $container->get('current_route_match'));
  }
  
  public function __construct(ConfigFactoryInterface $config_factory, $routeMatch) {
    parent::__construct($config_factory);
    $this->routeMatch = $routeMatch;
    $this->mailType = $this->routeMatch->getParameter('type');
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    $formId = $this->mailType . '_mail_template';
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
    $data = $config->get($this->mailType);
    $typeLabel =null;
    if (in_array($this->mailType, ['group', 'group_content'],TRUE)) {
      $types = [
        'group' => $this->t('Group'),
        'group_content' => $this->t('Group Content')
      ];
      $typeLabel = $types[$this->mailType];
    }
    else {
      $nodeType = NodeType::load($this->mailType);
      $typeLabel = $nodeType->label();
    }
    $form['#title'] = $this->t('Mail Template for @type', ['@type' => $typeLabel]);;
    $form['subject_insert'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Subject Insert'),
      '#description' => $this->t('Mail subject when content is inserted'),
      '#default_value' => isset($data['subject_insert']) ? $data['subject_insert'] : NULL,
    );
    $form['subject_update'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Subject Update'),
      '#description' => $this->t('Mail subject when content is updated'),
      '#default_value' => isset($data['subject_update']) ? $data['subject_update'] : NULL,
    );
    $form['mail_view'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Send details page display as mail content'),
      '#description' => $this->t('Check if mail content is going to be the same way it is shown on the details page'),
      '#default_value' => isset($data['mail_view']) ? $data['mail_view'] : NULL,
    );
    
    $form['mail_content'] = array(
      '#type' => 'text_format',
      '#title' => t('Mail Content'),
      '#format' => 'full_html',
      '#default_value' => isset($data['mail_content']) ? $data['mail_content'] : NULL,
      '#states' => [
        'visible' => array(
          ':input[name="mail_view"]' => array('checked' => FALSE),
        ),
      ],
    );
    $form['mail_footer'] = array(
      '#type' => 'text_format',
      '#title' => t('Mail footer'),
      '#description' => $this->t('Footer of the mail content'),
      '#format' => 'full_html',
      '#default_value' => isset($data['mail_footer']) ? $data['mail_footer'] : NULL,
    );
    /*if($this->mailType == 'downtimes'){
      $form['mail_content']['#disabled'] = true;
      $form['mail_content']['#value'] = $this->t('This template uses node view.');
    }*/
    
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
//    pr($form_state->getValues());exit;
    parent::submitForm($form, $form_state);
    $data = [
      'subject_insert' => $form_state->getValue('subject_insert'),
      'subject_update' => $form_state->getValue('subject_update'),
      'mail_content' => $form_state->getValue('mail_content')['value'],
      'mail_footer' => $form_state->getValue('mail_footer')['value'],
      'mail_view' => $form_state->getValue('mail_view'),
    ];
    $this->config('hzd_customizations.mailtemplates')
      ->set($this->mailType, $data)
      ->save();
  }
  
}
