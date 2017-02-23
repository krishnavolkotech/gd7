<?php
/**
 * Created by PhpStorm.
 * User: sandeep
 * Date: 23/2/17
 * Time: 7:26 PM
 */

namespace Drupal\custom_migration\Plugin\migrate\source;


use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides table destination plugin.
 *
 * Use this plugin for a table not registered with Drupal Schema API.
 *
 * @MigrateSource(
 *   id = "group_notification"
 * )
 */
class GroupNotification extends SqlBase {
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state);
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
    $query = $this->select('notifications', 'n');
    $query->join('notifications_fields','nf','n.sid = nf.sid');
    $query->condition('nf.field','group');
    $query->distinct();
    $query->fields('n',['send_interval','uid']);
    $query->fields('nf',['value']);
    //$query->range(0,5);
    return $query;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return ['uid'=>['type'=>'integer']];
  }
  
  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'uid' => $this->t('User ID'),
      'send_interval' => $this->t('send_interval'),
      'value' => 'value',
    ];
    return $fields;
  }
  
  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Perform extra pre-processing for keywords terms, if needed.
    return parent::prepareRow($row);
  }
}