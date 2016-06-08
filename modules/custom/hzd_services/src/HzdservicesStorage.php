<?php

namespace Drupal\hzd_services; 

// use Drupal\node\Entity\Node;

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
  
}

 
//}
