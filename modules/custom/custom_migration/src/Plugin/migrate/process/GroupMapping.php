<?php
/**
 * Created by PhpStorm.
 * User: sandeep
 * Date: 9/2/17
 * Time: 2:53 PM
 */

namespace Drupal\custom_migration\Plugin\migrate\process;


use Drupal\Core\Database\Database;
use Drupal\group\Entity\Group;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides process plugin.
 * Custom process plugin to migrate all foreign keys into comma imploded string in destination table.
 *
 *
 *
 * @MigrateProcessPlugin(
 *   id = "group_mapping_from_d6"
 * )
 */
class GroupMapping extends ProcessPluginBase {
  
  
  public function getGroupId($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    
    
    $source = $row->getSource();
    
    if (empty($row->getSourceProperty('title'))) {
      return FALSE;
    }
    
    $data = \Drupal\Core\Database\Database::getConnection('default', $source['target'])
      ->select('og_ancestry', 'source_table_name')
      ->fields('source_table_name')
      ->condition('source_table_name.' . $this->configuration['source'], $value)
      ->execute()
      ->fetchAssoc();
    
    $d8Gid = FALSE;
    if (isset($data['group_nid'])) {
      $d8Gid = \Drupal::entityQuery('group')
        ->condition('field_old_reference', $data['group_nid'])
        ->execute();
      $d8Gid = reset($d8Gid);
//      print_r($d8Gid);exit;
    }
    return $d8Gid;
    
  }
  
  
  public function getGroupContentTypeId($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $content_name = $row->getSourceProperty('type');
//    print_r($row);
    $gid = $row->getDestinationProperty('gid');
    if (!empty($gid)) {
      $group = Group::load($gid);
      $plugin_id = 'group_node:' . $content_name;
      $plugin = $group->getGroupType()->getContentPlugin($plugin_id);
//      print_r($plugin->getContentTypeConfigId());exit;
      return $plugin->getContentTypeConfigId();
    }
//    exit;
    return FALSE;
  }
  
  public function getNewGroupId($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $d8Gid = \Drupal::entityQuery('group')
      ->condition('field_old_reference', $value)
      ->execute();
    if (!empty($d8Gid)) {
      return reset($d8Gid);
    }
    return FALSE;
  }
  
  public function getGroupForumId($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $source = $row->getSource();
    
    if (empty($row->getSourceProperty('title'))) {
      return FALSE;
    }
    
    $data = \Drupal\Core\Database\Database::getConnection('default', $source['target'])
      ->select('term_hierarchy', 'source_table_name')
      ->fields('source_table_name')
      ->condition('source_table_name.' . $this->configuration['source'], $value)
      ->execute()
      ->fetchAssoc();
    if ($data['parent'] != 0) {
      $tid = $data['parent'];
    }
    else {
      $tid = $data['tid'];
    }
    $groupName = \Drupal\Core\Database\Database::getConnection('default', $source['target'])
      ->select('term_data', 'source_table_name')
      ->fields('source_table_name')
      ->condition('source_table_name.tid', $tid)
      ->execute()
      ->fetchAssoc();
    $d8Gid = FALSE;
    if (isset($data['group_nid'])) {
      $d8Gid = \Drupal::entityQuery('group')
        ->condition('label', $groupName['name'])
        ->execute();
      $d8Gid = reset($d8Gid);
//      print_r($d8Gid);exit;
    }
    return $d8Gid;
  }
  
  function getForumTaxonomyId($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $source = $row->getSource();
    
    if (empty($row->getSourceProperty('nid'))) {
      return FALSE;
    }
    
    $data = \Drupal\Core\Database\Database::getConnection('default', $source['target'])
      ->select('term_node', 'source_table_name')
      ->fields('source_table_name', ['tid'])
      ->condition('source_table_name.nid', $value)
      ->execute()
      ->fetchField();
    if (!empty($data)) {
      return $data;
    }
    return FALSE;
  }
  
  function getFaqCategoryTaxonomyId($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    //5
    return $this->getFaqTaxonomyId($value, $migrate_executable, $row, $destination_property, 5);
  }
  
  function getFaqSeiteTaxonomyId($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    //4
    return $this->getFaqTaxonomyId($value, $migrate_executable, $row, $destination_property, 4);
  }
  
  function getFaqTaxonomyId($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property, $vid) {
    $source = $row->getSource();
    
    $data = Database::getConnection('default', $source['target'])
      ->select('term_node', 'source_table_name')
      ->fields('source_table_name')
      ->condition('source_table_name.nid', $value)
      ->execute()
      ->fetchCol();
    $categoryOrSeiteId = false;
    if ($data) {
      $categoryOrSeiteId = Database::getConnection('default', $source['target'])
        ->select('term_data', 'source_table_name')
        ->fields('source_table_name')
        ->condition('source_table_name.tid', $data, 'IN')
        ->condition('source_table_name.vid', $vid)
        ->execute()
        ->fetchField();
    }
    return $categoryOrSeiteId;
  }
}