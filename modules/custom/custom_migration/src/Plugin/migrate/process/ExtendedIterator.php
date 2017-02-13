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
 *   id = "extended_iterator",
 *   handle_multiples = TRUE
 * )
 */
class ExtendedIterator extends ProcessPluginBase {
  
  
  public function iterateData($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    
    $source = $row->getSource();
//    print_r($row->getSource());
//    print_r($this->configuration);
    $data = \Drupal\Core\Database\Database::getConnection('default', $source['target'])
      ->select($source['table_name'], 'source_table_name')
      ->fields('source_table_name', [$this->configuration['source']])
      ->condition('source_table_name.' . $this->configuration['key'], $row->getSourceProperty($this->configuration['key']))
      ->execute()
      ->fetchCol();
    return implode(',', $data);
  }
}