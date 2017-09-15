<?php
/**
 * Created by PhpStorm.
 * User: sandeep
 * Date: 12/9/17
 * Time: 8:12 PM
 */

namespace Drupal\cust_group\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class DetachOldFilesForm extends FormBase {
  public function getFormId() {
    return 'detach_old_files';
  }
  
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'Detach old files'
    ];
    return $form;
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
    self::prepareBatch();
  }
  
  
  static public function prepareBatch() {
    $d6Db = \Drupal\Core\Database\Database::getConnection('default', 'migrate');
    $d6Nodes = $d6Db->select('node', 'n')
      ->fields('n')
      ->condition('type', 'page')
//      ->condition('nid', 542)
      ->execute()
      ->fetchAll();
    
    $batch = array(
      'title' => t('Detaching Old Files from pages'),
      'finished' => '\Drupal\cust_group\Form\DetachOldFilesForm::finishedCallBack',
    );
    foreach ($d6Nodes as $item) {
      $batch['operations'][] = array(
        '\Drupal\cust_group\Form\DetachOldFilesForm::detach',
        [$item]
      );
    }

//    pr(count($quickinfos));
//    exit;
    return batch_set($batch);
  }
  
  static public function detach($d6Node, &$context) {
    $d6Db = \Drupal\Core\Database\Database::getConnection('default', 'migrate');
    $d8Db = \Drupal::database();

//    pr($d6Nodes);exit;
//    foreach ($d6Nodes as $d6Node) {
    $filesQuery = $d6Db->select('content_field_page_files', 'cp');
    $filesQuery->fields('cp', ['field_page_files_fid']);
    $filesQuery->addExpression('max(cp.vid)', 'vvid');
    $filesQuery->condition('nid', $d6Node->nid);
    $filesQuery->groupBy('field_page_files_fid');
    $files = $filesQuery->execute()
      ->fetchAll();
    $oldFiles = [];
    foreach ($files as $file) {
      if ($d6Node->vid != $file->vvid) {
        $oldFiles[] = $file->field_page_files_fid;
//          pr($file);
      }
    }
    if(empty($oldFiles)){
      return FALSE;
    }
    $oldD8Files = $d8Db->select('migrate_map_d6_file', 'map')
      ->fields('map', ['destid1'])
      ->condition('sourceid1', $oldFiles, 'IN')
      ->execute()
      ->fetchCol();
//      pr($oldD8Files);exit;
    $d8Node = Node::load($d6Node->nid);
    $fileRemoved = 0;
//      $removedIndexes = [];
//      pr($d8Node->get('field_page_files')->getIterator());exit;
    
    foreach ($oldD8Files as $oldD8File) {
      $values = $d8Node->get('field_page_files')->getValue();
      $index = NULL;
      foreach ($values as $key => $value) {
        if ($value['target_id'] == $oldD8File) {
          $index = $key;
          break;
        }
      }
//        $index = array_search($oldD8File, $values);
//        pr($values);exit;
      if (is_null($index)) {
        continue;
      }
      $d8Node->get('field_page_files')->removeItem($index);
      $fileRemoved = 1;
    }
    
    if ($fileRemoved) {
      $usr = $d8Node->getRevisionUserId();
      $d8Node->setNewRevision();
//        $d8Node->revision_log = 'Created revision for node';
      $d8Node->setRevisionCreationTime(REQUEST_TIME);
      $d8Node->setRevisionUserId($usr);
      $d8Node->sendNomail = 1;
      $d8Node->save();
    }
    $context['message'] = t('Detaching files from node %node', ['%node' => $d8Node->id()]);
    /*  pr(count($oldD8Files));
      
      exit;
    
    pr($d6Nodes);
    exit;*/
  }
  
  
  public static function finishedCallBack($success, $results, $operations) {
    if ($success) {
      drupal_set_message(\Drupal::translation()->translate('Unlinking old files completed'));
    }
  }
  
}