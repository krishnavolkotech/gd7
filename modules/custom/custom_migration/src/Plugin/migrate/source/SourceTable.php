<?php
namespace Drupal\custom_migration\Plugin\migrate\source;

use Drupal\Core\Database\Database;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Source plugin for migrating custom database table.
 *
 * @MigrateSource(
 *   id = "source_table"
 * )
 */
class SourceTable extends SqlBase {
  
  
  /**
   * The name of the destination table.
   *
   * @var string
   */
  protected $tableName;
  
  /**
   * IDMap compatible array of id fields.
   *
   * @var array
   */
  protected $idFields;
  
  /**
   * Array of fields present on the destination table.
   *
   * @var array
   */
  protected $fields;
  
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state);
    $this->tableName = $configuration['table_name'];
    $this->idFields = $configuration['id_fields'];
    $this->fields = isset($configuration['fields']) ? $configuration['fields'] : [];
    $this->supportsRollback = TRUE;
  }
  
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('state')
    );
  }
  
  
  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select($this->tableName, 'u');
    $query->fields('u');
//    $query->innerJoin('service_downtimes','s','s.downtime_id = u.down_id');
//    $query->fields('s');
//    $query->range(0, 10)
    ;
    return $query;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getIds() {
    if (empty($this->idFields)) {
      throw new MigrateException('Id fields are required for a table destination');
    }
    return $this->idFields;
  }
  
  /**
   * {@inheritdoc}
   */
  public function fields() {
    return $this->fields;
    /*$fields = [
      'uid' => $this->t('User ID'),
      'firstname' => $this->t('First Name'),
      'lastname' => $this->t('Last Name'),
      'phone' => $this->t('Phone'),
      'position' => $this->t('Position'),
      'state_id' => $this->t('State Id'),
    ];
    return $fields;*/
  }
  
  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Perform extra pre-processing for keywords terms, if needed.
    return parent::prepareRow($row);
  }
}