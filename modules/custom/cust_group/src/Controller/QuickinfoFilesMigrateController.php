<?php
/**
 * Created by PhpStorm.
 * User: sandeep
 * Date: 1/9/17
 * Time: 12:55 AM
 */

namespace Drupal\cust_group\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

class QuickinfoFilesMigrateController extends FormBase {
  
  public function buildForm(array $form, FormStateInterface $form_state) {
    // TODO: Implement buildForm() method.
    $form['submit'] = ['#type' => 'submit', '#value' => 'Migrate Quickinfo Files'];
    return $form;
  }
  
  public function getFormId() {
    return 'migrate_quickinfo';
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
    self::attachFiles();
  }
  
  public function attachFiles() {
    $quickinfos = \Drupal::entityQuery('node')
      ->condition('type', 'quickinfo')
      ->condition('status', 1)
      ->execute();
    $fileChunks = array_chunk($quickinfos, 5);
    $batch = array(
      'title' => t('Migrating'),
      'finished' => '\Drupal\cust_group\Controller\QuickinfoFilesMigrateController::finishedCallBack',
    );
    foreach ($fileChunks as $quickinfo) {
//      self::updateQuickinfo($quickinfo, []);
      $batch['operations'][] = array(
        '\Drupal\cust_group\Controller\QuickinfoFilesMigrateController::updateQuickinfo',
        [$quickinfo]
      );
    }
    
//    pr(count($quickinfos));
//    exit;
    return batch_set($batch);
  }
  
  public function updateQuickinfo($quickinfos, $context) {
    foreach ($quickinfos as $quickinfo) {
      $node = Node::load($quickinfo);
      
      if (!$node) {
        return FALSE;
      }
      $uniqueId = $node->get('field_unique_id')->value;
      $files = \Drupal::entityQuery('file')
        ->condition('uri', 'private://rz-schnellinfos/' . $uniqueId . '%', 'LIKE')
        ->condition('status', 1)
        ->execute();
      $uploads = $node->get('upload')->getValue();
      $attachedFiles = [];
      foreach ($uploads as $item) {
        $attachedFiles[] = $item['target_id'];
      }
      
      foreach ($files as $file) {
        if (!in_array($file, $attachedFiles)) {
          
          $node->revision = FALSE;
          $time = $node->getTranslation(LanguageInterface::LANGCODE_DEFAULT)->getChangedTime();
          $node->getTranslation(LanguageInterface::LANGCODE_DEFAULT)->setChangedTime($time);
          
          $node->upload->appendItem([
            'target_id' => $file,
            'display' => 1,
            'description' => '',
          ]);
          $node->auto_nodetitle_applied = 1;
          $node->save();
          //Changed timestamp to be retained.
          $node->revision = FALSE;
          $node->getTranslation(LanguageInterface::LANGCODE_DEFAULT)->setChangedTime($time);
          $node->save();
        }
      }
      
      
    }
    $context['message'] = 'Attaching Quickinfo files';
  }
  
  public function finishedCallBack($success, $results, $operations){
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One Node processed.', '@count file processed.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }
  
  
}