<?php

namespace Drupal\hzd_services; 
use Drupal\Core\Url;

class HzdservicesStorage { 

  //function which returns the services array
  static function get_related_services($type = NULL) {
    $service_names = array();
    switch ($type) {
    case 'problems':
     $query = db_select('node_field_data', 'n');
	                  $query->join('node__field_problem_name', 'nfpn', 'nfpn.entity_id = n.nid');
	                  $query->Fields('n', array('nid'));
	                // ->Fields('nfpn', array('field_problem_name_value as service'))
                   $query->addField('nfpn', 'field_problem_name_value' , 'service');
	                 $query->condition('nfpn.field_problem_name_value', NULL,'IS NOT NULL');
		             	 $query->orderBy('service');
                   // $this->condition($field, $function, NULL, 'IS NOT NULL', $langcode);
                   break;
   case 'releases':
     $query = db_select('node', 'n');
                   $query->Fields('n', array('nid'));
                   $query->join('node__field_release_name', 'nfrn', 'nfrn.entity_id = n.nid');
                   $query->addField('nfrn', 'field_release_name_value' , 'service');
	                // $query->condition('nfrn.field_release_name_value',NULL, 'IS NOT NULL');
                   $query->isNotNull('nfrn.field_release_name_value');
			             $query->orderBy('service');
                   break;

   case 'downtimes' :
     $query = db_select('node_field_data', 'n');
	                 $query->join('node__field_enable_downtime', 'nfed', 'nfed.entity_id = n.nid');
	                 $query->Fields('n', array('nid'));
                   $query->addField('n', 'title' , 'service');
	                 $query->condition('nfed.field_enable_downtime_value', 1 , '=');
                   $query->orderBy('service');
                  break;

    case 'service_profile' :
      $query = db_select('node_field_data', 'n');
                       $query->join('node__field_service_type', 'nfst', 'nfst.entity_id = n.nid');
                       $query->Fields('n', array('nid'));
                       $query->addField('nfrn', 'title' , 'service');
                       $query->condition('nfed.field_service_type_value', 'Publish' , '=');
                       $query->condition('n.nid', 'nfed.nid', '=');
                       $query->orderBy('service');
      break;

   }
   $result = $query->execute()->fetchAll();
  // echo '<pre>'; print_r($result);  exit;
   foreach($result as $services) {
   //while ($services = $query->execute()->fetchAssoc()) {
     $service_names[$services->nid]  = trim($services->service);
   }
//   echo "<pre>";  print_r($service_names); exit;
   return $service_names;
 }

 
 function get_default_services_current_session() {
  //Getting the default Services
  // $group_id = \Drupal::service('user.private_tempstore')->get()->get('Group_id');
  $group_id = $_SESSION['Group_id'];
  
  $query = db_select('group_problems_view', 'gpv');
  $query->Fields('gpv', array('service_id'));
  $query->conditions('group_id', $group_id, '=');

  $result = $query->execute()->fetchAssoc();
  return $result['service_id'];
  }
  
  function get_downtimes_default_services() {
  //Getting the default Services
  // $group_id = \Drupal::service('user.private_tempstore')->get()->get('Group_id');
  $group_id = $_SESSION['Group_id'];
  $query = db_select('group_downtimes_view', 'gdv');
  $query->Fields('gdv', array('service_id'));
  $query->conditions('group_id', $group_id, '=');

  $result = $query->execute()->fetchAssoc();
  return $result['service_id'];
  }

  // display service info in manage services page
  function service_info() {
    $options['query']['destinations'] = 'manage_services';
    $url = Url::fromUserInput('/node/add/services', $options);
    $link = array('#title' => array('#markup' => t('Create Services')), '#type' => 'link', '#url' => $url);
    $create_service = \Drupal::service('renderer')->renderRoot($link);
    $output = "<div class = 'create_service'>" . $create_service . "</div>";
    $build['#markup'] = $output;
    return $build;
  }

