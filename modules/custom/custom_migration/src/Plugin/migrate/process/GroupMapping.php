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
 *   id = "group_mapping_from_d6"
 * )
 */
class GroupMapping extends ProcessPluginBase {
  
  
  public function getGroupId($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    
    
    $source = $row->getSource();
    
    $data = \Drupal\Core\Database\Database::getConnection('default', $source['target'])
      ->select('og_ancestry', 'source_table_name')
      ->fields('source_table_name')
      ->condition('source_table_name.' . $this->configuration['source'], $value)
      ->execute()
      ->fetchAssoc();
  
    $d8Gid = false;
    if(isset($data['group_nid'])){
      $d8Gid = \Drupal::entityQuery('group')
        ->condition('field_old_reference',$data['group_nid'])
        ->execute();
      $d8Gid = reset($d8Gid);
    }
    return $d8Gid;
    
  }
}