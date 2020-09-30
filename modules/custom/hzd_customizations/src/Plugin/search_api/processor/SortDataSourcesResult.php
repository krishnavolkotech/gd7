<?php

namespace Drupal\hzd_customizations\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds the  Content-Type and Group as item to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "sort_datasources_result",
 *   label = @Translation("Sort datasources by entity changed date"),
 *   description = @Translation("Combines Node,Group,Comment changed date as new item to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */

class SortDataSourcesResult extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
  */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Sort datasources by entity changed date'),
        'description' => $this->t('Combines Node,Group,Comment changed date as new item to the indexed data.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['sort_datasources_result'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $entity = $item->getOriginalObject()->getValue();

    if(method_exists($entity, 'hasField') && $entity->hasField('changed')){
      $fields = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), NULL, 'sort_datasources_result');
      foreach ($fields as $field) {
        if(method_exists($entity, 'getEntity')){
          $field->addValue($entity->getEntity()->getChangedTime());
        }
        else {
          $field->addValue($entity->getChangedTime());
        }
      }
    }
  }
}
