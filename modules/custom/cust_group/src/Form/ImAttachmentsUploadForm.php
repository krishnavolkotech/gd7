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
    $node = \Drupal::routeMatch()->getParameter('node');
    $settings = $node->get('field_im_upload_page_files')->getSettings();
    $validators = array(
        'file_validate_extensions' => array($settings['file_extensions']),
        'file_validate_size' => array(\Drupal\Component\Utility\Bytes::toInt($settings['max_filesize'])),
    );
    $form['upload_file'] = array(
        '#type' => 'managed_file',
//        '#title' => 'kjdsfds',
        '#upload_location' => 'private://',
        '#upload_validators' => $validators,
//        '#description' => 'jsdbfjksdbfjbsdkjfsj',
        '#progress_indicator' => 'bar',
        '#progress_message' => t('Uploading File'),
    );
    $form['form_build_id']['#access'] = FALSE;
    $form['form_token']['#access'] = FALSE;
    $form['form_id']['#access'] = FALSE;
    $form['upload_submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Upload'),
        '#submit' => [[$this, 'submitForm']],
    ];
    $node = \Drupal::routeMatch()->getParameter('node');
    $files = $node->get('field_im_upload_page_files')->referencedEntities();
    $form['files'] = [
        '#type' => 'table',
        '#attributes' => ['class' => ['files']],
        '#header' => [$this->t('User'), $this->t('File name'), $this->t('Action')],
    ];
    foreach ($files as $file) {
      $form['files'][$file->id()]['owner'] = [
          '#type' => 'link',
          '#title' => $file->getOwner()->getDisplayName(),
          '#url' => $file->getOwner()->toUrl(),
      ];


      $form['files'][$file->id()]['filename'] = [
          '#type' => 'link',
          '#title' => $file->getFileName(),
          '#url' => \Drupal\Core\Url::fromUri($file->url()),
      ];
//      pr($file->url());exit;
//      ['#markup' => $file->getFilename()];
      $form['files'][$file->id()]['delete'][$file->id()] = ['#type' => 'submit','#name'=>$file->id(), '#value' => $this->t('Delete'), '#fileId' => $file->id(), '#attributes' => ['class' => ['btn-danger'], 'onclick' => t('return confirm("Are you sure?")')]];
    }
    $form['#attached']['drupalSettings']['isImupload'] = 1;
    return $form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $action = $form_state->getTriggeringElement()['#parents'][0];
//    pr($action);exit;
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($action == 'upload_submit') {
      foreach ($values['upload_file'] as $uploadedFile) {
        $node->get('field_im_upload_page_files')->appendItem(['target_id' => $uploadedFile]);
      }
      drupal_set_message(t('File was successfully uploaded!'));
    } elseif ($action == 'files') {
      $deleteId = $form_state->getTriggeringElement()['#fileId'];
//      pr($deleteId);exit;
      $count = 0;
      foreach ($node->get('field_im_upload_page_files')->getValue() as $file_field) {
        if ($file_field['target_id'] == $deleteId) {
          $node->get('field_im_upload_page_files')->removeItem($count);
          file_delete($file_field['target_id']);
        } else {
          $count++;
        }
      }
      drupal_set_message(t('File was successfully deleted!'));
    }
    $node->save();
  }

}
