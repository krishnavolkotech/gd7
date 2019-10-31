<?php

namespace Drupal\cust_group\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class FragebogenUploadFilterForm extends FormBase {
    
    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'fragebogen_upload_filter';
    }
    
    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $request = \Drupal::request()->query;
        $fileName = $request->get('filename');

        $form['#method'] = 'get';

        $form['filename'] = [
            '#type' => 'textfield',
            '#placeholder' => $this->t('Suchbegriff'),
            '#default_value' => isset($fileName) ? $fileName : '',
            '#size' => 30,
            '#maxlength' => 128,
            '#prefix' => "<div class = 'service_search_dropdown hzd-form-element'>",
            '#suffix' => '</div>',
        ];
        
        $form['actions']['submit'] = array(
            '#type' => 'button',
            '#value' => $this->t('Filtern'),
            '#weight' => 99,
            '#attributes' => array(
                'class' => array('fragebogen-submit')
            ),
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
