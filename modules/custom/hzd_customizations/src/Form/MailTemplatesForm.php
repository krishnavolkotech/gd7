<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

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
class MailTemplatesForm extends ConfigFormBase
{
    
    //put your code here
    
    var $mailType = null;
    static function create(ContainerInterface $container){
        return new static($container->get('config.factory'),$container->get('current_route_match'));
    }
    
    function __construct(ConfigFactoryInterface $config_factory, $routeMatch) {
        parent::__construct($config_factory);
        $this->routeMatch = $routeMatch;
        $this->mailType = $this->routeMatch->getParameter('type');
    }
    
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        $formId = $this->mailType.'_mail_template';
//        echo $formId;exit;
        return $formId;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return ['hzd_custom.mailtemplates'];
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $config = $this->config('hzd_custom.mailtemplates');
        $data = $config->get($this->mailType);
        $nodeType = \Drupal\node\Entity\NodeType::load($this->mailType);
        $form['#title'] = t('Mail Template for @type',['@type'=>$nodeType->label()]);
        $form['subject'] = array(
            '#type' => 'textfield',
            '#title' => t('Subject'),
            '#default_value' => $data['subject'] ?: NULL,
        );
        $form['mail_content'] = array(
            '#type' => 'textarea',
            '#title' => t('Content'),
            '#default_value' => $data['mail_content'] ?: NULL,
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
        parent::submitForm($form, $form_state);
        $data = ['subject' => $form_state->getValue('subject'), 'mail_content' => $form_state->getValue('mail_content')];
        $this->config('hzd_custom.mailtemplates')
            ->set($this->mailType, $data)
            ->save();
    }
    
}
