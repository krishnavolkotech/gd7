<?php

namespace Drupal\cust_group\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FragebogenUploadForm.
 *
 * @package Drupal\cust_group\Form
 */
class FragebogenUploadForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'fragebogen_upload_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity_type_id = 'node';
    $bundle = 'fragebogen_upload';
    
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $fields = $entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    $settings = $fields['field_upload']->getSettings();
    //kint($settings);exit;

    $validators = array(
        'file_validate_extensions' => array($settings['file_extensions']),
        'file_validate_size' => array(\Drupal\Component\Utility\Bytes::toInt($settings['max_filesize'])),
    );

    $file_upload_help = [
          '#theme' => 'file_upload_help',
          '#description' => '',
          '#upload_validators' => $validators,
          '#cardinality' => 1,
      ];

    $form = array(
        '#attributes' => array('enctype' => 'multipart/form-data'),
    );
    
    $form['upload_file'] = array(
        '#type' => 'managed_file',
        '#title' => t('File'),
        '#upload_location' => 'public://release-fragebogen/',
        '#upload_validators' => $validators,
        '#description' => \Drupal::service('renderer')->renderPlain($file_upload_help),
        '#progress_indicator' => 'bar',
        '#progress_message' => t('Uploading File'),
        '#required' => TRUE,
    );
    
    $form['form_build_id']['#access'] = FALSE;
    $form['form_token']['#access'] = FALSE;
    $form['form_id']['#access'] = FALSE;
    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['upload_submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Upload'),
        '#submit' => [[$this, 'submitForm']],
    ];
    $form['#attached']['drupalSettings']['isImupload'] = 1;
    return $form;
  }
  
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $action = $form_state->getTriggeringElement()['#parents'][0];
    $values = $form_state->getValues();
    if ($action == 'upload_submit') {
      if (empty($values['upload_file'])) {
        $form_state->setErrorByName('upload_file',t('Field @title is required',['@title'=>$form['upload_file']['#title']]));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_get_messages(null, TRUE);
    $values = $form_state->getValues();
    $action = $form_state->getTriggeringElement()['#parents'][0];

    if ($action == 'upload_submit') {
      if (!empty($values['upload_file'])) {
        $file = \Drupal::entityTypeManager()->getStorage('file')->load($form_state->getValue('upload_file')[0]);

        $fragebogen_data = \Drupal\node\Entity\Node::create(array(
          'type' => 'fragebogen_upload',
          'title' => $file->getFilename(). ':'. date('d.m.Y H:i:s'),
          'field_upload' => $file->id(),
          'description' => array(
              'value' =>$values['description'],
              'format' => 'plain_text',
          ),
        ));
        $fragebogen_data->save();
      }
      $message = t('File was successfully uploaded!');
    }
    drupal_set_message($message);
  }  
}
