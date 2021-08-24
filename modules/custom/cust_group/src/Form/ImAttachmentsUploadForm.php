<?php

namespace Drupal\cust_group\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ImAttachmentsUploadForm.
 *
 * @package Drupal\cust_group\Form
 */
class ImAttachmentsUploadForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'im_attachments_upload_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    //$node = \Drupal::routeMatch()->getParameter('node');
    //$settings = $node->get('field_im_upload_page_files')->getSettings();
    $entity_type_id = 'node';
    $bundle = 'im_upload_page';
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $fields = $entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    $settings = $fields['field_im_upload_page_files']->getSettings();
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

    $form['upload_file'] = array(
        '#type' => 'managed_file',
        '#title' => t('File'),
        '#upload_location' => 'private://',
        '#upload_validators' => $validators,
        '#description' => \Drupal::service('renderer')->renderPlain($file_upload_help),
        '#progress_indicator' => 'bar',
        '#progress_message' => t('Uploading File'),
        '#required' => TRUE,
    );
    $form['description'] = array(
        '#type' => 'textarea',
        '#title' => $this->t('Description'),
        '#required' => TRUE,
        '#attributes' => ['class' => ['form-group']],
    );
    $form['ticket_id'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Ticket ID'),
        '#required' => TRUE,
    );
    $form['confirm'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Please confirm that the file was encrypted.'),
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
      if (empty($values['confirm'])) {
        $form_state->setErrorByName('confirm',t('Please confirm that the file was encrypted'));
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
//    pr($action);exit;
    $curentuser = \Drupal::currentUser();
    $userid = $curentuser->id();
    $db = \Drupal::database();
    $result = $db->select('cust_profile', 'cp')
        ->fields('cp', array('state_id'))
        ->condition('cp.uid', $userid);
    $stateid = $result->execute()->fetchField();
    $nodedata = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['field_state' => $stateid]);
    $nodeid = array_shift($nodedata)->id();
    $node = \Drupal\node\Entity\Node::load($nodeid);
    $message = null;
    if ($action == 'upload_submit') {
      if (!empty($values['upload_file'])) {
        foreach ($values['upload_file'] as $uploadedFile) {
            $node->get('field_im_upload_page_files')->appendItem(['target_id' => $uploadedFile]);
            $imattachdata = \Drupal\cust_group\Entity\ImAttachmentsData::create(array(
                'nid' => $node->id(),
                'fid' => $uploadedFile,
                'ticket_id' => $values['ticket_id'],
                'langcode' => $node->getUntranslated()->language()->getId(),
                'description' => array(
                   'value' =>$values['description'],
                   'format' => 'plain_text',
                     ),
                )
            );
            $imattachdata->save();
        }
        $node->save();
        $message = t('File was successfully uploaded!');
      }
//      \Drupal::messenger()->addMessage(t('File was successfully uploaded!'));
    }
    \Drupal::messenger()->addMessage($message);
  }

}
