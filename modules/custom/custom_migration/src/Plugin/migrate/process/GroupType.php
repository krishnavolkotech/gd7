<?php
/**
 * Created by PhpStorm.
 * User: sandeep
 * Date: 9/2/17
 * Time: 2:53 PM
 */

namespace Drupal\custom_migration\Plugin\migrate\process;


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
 *   id = "group_type_maintainer"
 * )
 */
class GroupType extends ProcessPluginBase {
  
  
  public function iterateData($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    
    /*    $source = $row->getSource();
        $initialData = $row->getSourceProperty('nid');
    //    print_r($row->getSource());
    //    print_r($this->configuration);
        $data = \Drupal\Core\Database\Database::getConnection('default', $source['target'])
          ->select($source['table_name'], 'source_table_name')
          ->fields('source_table_name', [$this->configuration['source']])
          ->condition('source_table_name.' . $this->configuration['key'], $row->getSourceProperty($this->configuration['key']))
          ->execute()
          ->fetchCol();
        return implode(',', $data);*/
    $source = $row->getSource();
    $initialData = $row->getSourceProperty('nid');
    
    $data = \Drupal\Core\Database\Database::getConnection('default', $source['target'])
      ->select('og', 'source_table_name')
      ->fields('source_table_name')
      ->condition('source_table_name.' . $this->configuration['source'], $value)
      ->execute()
      ->fetchAssoc();
    
    $finalData = 'open';
    if (isset($data) && $data['og_selective'] == 1 && $data['og_private'] == 1) {
      $finalData = 'moderate_private';
    } elseif (isset($data) && $data['og_selective'] == 1 && $data['og_private'] == 0) {
      $finalData = 'moderate';
    } elseif (isset($data) && $data['og_selective'] == 3 && $data['og_private'] == 0) {
      $finalData = 'closed';
    } elseif (isset($data) && $data['og_selective'] == 3 && $data['og_private'] == 1) {
      $finalData = 'closed_private';
    } elseif (isset($data) && $data['og_selective'] == 2) {
      $finalData = 'closed';
    }
    return $finalData;
    
  }
  
  
  function groupDescription($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property){
    $source = $row->getSource();
    $data = \Drupal\Core\Database\Database::getConnection('default', $source['target'])
      ->select('og', 'source_table_name')
      ->fields('source_table_name')
      ->condition('source_table_name.' . $this->configuration['source'], $value)
      ->execute()
      ->fetchAssoc();
    return $data['og_description'];
  }
}