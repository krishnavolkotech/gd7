<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Plugin\migrate\cckfield\NodeReference.
 */

namespace Drupal\Core\Field\Plugin\migrate\cckfield;

use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Plugin\Migration;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Row;

/**
 * @MigrateCckField(
 *   id = "nodereference",
 *   type_map = {
 *     "nodereference" = "entity_reference"
 *   }
 * )
 */
class NodeReference extends ReferenceBase {

  /**
   * @var string
   */
  protected $nodeTypeMigration = 'd6_node_type';

  /**
   * {@inheritdoc}
   */
  protected function entityId() {
    return 'nid';
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'nodereference_select' => 'options_select',
      'nodereference_buttons' => 'options_buttons',
      'nodereference_autocomplete' => 'entity_reference_autocomplete'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function transformFieldStorageSettings(Row $row) {
    $settings['target_type'] = 'node';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function processFieldInstance(MigrationInterface $migration) {
    parent::processFieldInstance($migration);

    $migration_dependencies = $migration->get('migration_dependencies');
    $migration_dependencies['required'][] = $this->nodeTypeMigration;
    $migration->set('migration_dependencies', $migration_dependencies);
  }

  /**
   * {@inheritdoc}
   */
  public function transformFieldInstanceSettings(Row $row) {
    $source_settings = $row->getSourceProperty('global_settings');
    $settings['handler'] = 'default:node';
    $settings['handler_settings']['target_bundles'] = [];

    $node_types = array_filter($source_settings['referenceable_types']);
    if (!empty($node_types)) {
      $settings['handler_settings']['target_bundles'] = $this->migrateNodeTypes($node_types);
    }
    return $settings;
  }

  /**
   * Look up migrated node types from the d6_node_type migration.
   *
   * @param $source_node_types
   *   The source node types.
   *
   * @return array
   *   The migrated node types.
   */
  protected function migrateNodeTypes($source_node_types) {
    // Configure the migration process plugin to look up migrated IDs from
    // the d6_node_type migration.
    $migration_plugin_configuration = [
      'migration' => $this->nodeTypeMigration,
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

    $node_types = [];
    foreach ($source_node_types as $node_type) {
      $node_types[] = $migrationProcessPlugin->transform($node_type, $executable, $row, NULL);
    }
    return array_combine($node_types, $node_types);
  }

}
