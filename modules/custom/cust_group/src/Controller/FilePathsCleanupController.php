<?php
/**
 * Created by PhpStorm.
 * User: sandeep
 * Date: 28/3/17
 * Time: 1:10 PM
 */

namespace Drupal\cust_group\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

class FilePathsCleanupController extends FormBase {


  protected static $d6BasePath = '/srv/www/betriebsportal/prd/files';
  protected static $d6url = 'http://betriebsportal-konsens.hessen.testa-de.net';
  
  public function fileClean() {
    
    /*$d6Db = \Drupal\Core\Database\Database::getConnection('default', 'migrate');
    
    $d6Files = $d6Db->select('files', 'f')
      ->fields('f')
//      ->range(0, 15)
      ->execute()
      ->fetchAll();*/
    $files = \Drupal::entityQuery('file')
//      ->range(114, 5)
      ->execute();
//    pr($files);exit;
//    $d8Files = File::loadMultiple($files);
    
    $fileChunks = array_chunk($files, 5, TRUE);
//    self::migrateFile($d8Files, []) ;
//    exit;
    //Adding all the files from D6 to process one after one in batch
    $batch = array(
      'title' => t('Migrating'),
//      'operations' => array(
//        array('::migrateFile', array($account->id(), 'story')),
//      ),
      'finished' => '::finishedCallBack',
    );
    
    foreach ($fileChunks as $d6File) {
      $batch['operations'][] = array(
        '\Drupal\cust_group\Controller\FilePathsCleanupController::migrateFile',
        [$d6File]
      );
    }
//    pr($files);
//    pr($batch);exit;
//    exit;
    return batch_set($batch);
  }
  
  
  public function buildForm(array $form, FormStateInterface $form_state) {
    // TODO: Implement buildForm() method.
    $form['submit'] = ['#type' => 'submit', '#value' => 'Mgrate file paths'];
    return $form;
  }
  
