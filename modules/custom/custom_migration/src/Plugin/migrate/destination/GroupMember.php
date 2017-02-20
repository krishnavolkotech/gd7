<?php

namespace Drupal\custom_migration\Plugin\migrate\destination;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\group\Entity\Group;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Row;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides table destination plugin.
 *
 * Use this plugin for a table not registered with Drupal Schema API.
 *
 * @MigrateDestination(
 *   id = "group_member"
 * )
 */
class GroupMember extends DestinationBase implements ContainerFactoryPluginInterface {
  
  /**
   * GroupMember constructor.
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param MigrationInterface $migration
   * @param Connection $connection
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
  }
  
  
  /**
   * @param ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param MigrationInterface|NULL $migration
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $db_key = !empty($configuration['database_key']) ? $configuration['database_key'] : NULL;
    
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      Database::getConnection('default', $db_key)
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [];
  }
  
  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [];
  }
  
  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $values = $row->getDestination();
    $group = Group::load($values['gid']);
    $groupTypeId = $group->getGroupType()->id();
    if ($values['is_admin'] == 1) {
      $group->addMember(User::load($values['uid']), ['group_roles' => $groupTypeId . '-admin']);
    } else {
      $group->addMember(User::load($values['uid']));
    }
    return true;
    
  }
  
  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    
  }
}
