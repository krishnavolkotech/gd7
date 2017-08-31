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

class FilePathsCleanupController extends FormBase {
  
  
  protected static $d6BasePath = '/srv/www/betriebsportal/prd/files';
  protected static $d6url = 'http://betriebsportal-konsens.hessen.testa-de.net';
  
  public function fileClean() {
    
    $d6Db = \Drupal\Core\Database\Database::getConnection('default', 'migrate');
    
    $d6Files = $d6Db->select('files', 'f')
      ->fields('f')
//      ->range(0, 15)
      ->execute()
      ->fetchAll();
    //Adding all the files from D6 to process one after one in batch
    $batch = array(
      'title' => t('Migrating'),
//      'operations' => array(
//        array('::migrateFile', array($account->id(), 'story')),
//      ),
      'finished' => '::finishedCallBack',
    );
    
    foreach ($d6Files as $d6File) {
      $batch['operations'][] = array('\Drupal\cust_group\Controller\FilePathsCleanupController::migrateFile', [$d6File]);
    }
    return batch_set($batch);
  }
  
  
  public function buildForm(array $form, FormStateInterface $form_state) {
    // TODO: Implement buildForm() method.
    $form['submit'] = ['#type'=>'submit','#value'=>'Mgrate file paths'];
    return $form;
  }
  
  public function getFormId() {
    return 'migrate_file_path';
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
    self::fileClean();
  }
  
  
  public static function migrateFile($d6File, $context) {
//    $d6File = reset($data);
    global $base_url;
    $d8Db = \Drupal::database();
    /*$d8Fid = $d8Db->select('migrate_map_d6_file', 'map')
      ->fields('map', ['destid1'])
      ->condition('sourceid1', $d6File->fid)
      ->execute()
      ->fetchField();
    if (empty($d8Fid)) {
      return;
    }*/
    $d8File = File::load($d6File->fid);
    if (!$d8File) {
      return;
    }
    $d8FilePath = file_create_url($d8File->getFileUri());
    $d8FilePath = str_replace($base_url, self::$d6url, $d8FilePath);
//      pr($d8FilePath);exit;
    $filePath = self::$d6url . str_replace(self::$d6BasePath, '/system/files', $d6File->filepath);
    $nodesQuery = $d8Db->select('node__body', 'nb')
      ->condition('body_value', '%' . $filePath . '%', 'LIKE')
      ->fields('nb', ['entity_id', 'body_value','bundle'])
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
      \Drupal::logger('migr-upd')->debug($item->entity_id.'=='.$d8FilePath, ['migr-upd']);
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
      \Drupal::logger('migr-rev')->debug($item->revision_id.'=='.$d8FilePath, ['migr-rev']);
    }
    $context['message'] = 'Cleaning file';
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
  
  public function checkFiles(){
    $d6Db = \Drupal\Core\Database\Database::getConnection('default', 'migrate');
  
    $d6Files = $d6Db->select('files', 'f')
      ->fields('f')
      ->condition('fid',11154)
      ->range(0, 15)
      ->execute()
      ->fetchAll();
  
    foreach ($d6Files as $d6File) {
      $data = [];
      self::migrateFile($d6File,$data);
//      $batch['operations'][] = array('::migrateFile', $d6File);
//      echo $filePath . '<br>'.$d8FilePath;exit;
    }
  }
}