  // display list of all services
  function service_list() {
    $header = array(
		  0 => array('data' => t('Service Name'), 'class' => 'service_name'),
		  1 => array('data' => t('Name in problem database'), 'class' => 'problem_name'),
		  2 => array('data' => t('Name in release database'), 'class' => 'release_name'),
		  3 => array('data' => t('Enable for downtimes'), 'class' => 'downtime_enable'),
		  4 => array('data' => t('Action'), 'class' => 'downtime_action'),
		);
		$services_query = self::services_query();
		$loader_path = drupal_get_path('module', 'hzd_services') . '/images/status-active.gif';
		foreach($services_query as $service_info) {
		  /*$value = TRUE; 
      if(!$service_info->field_enable_downtime_value) {
        $value = FALSE;
      }*/
      $form['downtime_checkbox'] = array(
        '#type' => 'checkbox', 
				'#attributes' => array('node_id' => $service_info->nid, 'class' => 'enable_downtimes'),
				'#value' => $service_info->field_enable_downtime_value,
				'#prefix' => "<div class = 'downtime_enable'><div class = 'downtime_check_form'>",
				'#suffix' => "<div style = 'display:none' class = 'loader " . $service_info->nid . "'><img src =" . $loader_path . "  / ><div></div></div>",
      );
      $downtimes = \Drupal::service('renderer')->render($form['downtime_checkbox']);
      $downtime_checkbox = array('#title' => array('#markup' => $downtime_enable));
		  $url = Url::fromUserInput('/node/' . $service_info->nid . '/edit');
      $link = array('#title' => array('#markup' => t('Edit')), '#type' => 'link', '#url' => $url);
      $edit_link = \Drupal::service('renderer')->renderRoot($link);
		  $elements = array (
        'service_name' => $service_info->title,
        'problem_name' => $service_info->field_problem_name_value,
        'import_status' => $service_info->field_release_name_value,
        'downtime' => $downtimes,
        'action' => $edit_link,
      );
      $rows[] = $elements;
		}
    $output['services-list'] = array(
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
      '#attributes' => ['id' => "sortable", 'class' =>"tablesorter"],
      '#empty' => t('No records found'),
    );
    return $output;
  }

  function services_query() {
    $query = db_select('node_field_data', 'n');
    $query->leftJoin('node__field_release_name', 'nfrn', 'n.nid = nfrn.entity_id');
    $query->leftJoin('node__field_problem_name', 'nfpn', 'n.nid = nfpn.entity_id');
    $query->leftJoin('node__field_enable_downtime', 'nfed', 'n.nid = nfed.entity_id');
    $query->condition('n.type', 'services', '=')
          ->fields('n', array('nid', 'title'))
          ->fields('nfrn', array('field_release_name_value'))
          ->fields('nfpn', array('field_problem_name_value'))
          ->fields('nfed', array('field_enable_downtime_value'))
          ->orderBy('title');
    $result = $query->execute()->fetchAll();
    return $result;
  }

  function default_services_insert($node) {
    $query = "SELECT nid FROM {node_field_data} WHERE type = :type AND title like :title";

    $release_management_group_id = db_query($query, array(':type' => 'group', ':title' => 'release management'))->fetchField();
    if($release_management_group_id) {
      $table = 'group_releases_view';
      self::service_update($node->nid, $release_management_group_id, $node->field_release_name->value, $table);
    }

    $problem_management_group_id = db_query($sql, array(':type' => 'group', ':title' => 'problem management'))->fetchField();
    if($problem_management_group_id) {
      $table = 'group_problems_view';
      self::service_update($node->nid, $problem_management_group_id, $node->field_problem_name->value, $table);
    }

    $incident_management_group_id = db_query($sql, array(':type' => 'group', ':title' => 'incident management'))->fetchField();
    if($incident_management_group_id) {
      $table = 'group_downtimes_view';
      self::service_update($node->nid, $incident_management_group_id, $node->field_enable_downtime->value, $table);
    }
  }

  function service_update($node_id, $group_id, $service_name, $table) {
    $count = db_query("SELECT count(*) as count FROM {" . $table . "} WHERE group_id = :gid AND service_id = :sid", array(":gid" => $group_id, ":sid" => $node_id))->fetchField();

    if(!$count && $service_name) {
      $service_record = array('group_id' => $group_id, 'service_id' => $node_id);
      db_insert($table)->fields($service_record)->execute();
    }
    else if ($count && !$service_name) {
      db_delete($table)->condition('group_id', $group_id, '=')->condition('service_id', $node_id, '=')->execute();
    }
  }

  // written batch process for updating downtime services
  function update_downtime_notifications($node, $rel_type) {
    $batch = array(
      'operations' => array(),
      'title' => t('Updating Downtimes Notifications'),
      'init_message' => t('Updating Downtimes Notifications...'),
      'progress_message' => t('Processed @current out of @total.'),
      'error_message' => t('An error occurred during processing'),
    );

    if ($node->field_enable_downtime->value) {
      //hzd_notifications_delete_subscriptions(array('type' => 'service'), array('service' => $node->nid, 'type' => 'downtimes'));
      //$batch['operations'][] = array('notifications_insert', array($node->nid, "downtimes", $rel_type));
      $batch['operations'][] = array('notifications_insert', array($node->nid->value, "downtimes", $rel_type));
    }
    elseif ($node->field_enable_downtime->value == '') {
      //hzd_notifications_delete_subscriptions(array('type' => 'service'), array('service' => $node->nid, 'type' => 'downtimes'));
    }
    $url = array('manage_services');
    batch_set($batch);
    return batch_process($url);
  }

}

 
//}
