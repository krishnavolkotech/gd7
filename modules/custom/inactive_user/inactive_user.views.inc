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
  );

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

    'field' => array(
      // ID of field handler plugin to use.
      'id' => 'numeric',
    ),

    'sort' => array(
      // ID of sort handler plugin to use.
      'id' => 'numeric',
    ),

    'filter' => array(
      // ID of filter handler plugin to use.
      'id' => 'numeric',
    ),
  );



 /**

   'field' => array(
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
     ),
    'filter' => array(
      'handler' => 'views_handler_filter_numeric',
    ),
    'sort' => array(
      'handler' => 'views_handler_sort',
    ),

*/

  return $data;

}
