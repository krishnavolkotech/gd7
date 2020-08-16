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
    $content_type = $node->getType();

    if ($content_type) {
      $groupId = \Drupal\cust_group\Controller\CustNodeController::getNodeGroupId($node);
      $group_name = '';
      if ($groupId) {
        $gid = $groupId->getGroup()->id();
        $group = \Drupal\group\Entity\Group::load($gid);
        $group_name = $group->label();
        
        $field_value = $content_type .':'.$group_name; 
        $fields = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), NULL, 'node_group_content_type');
        foreach ($fields as $field) {
          $field->addValue($field_value);
        }
        
      }// End of Group IF
      
    } //End of ContentType If
    
  }
  
}