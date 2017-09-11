<?php
/**
 * Created by PhpStorm.
 * User: sandeep
 * Date: 11/9/17
 * Time: 2:46 PM
 */

namespace Drupal\cust_group\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

class MigrateUrlAliasForm extends FormBase {
  
  
  public function getFormId() {
    return 'migrate_url_alias_form';
  }
  
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = ['#type' => 'submit', '#value' => 'Migrate Urls'];
    return $form;
  }
  
  public function submitForm(array &$form, FormStateInterface $form_state) {
    self::prepareBatch();
  }
  
  public function prepareBatch() {
    $d6Db = \Drupal\Core\Database\Database::getConnection('default', 'migrate');
    $d6Urls = $d6Db->select('url_alias', 'u')
      ->fields('u')
      ->condition('src', "^node\\/[0-9]{2,6}$", 'REGEXP')
//      ->range(1400, 1000)
      ->execute()
      ->fetchAll();
//    $x = [];
//    self::processBatch($d6Urls,$x);
//    exit;
    $urlBatch = array_chunk($d6Urls, 100);
    
    $batch = array(
      'title' => t('Migrating Urls'),
      'finished' => '\Drupal\cust_group\Form\MigrateUrlAliasForm::finishedCallback',
    );
    
    foreach ($urlBatch as $item) {
      $batch['operations'][] = array(
        '\Drupal\cust_group\Form\MigrateUrlAliasForm::processBatch',
        [$item]
      );
    }
    
    return batch_set($batch);
  }
  
  
  public static function processBatch(array $d6Urls, &$context) {
    $d8Db = \Drupal::database();
    $aliasStorage = \Drupal::service('path.alias_storage');
    foreach ($d6Urls as $d6Url) {
      
      $d8UrlData = $d8Db->select('url_alias', 'u')
        ->condition('source', '/' . $d6Url->src)
        ->fields('u')
        ->execute()
        ->fetch();
      $lang = !empty($d6Url->language) ? $d6Url->language : LanguageInterface::LANGCODE_NOT_SPECIFIED;
      if (!empty($d8UrlData) && $d8UrlData->alias !== '/' . $d6Url->dst) {
        if (!$aliasStorage->aliasExists('/' . $d6Url->dst, $lang)) {
          if ($aliasStorage->save($d8UrlData->source, '/' . $d6Url->dst, $lang, $d8UrlData->pid) !== FALSE) {
            if (!isset($context['results']['errors'])) {
              $context['results']['errors'] = [];
            }
            $context['results']['aliases'][] = [
              'pid' => $d8UrlData->pid,
              'source' => $d8UrlData->alias,
              'alias' => '/' . $d6Url->dst,
            ];
          }
        }
      }
    }
    
  }
  
  
  public static function finishedCallback($success, $results, $operations) {
    if ($success) {
      if (!empty($results['aliases'])) {
        $ymlData = Yaml::dump($results['aliases']);
        file_unmanaged_save_data($ymlData, 'public://url_migration.YAML',FILE_EXISTS_REPLACE);
      }
      drupal_set_message(\Drupal::translation()
        ->translate('Urls imported successfully and log can be seen at @file_link',['@file_link'=>file_create_url('public://url_migration.YAML')]));
      
    }
  }
  
}