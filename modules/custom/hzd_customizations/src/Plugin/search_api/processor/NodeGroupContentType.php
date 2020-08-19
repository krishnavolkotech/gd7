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
 *   id = "node_group_content_type",
 *   label = @Translation("Node Content Type Group "),
 *   description = @Translation("Combines Node ContentType and Group as new item to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */

class NodeGroupContentType extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
  */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Node Group Content Type'),
        'description' => $this->t('Node Content type + Group Name Item'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['node_group_content_type'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $node = $item->getOriginalObject()->getValue();
    $node_type = $node->getEntityTypeId();

    if ($node_type == 'group_content') {
      $rel = \Drupal\group\Entity\GroupContent::load($node->id());
      $group_name = $rel->getGroup()->label();
      $content_type = $rel->getEntity()->getType();        
      $field_value = $content_type .':'.$group_name; 
      $fields = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), NULL, 'node_group_content_type');
      foreach ($fields as $field) {
        $field->addValue($field_value);
      }
    } //End of ContentType If
    
  }
}
