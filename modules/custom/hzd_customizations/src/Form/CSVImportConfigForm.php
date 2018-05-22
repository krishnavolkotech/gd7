<?php

namespace Drupal\hzd_customizations\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;



class CSVImportConfigForm extends ConfigFormBase{



    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'imports_upload_form';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return ['hzd_customizations.uploads'];
    }


    private function prepareImportPaths(){
        $pmCon = \Drupal::config('problem_management.settings');
        $rmCon = \Drupal::config('hzd_release_management.settings');
        $data = [];
        $data[$pmCon->get('import_path')] = $this->t('Problems');
        $data[$rmCon->get('import_path_csv_released')] = $this->t('Released Releases');
        $data[$rmCon->get('import_path_csv_progress')] = $this->t('Inprogress Releases');
        $data[$rmCon->get('import_path_csv_locked')] = $this->t('Locked Releases');
        $data[$rmCon->get('import_path_csv_ex_eoss')] = $this->t('Exeoss Releases');
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form = [];
        $form['#attributes']['id'] = ['form_data_node'];
        $form['csv_path'] = [
            '#type' => 'select',
            '#title' => $this->t('CSV Path'),
            '#options' => $this->prepareImportPaths(),
            '#empty_option' => t('--select--'),
            '#weight' => 1,
            '#ajax' => array(
                'callback' => '::loadPreview',
                'event' => 'change',
                'wrapper' => 'form_data_node',
                'progress' => array(
                  'type' => 'throbber',
                ),
              ),
        ];

        $form['csv_content'] = [
            '#type' => 'textarea',
            '#title' => t('Content'),
            '#rows' => 20,
            '#weight' => 3,
        ];
        $form['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Upload'),
            '#weight' => 4,
        ];
        return $form;
    }

    public function loadPreview(array &$form, FormStateInterface $form_state){
        $path = DRUPAL_ROOT.'/'.$form_state->getValue('csv_path');
        if(!is_file($path) || !is_writable($path)){
            \Drupal::messenger()->addMessage('Invalid file path/permissions', 'error');
        }
        $file = file_get_contents($path);
        $form['#attributes']['id'] = ['form_data_node'];
        $form['preview'] = [
            '#type' => 'container',
            'prev' => [
                '#title' => t('Preview'),
                '#plain_text'=>$file
            ],
            '#attributes'=>['style'=>'white-space: pre-wrap;'],
            '#weight' => 2,
        ];
        $form['csv_content']['#value'] = $file;
        return $form;
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
    $path = DRUPAL_ROOT.'/'.$form_state->getValue('csv_path');
    if(!is_file($path) || !is_writable($path)){
        \Drupal::messenger()->addMessage('Invalid file path/permissions', 'error');
    }else{
        $file = $form_state->getValue('csv_content');
        file_put_contents($path, $file);
        parent::submitForm($form, $form_state);
    }
  }
}