  public function getFormId() {
    return 'migrate_file_path';
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
    self::fileClean();
  }
  
  
  public static function migrateFile($d8Files, $context) {
//    $d6File = reset($data);
    global $base_url;
    $d8Db = \Drupal::database();
    $d6Db = \Drupal\Core\Database\Database::getConnection('default', 'migrate');
    foreach ($d8Files as $d8FileId) {
      $d8File = File::load($d8FileId);
      $d6Fid = $d8Db->select('migrate_map_d6_file', 'map')
        ->fields('map', ['sourceid1'])
        ->condition('destid1', $d8File->id())
        ->execute()
        ->fetchField();
      if (empty($d6Fid)) {
        return;
      }
//      $d6Db->query('SET sql_mode = ""')->execute();
      $nid = $d6Db->select('content_field_page_files', 'c')
        ->fields('c', ['nid', 'vid', 'field_page_files_list'])
        ->condition('field_page_files_fid', $d6Fid)
        ->execute()
        ->fetchAll();
      
      $field = 'field_page_files';
      if (empty($nid)) {
        $nid = $d6Db->select('content_type_planning_files', 'c')
          ->fields('c', ['nid', 'vid', 'field_upload_planning_file_list'])
          ->condition('field_upload_planning_file_fid', $d6Fid)
          ->execute()
          ->fetchAll();
        $field = 'field_upload_planning_file';
        if (empty($nid)) {
          continue;
        }
      }
      $vid = NULL;
      foreach ($nid as $item) {
        if (!$vid || ($vid && $vid < $item->vid)) {
          $vid = $item->vid;
          $nodeId = $item->nid;
          $display = $item->field_page_files_list;
        }
      }
//      pr($vid);
//      pr($nid);
//      exit;
//      pr($d6Fid);
//      pr($nid);
//      exit;
      $node = Node::load($nodeId);
      if($node->getRevisionId() != $vid){
        continue;
      }
      $time = $node->getChangedTime();
      $node->revision = FALSE;
      $uploads = $node->get('field_page_files')->getValue();
      $attachedFiles = [];
      foreach ($uploads as $item) {
        $attachedFiles[] = $item['target_id'];
      }
      if (!in_array($d8File->id(), $attachedFiles)) {
        $node->{$field}->appendItem([
          'target_id' => $d8File->id(),
          'display' => $display,
          'description' => '',
        ]);
        $node->auto_nodetitle_applied = 1;
        $node->sendNomail = 1;
        $node->save();
        $node->revision = FALSE;
        $node->setChangedTime($time);
        $node->sendNomail = 1;
        $node->save();
        \Drupal::logger('files_migration')
          ->debug($node->id() . '==' . $d8File->id(), ['files_migration']);
      }
      /*$body = $node->get('body')->value;
      $newFile = File::load($d8File->id());
      $d8FilePath = $newFile->url();
      $d6Url = str_replace($base_url, self::$d6url, $d8FilePath);
//      pr($d6Url);exit;
//      $replaced = substr_compare($body, self::$d6url);
      $linkExists = strpos($body, $d6Url);
      $replacedBody = '';
      if ($linkExists !== FALSE) {
        $replacedBody = str_replace($d6Url, $d8FilePath . 'migrated', $body);
        $node->body->value = $replacedBody;
        $node->setChangedTime($time);
        $node->sendNomail = 1;
        $node->save();
        \Drupal::logger('files_migration')
          ->debug($node->id() . '==' . $d6Url . '==' . $d8FilePath, ['migr-upd-link']);
      }*/
      /* echo 12;
       pr($replacedBody);
       exit;
 //      $node->save();
       exit;*/


//      $node->
      $d6FileEntity = $d6Db->select('files','f')
        ->fields('f')
        ->condition('fid',$d6Fid)
        ->execute()
        ->fetchAssoc();
      
      $d6Url = self::$d6url . str_replace(self::$d6BasePath, '/system/files', $d6FileEntity['filepath']);
      $newFile = File::load($d8File->id());
      $d8FilePath = $newFile->url();
      //Restore file created timestamp from d6
      
      $newFile->set('created',$d6FileEntity['timestamp']);
      $newFile->save();
      
//      $d8FilePath = file_create_url($d8File->getFileUri());
      $d8FilePath = str_replace($base_url, self::$d6url, $d8FilePath.'-migrate');
//      pr($d6Url);
//      pr($d8FilePath);exit;
      $filePath = $d6Url;
      $nodesQuery = $d8Db->select('node__body', 'nb')
        ->condition('body_value', '%' . $filePath . '%', 'LIKE')
        ->fields('nb', ['entity_id', 'body_value', 'bundle'])
        ->execute()
        ->fetchAll();
//      pr($nodesQuery);exit;
      foreach ((array) $nodesQuery as $item) {
        $bodyData = $item->body_value;
        $replacedData = str_replace($filePath, $d8FilePath, $bodyData);
        
        $update = $d8Db->update('node__body')
          ->fields(['body_value' => $replacedData])
          ->condition('entity_id', $item->entity_id)
          ->condition('bundle', $item->bundle)
          ->execute();
        \Drupal::logger('clean-body')
          ->debug($item->entity_id . '==' . $d8FilePath, ['clean-body']);
      }
      $nodeRevisionQuery = $d8Db->select('node_revision__body', 'nb')
        ->condition('body_value', '%' . $filePath . '%', 'LIKE')
        ->fields('nb', ['revision_id', 'body_value'])
        ->execute()
        ->fetchAll();
//      pr($nodeRevisionQuery);exit;
      foreach ((array) $nodeRevisionQuery as $item) {
        $bodyData = $item->body_value;
        $replacedData = str_replace($filePath, $d8FilePath, $bodyData);
//        pr($replacedData);exit;
        $d8Db->update('node_revision__body')
          ->fields(['body_value' => $replacedData])
          ->condition('revision_id', $item->revision_id)
          ->execute();
        \Drupal::logger('clean-revision')
          ->debug($item->revision_id . '==' . $d8FilePath, ['clean-revision']);
      }
    }
    $context['message'] = 'Cleaning files';
//    $context['results'] = ;
  }
  
  
  public static function finishedCallBack($success, $results, $operations) {
    // The 'success' parameter means no fatal PHP errors were detected. All
    // other error management should be handled using 'results'.
    if ($success) {
      $message = \Drupal::translation()->formatPlural(
        count($results),
        'One File processed.', '@count file processed.'
      );
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }
  
  public function checkFiles() {
    $d6Db = \Drupal\Core\Database\Database::getConnection('default', 'migrate');
    
    $d6Files = $d6Db->select('files', 'f')
      ->fields('f')
      ->condition('fid', 11154)
      ->range(0, 15)
      ->execute()
      ->fetchAll();
    
    foreach ($d6Files as $d6File) {
      $data = [];
      self::migrateFile($d6File, $data);
//      $batch['operations'][] = array('::migrateFile', $d6File);
//      echo $filePath . '<br>'.$d8FilePath;exit;
    }
  }
}