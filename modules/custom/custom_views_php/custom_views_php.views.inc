<?php

/**
 * @file
 * Contains custom_views_php\custom_views_php.views.inc..
 * Provide a custom views field data that isn't tied to any other module. */

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Render\Markup;
use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\system\ActionConfigEntityInterface;

/**
* Implements hook_views_data().
*/
function custom_views_php_views_data() {

    $data['views']['table']['group'] = t('Custom Global');
    $data['views']['table']['join'] = array(
      // #global is a special flag which allows a table to appear all the time.
      '#global' => array(),
    );

    $data['views']['group_post_count_field'] = array(
        'title' => t('Group Post Count'),
        'help' => t('Enable to include and Group Post Count code in your modules as part of a view'),
        'field' => array(
            'id' => 'group_post_count_field',
        ),
        'sort' => array(
            // ID of sort handler plugin to use.
            'id' => 'group_post_count_field',
        ),

    );

    $data['views']['group_member_count_field'] = array(
        'title' => t('Group Member Count'),
        'help' => t('Enable to include and Group Member Count code in your modules as part of a view'),
        'field' => array(
            'id' => 'group_member_count_field',
        ),
        'sort' => array(
            // ID of sort handler plugin to use.
            'id' => 'group_member_count_field',
        ),
    );

    $data['views']['group_type_value'] = array(
        'title' => t('Group Type Value'),
        'help' => t('Enable to include and GroupType code in your modules as part of a view'),
        'field' => array(
            'id' => 'group_type_value',
        ),
        'sort' => array(
            // ID of sort handler plugin to use.
            'id' => 'group_type_value',
        ),
    );
    
    $data['views']['group_actions_field'] = array(
        'title' => t('Group Actions'),
        'help' => t('Enable to include and Group Actions link in your modules as part of a view'),
        'field' => array(
            'id' => 'group_actions_field',
        ),
        'sort' => array(
            // ID of sort handler plugin to use.
            'id' => 'group_actions_field',
        ),
    );
    
    $data['views']['group_member_name'] = array(
        'title' => t('Group Members'),
        'help' => t('Enable to include and Group members role with name as part of a view'),
        'field' => array(
            'id' => 'group_member_name',
        ),
        'sort' => array(
            // ID of sort handler plugin to use.
            'id' => 'group_member_name',
        ),
    );


    return $data;
}
