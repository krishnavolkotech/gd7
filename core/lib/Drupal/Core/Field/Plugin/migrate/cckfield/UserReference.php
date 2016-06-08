<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\migrate\cckfield\UserReference.
 */

namespace Drupal\Core\Field\Plugin\migrate\cckfield;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigratePluginManager;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @MigrateCckField(
 *   id = "userreference",
 *   type_map = {
 *     "userreference" = "entity_reference"
 *   }
 * )
 */
class UserReference extends ReferenceBase {

  /**
   * @var string
   */
  protected $userRoleMigration = 'd6_user_role';

  /**
   * {@inheritdoc}
   */
  protected function entityId() {
    return 'uid';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'userreference_select' => 'options_select',
      'userreference_buttons' => 'options_buttons',
      'userreference_autocomplete' => 'entity_reference_autocomplete'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function transformFieldStorageSettings(Row $row) {
    $settings['target_type'] = 'user';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function processFieldInstance(MigrationInterface $migration) {
    parent::processFieldInstance($migration);

    $migration_dependencies = $migration->get('migration_dependencies');
    $migration_dependencies['required'][] = $this->userRoleMigration;
    $migration->set('migration_dependencies', $migration_dependencies);
  }

  /**
   * {@inheritdoc}
   */
  public function transformFieldInstanceSettings(Row $row) {
    $source_settings = $row->getSourceProperty('global_settings');
    $settings['handler'] = 'default:user';
    $settings['handler_settings']['include_anonymous'] = FALSE;
    $settings['handler_settings']['filter']['type'] = '_none';
    $settings['handler_settings']['target_bundles'] = NULL;

    $roles = array_filter($source_settings['referenceable_roles']);
    if (!empty($roles)) {
      $settings['handler_settings']['filter']['type'] = 'role';
      $settings['handler_settings']['filter']['role'] = $this->migrateUserRoles($roles);
    }

    return $settings;
  }

  /**
   * Look up migrated role IDs from the d6_user_role migration.
   *
   * @param $source_roles
   *   The source role IDs.
   *
   * @return array
   *   The migrated role IDs.
   */
  protected function migrateUserRoles($source_roles) {
    // Configure the migration process plugin to look up migrated IDs from
    // the d6_user_role migration.
    $migration_plugin_configuration = [
      'migration' => $this->userRoleMigration,
    ];

    $row = new Row([], []);

    /**
     * @var MigrationInterface $migration
     */
    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration([]);

    /**
     * @var MigrateProcessInterface $migrationProcessPlugin
     */
    $migrationProcessPlugin = $this->migratePluginManager
      ->createInstance('migration', $migration_plugin_configuration, $migration);

    $executable = new MigrateExecutable($migration, new MigrateMessage());

    $roles = [];
    foreach ($source_roles as $role) {
      $roles[] = $migrationProcessPlugin->transform($role, $executable, $row, NULL);
    }
    return array_combine($roles, $roles);
  }

}
