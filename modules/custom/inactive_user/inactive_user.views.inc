<?php

/**
 * @file
 * Provide views data for dblog.module.
 */

/**
 * Implements hook_views_data().
 */
function inactive_user_views_data() {
  $data = array();
  
  $data['cust_profile'] = array();
  
  $data['cust_profile']['table']['group'] = t('Custom user profile');
  
  $data['cust_profile']['table']['base'] = array(
    'field' => 'id',
    'title' => t('Custom user profile'),
    'weight' => -10,
  );
  
  $data['cust_profile']['table']['join'] = array(
    'users_field_data' => array(
      'left_field' => 'uid',
      'field' => 'uid',
    ),
    'states' => array(
      'left_field' => 'id',
      'field' => 'state_id',
    ),
    'group_content_field_data' => array(
      'left_field' => 'entity_id',
      'field' => 'uid',
    ),
  );
  
  /*$data['cust_profile']['uid'] = array(
    'title' => t('Uid'),
    'help' => t('The custom user profile  ID'),
    'relationship' => array(
          // Views name of the table to join to for the relationship.
      'base' => 'users_field_data',
          // Database field name in the other table to join on.
      'base field' => 'uid',
          // ID of relationship handler plugin to use.
      'id' => 'standard',
          // Default label for relationship in the UI.
      'label' => t(' user profile uid'),
    ),
  );*/
  
  $data['cust_profile']['uid'] = array(
    'title' => t('Uid'),
    'help' => t('The custom user profile  ID'),
    'relationship' => array(
      // Views name of the table to join to for the relationship.
      'base' => 'users_field_data',
      // Database field name in the other table to join on.
      'base field' => 'uid',
      // ID of relationship handler plugin to use.
      'id' => 'standard',
      // Default label for relationship in the UI.
      'label' => t(' user profile uid'),
    ),
  );
  
  $data['cust_profile']['firstname'] = array(
    'title' => t('Firstname'),
    'help' => t('Firstname.'),
    
    'field' => array(
      // ID of field handler plugin to use.
      'id' => 'standard',
    ),
    
    'sort' => array(
      // ID of sort handler plugin to use.
      'id' => 'standard',
    ),
    
    'filter' => array(
      // ID of filter handler plugin to use.
      'id' => 'string',
    ),
    
    'argument' => array(
      // ID of argument handler plugin to use.
      'id' => 'string',
    ),
  );
  
  $data['cust_profile']['lastname'] = array(
    'title' => t('Lastname'),
    'help' => t('Lastname'),
    
    'field' => array(
      // ID of field handler plugin to use.
      'id' => 'standard',
    ),
    
    'sort' => array(
      // ID of sort handler plugin to use.
      'id' => 'standard',
    ),
    
    'filter' => array(
      // ID of filter handler plugin to use.
      'id' => 'string',
    ),
    
    'argument' => array(
      // ID of argument handler plugin to use.
      'id' => 'string',
    ),
  );
  
  $data['cust_profile']['phone'] = array(
    'title' => t('Phone'),
    'help' => t('Phone'),
    
    'field' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );
  
  // Integer timestamp field, exposed as a field, sort, and filter.
  $data['cust_profile']['position'] = array(
    'title' => t('Position'),
    'help' => t('Position'),
    'field' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );
  
  // Integer timestamp field, exposed as a field, sort, and filter.
  $data['cust_profile']['state_id'] = array(
    'title' => t('State id'),
    'help' => t('State id'),
    
    'relationship' => array(
      // Views name of the table to join to for the relationship.
      'base' => 'states',
      // Database field name in the other table to join on.
      'base field' => 'id',
      // ID of relationship handler plugin to use.
      'id' => 'standard',
      // Default label for relationship in the UI.
      'label' => t(' State id'),
    ),
  );
  
  /**
   *   * 'field' => array(
   * 'handler' => 'views_handler_field_numeric',
   * 'click sortable' => TRUE,
   * ),
   * 'filter' => array(
   * 'handler' => 'views_handler_filter_numeric',
   * ),
   * 'sort' => array(
   * 'handler' => 'views_handler_sort',
   * ),
   */
  
  $data['states']['table']['group'] = t('States');
  
  $data['states']['table']['base'] = array(
    'field' => 'id',
    'title' => t('State id'),
    'weight' => -10,
  );
  
  $data['states']['abbr'] = array(
    'title' => t('State abbr'),
    'help' => t('State abbr'),
    
    'field' => array(
      // ID of field handler plugin to use.
      'id' => 'standard',
    ),
    
    'sort' => array(
      // ID of sort handler plugin to use.
      'id' => 'standard',
    ),
    
    'filter' => array(
      // ID of filter handler plugin to use.
      'id' => 'string',
    ),
    
    'argument' => array(
      // ID of argument handler plugin to use.
      'id' => 'string',
    ),
  );
  
  $data['states']['state'] = array(
    'title' => t('State name'),
    'help' => t('State name'),
    
    'field' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );
  
  $data['states']['active'] = array(
    'title' => t('active'),
    'help' => t('State active'),
    
    'field' => array(
      // ID of field handler plugin to use.
      'id' => 'boolean',
    ),
    
    'sort' => array(
      // ID of sort handler plugin to use.
      'id' => 'standard',
    ),
    
    'filter' => array(
      // ID of filter handler plugin to use.
      'id' => 'boolean',
      // Override the generic field title, so that the filter uses a different
      // label in the UI.
      'label' => t('Published'),
      // Override the default BooleanOperator filter handler's 'type' setting,
      // to display this as a "Yes/No" filter instead of a "True/False" filter.
      'type' => 'yes-no',
      // Override the default Boolean filter handler's 'use_equal' setting, to
      // make the query use 'boolean_field = 1' instead of 'boolean_field <> 0'.
      'use_equal' => TRUE,
    ),
  );
  
  $data['inactive_users']['table']['group'] = t('Inactive User');
  
  $data['inactive_users']['table']['base'] = array(
    'field' => 'uid',
    'title' => t('Inactive user'),
    'weight' => -9,
  );
  $data['inactive_users']['table']['join'] = array(
    'users_field_data' => array(
      'left_field' => 'uid',
      'field' => 'uid',
    ),
  );
  $data['inactive_users']['uid'] = array(
    'title' => t('Inactive user id'),
    'help' => t('The Inactive user ID'),
    'relationship' => array(
      // Views name of the table to join to for the relationship.
      'base' => 'users_field_data',
      // Database field name in the other table to join on.
      'base field' => 'uid',
      // ID of relationship handler plugin to use.
      'id' => 'standard',
      // Default label for relationship in the UI.
      'label' => t(' Inactive user'),
    ),
    'field' => array(
      // ID of field handler plugin to use.
      'id' => 'boolean',
      'label'=>'Inactive Status'
    ),
  
    'sort' => array(
      // ID of sort handler plugin to use.
      'id' => 'standard',
    ),
  
    'filter' => array(
      // ID of filter handler plugin to use.
      'id' => 'is_null',
      // Override the generic field title, so that the filter uses a different
      // label in the UI.
      'label' => t('Active'),
      // Override the default BooleanOperator filter handler's 'type' setting,
      // to display this as a "Yes/No" filter instead of a "True/False" filter.
      'type' => 'true-false',
      // Override the default Boolean filter handler's 'use_equal' setting, to
      // make the query use 'boolean_field = 1' instead of 'boolean_field <> 0'.
//      'use_equal' => TRUE,
//      'allow_empty' => TRUE,
//      'accept null'=>TRUE,
    ),
  );
  
  return $data;
  
}
