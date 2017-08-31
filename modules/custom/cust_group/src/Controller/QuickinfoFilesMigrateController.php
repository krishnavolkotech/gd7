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
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

class QuickinfoFilesMigrateController extends FormBase {
  
  public function buildForm(array $form, FormStateInterface $form_state) {
    // TODO: Implement buildForm() method.
    $form['submit'] = ['#type' => 'submit', '#value' => 'Migrate Quickinfo'];
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
    $db = \Drupal::database();
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
          /*$db->insert('node__upload')
            ->fields([
              'bundle' => 'quickinfo',
              'deleted' => 0,
              'entity_id' => $node->id(),
              'revision_id' => $node->getRevisionId(),
              'langcode' => 'de',
              'delta' => $i++,
              'upload_target_id' => $file,
              'upload_display' => 1,
              'upload_description' => ''
            ])
            ->execute();
          echo $node->id();
          exit;*/
          $node->revision = FALSE;
          $time = $node->getChangedTime();
//          $node->setChangedTime($time);
          
          $node->upload->appendItem([
            'target_id' => $file,
            'display' => 1,
            'description' => '',
          ]);
//          $node->set('changed', $time);
          $node->save();
          $db->update('node_field_revision')
            ->fields(['changed' => $time])
            ->condition('vid', $node->getRevisionId())
            ->execute();
          $db->update('node_field_data')
            ->fields(['changed' => $time])
            ->condition('vid', $node->getRevisionId())
            ->execute();
        }
      }
      
    }
  }
  
  
}