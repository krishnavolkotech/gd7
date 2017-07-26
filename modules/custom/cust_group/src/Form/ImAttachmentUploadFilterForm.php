<?php

namespace Drupal\cust_group\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ImAttachmentUploadFilterForm extends FormBase {
    
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'im_attachment_upload_filter';
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $request = \Drupal::request()->query;
        $state = $request->get('state');
        $statesoptions[] = 'Select';
        $states = \Drupal::database()->select('states', 'st')
              ->fields('st', ['state', 'abbr'])
              ->execute()
              ->fetchAll();
        foreach($states as $key => $value) {
            if(!empty($value->abbr)) {
                $statesoptions[strtolower($value->abbr)] = $value->state;
            }
        }
        $form['#method'] = 'get';
        $form['state'] = array(
            '#type' => 'select',
            '#options' => $statesoptions,
            '#default_value' => isset($state) ? $state : '',
            '#attributes' => array(
                'onchange' => 'this.form.submit()',
            ),
            '#prefix' => "<div class = 'service_search_dropdown hzd-form-element'>",
            '#suffix' => '</div>',
        );
        
        $form['submit'] = array(
            '#type' => 'button',
//            '#attributes' => array("class" => ["filter_submit"]),
            '#prefix' => '<div class = "hzd-form-element-auto hide">',
            '#suffix' => '</div>',
        );
        $form['actions']['reset'] = array(
            '#type' => 'button',
            '#value' => t('Reset'),
            '#weight' => 100,
            '#attributes' => array(
                'onclick' => 'reset_form_elements(); return false;'
            ),
        );
        return $form;
    }
    
    /**
     * {@inheritDoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
    }
    
    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
    }
}