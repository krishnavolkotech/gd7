<?php

namespace Drupal\problem_management; 

use Drupal\node\Entity\Node;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\Core\Database\Query\Condition;

class HzdStorage { 
  
  protected $tempStore;
  // Pass the dependency to the object constructor

  const DISPLAY_LIMIT = 20;
  const PROBLEM_MANAGEMENT = 825;


  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    // For "mymodule_name," any unique namespace will do
    $this->tempStore = $temp_store_factory->get('problem_management');
  }
  
  /*
   *Inserts the status of the import file on cron run
   */
  static function insert_import_status($status, $msg) {
   // Populate the node access table.
    db_insert('problem_import_history')
    ->fields(array(
      'problem_date' => time(),
      'import_status' => $status,
      'error_message' => $msg
    ))
    ->execute();
    // $sql = "insert into {problem_import_history} (problem_date, import_status, error_message) values (%d, '%s', '%s') "; 
    // db_query($sql, time(), $status, $msg);
  }


/*
 *function for saving problem node
 */
static function saving_problem_node($values) {
  $query = db_select('node_field_data', 'n');
  $query->Fields('n', array('nid'));
  $query->condition('type', 'group', '=');
  $query->condition('title', 'problem management', '='); 
  $problem_management_group_id = $query->execute()->fetchCol();

  $query = db_select('node_field_data', 'n');
  $query->join('node__field_s_no', 'nfsn', 'n.nid = nfsn.entity_id');
  $query->Fields('n', array('nid', 'vid', 'created'));
  $query->condition('field_s_no_value', $values['sno'], '=');
  $node_infos = $query->execute()->fetchAll();

 foreach ($node_infos as $node_info) {
    $nid = $node_info->nid;
    $vid = $node_info->vid;
    $created = $node_info->created;
  }

  //the erofnet date field conversion   
  $replace = array('/' => '.', '-' => '.');
  $formatted_date = strtr($values['eroffnet'], $replace);

  $date_time = explode(" ", $formatted_date);
  $date_format = explode(".", $date_time[0]);
  $time_format = explode(":", $date_time[1]);
    
  if($date_format[1] && $date_format[0] &&$date_format[2]) {
    $date = mktime((int)$time_format[0],(int)$time_format[1],(int)$time_format[2],(int)$date_format[1],(int)$date_format[0],(int)$date_format[2]);
  }
  $eroffnet = ($date?$date:time());

  // Generate notifications for updated problems.
  if($nid) {
    unset($values['sno']);
    $exist_node = node_load($nid);
    $existing_node_vals = array();

    $existing_node_vals['status'] = $exist_node->field_problem_status->value;
    $existing_node_vals['service'] = $exist_node->field_services->target_id;
    $existing_node_vals['function'] = $exist_node->field_function->value;
    $existing_node_vals['release'] = $exist_node->field_release->value;
    $existing_node_vals['title'] = $exist_node->getTitle();
    $existing_node_vals['body'] = $exist_node->body->value;
    $existing_node_vals['diagnose'] = $exist_node->field_diagnose->value;
    $existing_node_vals['solution'] = $exist_node->field_solution->value;
    $existing_node_vals['workaround'] = $exist_node->field_work_around->value;
    $existing_node_vals['version'] = $exist_node->field_version->value;
    $existing_node_vals['priority'] = $exist_node->field_priority->value;
    $existing_node_vals['taskforce'] = $exist_node->field_task_force->value;
    $existing_node_vals['comment'] = $exist_node->field_comments->value;
    $existing_node_vals['processing'] = $exist_node->field_processing->value;
    $existing_node_vals['attachment'] = $exist_node->field_attachment->value;
    $existing_node_vals['eroffnet'] = $exist_node->field_eroffnet->value;
    $existing_node_vals['timezone'] = 'Europe/Berlin';
    
    if(count(array_diff($existing_node_vals, $values)) != 0) {
      $node_array['status'] = 1;
    } 
   
  } 
  else {
    $node_array = array(
      'nid' => ($nid ? $nid : ''), 
      'vid' => ($vid ? $vid : ''), 
      'uid' => 1,
      'created' => ($created ? $created : time()),
      'type' => 'problem',
      'title' => $values['title'],
      'body' => $values['body'],
      'revision' => 0,
      'op' => 'Save',
      'submit' => 'Save',
      'preview' => 'Preview',
      'form_id' => 'problem_node_form',
      'comment' => 2,
      'field_problem_status' => Array(
        '0' => Array(
          'value' => $values['status'],
          )
        ),
      'field_services' => Array(
        '0' => Array(
          'nid' => $values['service'],
          )
        ),
      'field_s_no' => Array(
        '0' => Array(
          'value' => $values['sno'],
          )
        ),
      'field_function' => Array(
        '0' => Array(
          'value' => $values['function'],
          )
        ),
      'field_release' => Array(
        '0' => Array(
          'value' => $values['release'],
          )
        ),
      'field_closed' => Array(
        '0' => Array(
          'value' => $values['closed'],
          )
        ),
      'field_diagnose' => Array(
        '0' => Array(
          'value' => $values['diagnose'],
          )
        ),
      'field_solution' => Array(
        '0' => Array(
         'value' => $values['solution'],
         )
        ),
      'field_work_around' => Array(
        '0' => Array(
          'value' => $values['workaround'],
          )
        ),
      'field_priority' => Array(
        '0' => Array(
          'value' => $values['priority'],
          )
        ),
      'field_eroffnet' => Array(
        '0' => Array(
          'value' => $values['eroffnet'],
          )
        ),
      'field_problem_eroffnet' => Array(
        '0' => Array(
          'value' => $eroffnet,
          )
       ),
      'field_version' => Array(
        '0' => Array(
          'value' => $values['version'],
          )
        ),

      'field_task_force' => Array(
        '0' => Array(
          'value' => $values['taskforce'],
          )
        ),
      'field_comments' => Array(
        '0' => Array(
          'value' => $values['comment'],
          )
        ),

      'field_processing' => Array(
        '0' => Array(
          'value' => $values['processing'],
          )
        ),

      'field_attachment' => Array(
        '0' => Array(
          'value' => $values['attachment'],
          )
        ),

      'field_ticketstore_link' => Array(
        '0' => Array(
          'value' => $values['ticketstore_link'],
          )
        ),

      'field_ticketstore_count' => Array(
        '0' => Array(
          'value' => $values['ticketstore_count'],
          )
        ),

      'og_initial_groups' => Array(
        '0' => $problem_management_group_id,
       ),
      'og_public' => 0,
      'og_groups' => Array(
        $problem_management_group_id => $problem_management_group_id,
      ),
      'notifications_content_disable' => 0,
      'teaser' => '',
      'validated' => 1
      );
    $node = Node::create($node_array);
    $node->save();
    return TRUE;
  }
  return FALSE;
}

  static function insert_group_problems_view($selected_services) {
   // $sql = 'insert into {group_problems_view} (group_id, service_id) values (%d, %d)';
    $counter = 0;
    $query = db_insert('group_problems_view');

    // $tempstore = \Drupal::service('user.private_tempstore')->get('problem_management');
    // $group_id = $tempstore->get('Group_id');
    $group_id = $_SESSION['Group_id'];
    if ($selected_services) {
      foreach ($selected_services as $service) {
        $counter++;
        $query->fields(array(
        'group_id' => $group_id,
        'service_id' => $service
         ))->execute();
       // db_query($sql, $_SESSION['Group_id'], $service);
      }
    }
    return $counter; 
  }

static function import_history_display_table($limit = NULL) {
  $build = array();
  $build['#attached']['library'] = array('problem_management/problem_management');
  
  $query = db_select('problem_import_history','pmh');
  $query->Fields('pmh', array('problem_date', 'import_status', 'error_message'));
  $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender');
  if($limit != 'all') {
    $page_limit = ($limit ? $limit : 20);
    // $where .= " Order By 'id'  'DESC'";
    $table_sort->orderBy('id');
    $pager = $table_sort->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($page_limit);
    $result = $pager->execute();
  } else {
    $result = $query->execute()->fetchAll();
  }

  foreach($result as $row) {
    $elements = array (
      'date' => date('d-m-Y', $row->problem_date),
      'time' => date('H:i', $row->problem_date),
      'import_status' => $row->import_status,
      'error' => $row->error_message,
      );
    $rows[] = $elements;
  }
  
  $header = array(
    '0' => array('data' => t('Date'), 'class' => 'import_date'),
    '1' => array('data' => t('Time'), 'class' => 'time'),
    '2' => array('data' => t('Import Status'), 'class' => 'import_status'),
    '3' => array('data' => t('Error Message'), 'class' => 'error_message'),
  );

  $build['history_table'] = array(
    '#theme' => 'table', 
    '#header' => $header,
    '#rows' => $rows,
    '#empty' => t('No Data Created Yet'),
   // '#id' => 'sortable_new', 
   // '#class' => 'tablesorter',
  );
  $build['pager'] = array(
    '#type' => 'pager'
  );
  // echo '<pre>';  print_r($build); exit;
  return $build; 
}

static function build_ahah_query($form_state) {
  $sql_where = array();

 if ($form_state['values']['service']) {
    $service = $form_state['values']['service'];
    $sql_where[] = array('and' => array(
      'field' => 'nfs.field_services_target_id',
      'value' => $service,
      'operator' => '=',
      )
    );
  }

  if ($form_state['values']['string']) {
    $text = $form_state['values']['string'];
    if ($text != t('Search Title, Description, cause, Workaround, solution')) { 
      $query = db_select('node_field_data', 'nfd');
      $query->Fields('nfd', array('nid'));
      // $or = db_or();
      // $or->condition('field_s_no_value', $text, '=');
      // $or->condition('nfd.title', '%' . $text . '%' , 'like');
      // $or->condition('nrb.body_value', '%' . $text . '%' , 'like'); 
      // $or->condition('field_work_around_value', '%' . $text . '%' , 'like');
      $sql_where[] = array( 'or' => 
        array(
          array(      
            'field' => 'nfsn.field_s_no_value',
            'value' => $text,
            'operator' => '=',
          ), 
          array(
            'field' => 'nfd.title',
            'value' => '%' . $text . '%',
            'operator' => 'like',
          ), 
          array(     
            'field' => 'nrb.body_value',
            'value' => '%' . $text . '%',
            'operator' => 'like',
          ),
          array(     
            'field' => 'nfwa.field_work_around_value',
            'value' => '%' . $text . '%',
            'operator' => 'like',
          ),
        )
      );

      // $query->condition($or);
      // $sql_where .= " and (field_s_no_value = '" . $text . "' or n.title like '%%" . $text . "%' or nr.body like '%%" . $text . "%' or field_work_around_value like '%%" . $text . "%') ";
      // $sql_select = " SELECT n.nid ";

      $query->join('node_revision__body', 'nrb', 'nfd.nid = nrb.entity_id');
      $query->join('node__field_s_no', 'nfsn', 'nrb.entity_id = nfsn.entity_id');
      $query->join('node__field_problem_status', 'nfps', 'nfsn.entity_id = nfps.entity_id');
      $query->join('node__field_services', 'nfs', 'nfps.entity_id = nfs.entity_id');
      $query->join('node__field_function', 'nff', 'nfs.entity_id = nff.entity_id');
      $query->join('node__field_work_around', 'nfwa', 'nff.entity_id = nfwa.entity_id');
      /**
      $from = " FROM  {node_field_data} nfd 
                LEFT JOIN {node_revision__body} nrb ON nfd.nid = nrb.entity_id
                LEFT JOIN {node__field_s_no} nfsn ON nfd.nid = nfsn.entity_id
                LEFT JOIN {node__field_problem_status} nfps ON nfsn.entity_id = nfps.entity_id
                LEFT JOIN {node__field_services} nfs ON nfps.entity_id = nfs.entity_id
                LEFT JOIN {node__field_function} nff ON nfs.entity_id = nff.entity_id
                LEFT JOIN {node__field_work_around} nfwa ON nff.entity_id = nfwa.entity_id";
      $where = " WHERE  cp. field_s_no_value = '" . $text . "' and
                                       field_services_target_id in (SELECT service_id 
                                           FROM group_problems_view 
                                           WHERE group_id = " . ($_SESSION['Group_id']?$_SESSION['Group_id']: self::PROBLEM_MANAGEMENT) . ")";
                                           */
  
      $group_problems_view_service_id_query = db_select('group_problems_view', 'gpv');
      $group_problems_view_service_id_query->Fields('gpv', array('service_id'));
      $group_problems_view_service_id_query->conditions('group_id', $_SESSION['Group_id']?$_SESSION['Group_id']: self::PROBLEM_MANAGEMENT ,'=');
      $group_problems_view_service_id = $group_problems_view_service_id_query->execute()->fetchCol();

      $or = db_or();
      if (!empty($sql_where)) {
        foreach ($sql_where as $where) {
          foreach ($where as $conjuction => $condition) {
            if ($conjuction == 'and') {
              $query->condition($condition['field'], $condition['value'], $condition['operator']);
            } else if ( $conjuction == 'or') {
              foreach ($condition as $conditions) {
                $or->condition($conditions['field'], $conditions['value'], $conditions['operator']);
              } 
              $query->condition($or); 
            }
            // $query->condition($sql_where);
          }
        }
      }
      $query->condition('nfsn.field_s_no_value', $text, '=');
      $query->condition('nfs.field_services_target_id', $group_problems_view_service_id,'IN');
 //     echo '<pre>';  print_r($query); exit;

      $current_path = \Drupal::service('path.current')->getPath();
      $get_uri = explode('/', $current_path);

      if (isset($get_uri['4']) && $get_uri['4'] == 'archived') {
        $url = ( isset($_SESSION['Group_id'])?'node/'.$_SESSION['Group_id'].'/problems/archived_problems':'problems/archived_problems');
        $filter_where = " and nfps.field_problem_status_value = 'geschlossen' ";
        $query->condition('nfps.field_problem_status_value', 'geschlossen', '=');
      }
      else {
        $url = ( isset($_SESSION['Group_id'])?'node/'.$_SESSION['Group_id'].'/problems':'problems');
        // $filter_where = " and nfps.field_problem_status_value <> 'geschlossen' ";
        $query->condition('nfps.field_problem_status_value', 'geschlossen', '<>');
        // db_query("SELECT * FROM {menu_links} ml INNER JOIN {book} b ON b.mlid = ml.mlid LEFT JOIN {menu_router} m ON m.path = ml.router_path WHERE ml.mlid = :mlid", array(':mlid' => $mlid))->fetchAssoc();
      }
     //  $sql = $sql_select . $from . $where . $sql_where . $filter_where;
      $sid = $query->execute()->fetchCol();
    }
  }


  if ($form_state['values']['function']) {
    $function = trim($form_state['values']['function']);
   // $sql_where .= " and field_function_value = '" .$function . "'";
   // $query->condition('field_function_value', $function, '=');
    $sql_where[] = array( 'and' => 
      array(
        'field' => 'nff.field_function_value',
        'value' => $function,
        'operator' => '=',
      ) 
    );
  }
  
  if ($form_state['values']['release']) {
    $release = trim($form_state['values']['release']);
   // $sql_where .= " and nfr.field_release_value = '" .$release . "'";
   // $query->condition('field_release_value', $release, '=');
      $sql_where[] = array(
        'and' => array(
          'field' => 'nfr.field_release_value',
          'value' => $release,
          'operator' => '=',
        )
      );
  }
  
  if ($form_state['values']['limit']) {
    $limit = $form_state['values']['limit'];
  }

  if ($sid) {
      $params = array(
			  'url' => $url,
			  );
       $params_seralized = serialize($params);
       $_SESSION['params_seralized'] = $params_seralized;
  }
//  echo '<pre>';  print_r($query);  exit;
  if ($sid) {
    return array("sid" => $sid,"query" => $sql_where);
  }
  else {
    return array("query" => $sql_where);
  }
}


static function ahah_problems_display($form, $form_state, $sql_where = NULL, $string = NULL, $limit = NULL) {
  $form_state->setValue('submitted', 'FALSE');
  $form_build_id = $_POST['form_build_id'];
  FormCache::getCache($form_build_id, $form_state); 
  if ($_POST) {
    $service = $request->request->get('service'); 
  }

  //Geting functions and release data
  $default_function_releases = self::get_functions_release($string, $service);

  $form['function']['#options'] = ($default_function_releases['functions']?$default_function_releases['functions'] : $default_services[] = t("Select Service"));
  $form['function']['#options'] = $default_function_releases['functions'];
  $form['release']['#options'] = $default_function_releases['releases'];
 
  FormCache::setCache($form_build_id, $form, $form_state);
  $_SESSION['sql_where'] = $sql_where;
  $_SESSION['limit'] = $limit;

  // $output .= drupal_get_form('problems_filter_form', $string, $options);
  // $output .=  "<div class = 'reset_form'>" . drupal_render(problem_reset_element()). "</div>";
  // $output .= '<div style = "clear:both"></div>';
  // $output .= problems_default_display($sql_where, $string, $limit);

  $result['content']['#prefix'] = "<div id = 'problem_search_results_wrapper'>" ;
  $result['content']['problems_filter_element'] = \Drupal::formBuilder()->getForm('Drupal\problem_management\Form\ProblemFilterFrom', $string);
  $result['content']['problems_reset_element']['#prefix'] = "<div class = 'reset_form'>";
  $result['content']['problems_reset_element']['form'] = HzdproblemmanagementHelper::problem_reset_element();
  $result['content']['problems_reset_element']['#suffix'] = '</div><div style = "clear:both"></div>';
  $result['content']['problems_default_display'] = HzdStorage::problems_default_display($sql_where, $string, $limit);
  $result['content']['#suffix'] = "</div>";
  return $result;
}


/*
 *Function for populating the Functions and releses option values
*/
static  function get_functions_release($string = NULL, $service = NULL) {
 /**
  $sql = "SELECT field_function_value as function, field_release_value as prob_release  
          FROM {content_type_problem} ctp, {content_field_problem_status} cfps 
          WHERE ctp.nid = cfps.nid and  field_services_target_id = %d";
 */

  $sql_query = db_select('node__field_function', 'nff');
  $sql_query->join('node__field_problem_status', 'nfps', 'nff.entity_id = nfps.entity_id');
  $sql_query->join('node__field_release', 'nfr', 'nfps.entity_id = nfr.entity_id');
  $sql_query->join('node__field_services', 'nfs', 'nfr.entity_id = nfs.entity_id');
  $sql_query->addField('nff', 'field_function_value', 'function');
  $sql_query->addField('nfr', 'field_release_value', 'prob_release');
  $sql_query->condition('nfs.field_services_target_id', $service, '=');

  if ($string == 'archived') {
    $sql_query->condition('nfps.field_problem_status_value', 'geschlossen', '=');
    $sql_query->orderBy('nff.field_function_value');
    // $filter_where = " and cfps.field_problem_status_value = 'geschlossen'  ORDER BY field_function_value";
  }
  else {
    $sql_query->condition('nfps.field_problem_status_value', 'geschlossen', '!=');
    $sql_query->orderBy('nff.field_function_value');
    // $filter_where = " and cfps.field_problem_status_value <> 'geschlossen'  ORDER BY field_function_value";
  }

  
  $default_function[] = t("Select Function");
  $default_release[] = t("Select Release");
 // $query = db_query( $sql. $filter_where, $service);
  $functions = $sql_query->execute()->fetchAll();
//  echo '<pre>';  print_r($sql_query->execute()); exit;
 // echo '<pre>';  print_r($functions); exit;
//  while ($function = db_fetch_array($query)) {
  foreach ($functions as $function) {
    $default_function[$function->function] = $function->function;

    if ($function->prob_release) {
      $default_release[$function->prob_release] = $function->prob_release;
    }
  }

  return array('releases' => $default_release, 'functions' => $default_function);
}

/*
 * Problems display table
 * @sql_where: sql query for filtering the problems.
 * @string: type of problem(current, archived)
 * @limit: limit of problems to display per page.

 * archived problems will have the status "geschlossen".
 * problems which does not have the status 'geschlossen' will come under current
 * Details will displays the detailed problems display page where back to search is available.
 * While back from search same value need to be shown to user.
 * values are stored in session for showing the same results while back to search.
 */
static function problems_default_display($sql_where = NULL, $string = NULL, $limit = NULL) { 
  //  Condition::conditions();
  $build = array();
  $request = \Drupal::request();
  $serialized_data = unserialize($_SESSION['problems_query']);
  
  $sql_where = $serialized_data['sql']?$serialized_data['sql']:$sql_where;
  $string = $serialized_data['type']?$serialized_data['type']:$string;
  $limit = $serialized_data['limit']?$serialized_data['limit']:$limit;
  
  if ($string == 'archived') {
    $url = ( isset($_SESSION['Group_id'])?'node/'.$_SESSION['Group_id'].'/problems/archived_problems':'problems/archived_problems');
    $filter_where = " and nfps.field_problem_status_value = 'geschlossen' ";
  }
  else {
    $url = ( isset($_SESSION['Group_id'])?'node/'.$_SESSION['Group_id'].'/problems':'problems');
    $filter_where = " and nfps.field_problem_status_value != 'geschlossen' ";
  }
  // $sql_select = " SELECT n.nid ";
  $sql_select = db_select('node_field_data', 'nfd');
  $sql_select->Fields('nfd', array('nid'));
  $sql_select->join('node_revision__body', 'nrb', 'nfd.nid = nrb.entity_id');
  $sql_select->join('node__field_s_no', 'nfsn', 'nrb.entity_id = nfsn.entity_id');
  $sql_select->join('node__field_problem_eroffnet', 'nfpe', 'nfsn.entity_id = nfpe.entity_id');
  $sql_select->join('node__field_problem_status', 'nfps', 'nfpe.entity_id = nfps.entity_id');
  $sql_select->join('node__field_services', 'nfs', 'nfps.entity_id = nfs.entity_id');
  $sql_select->join('node__field_processing', 'nfp', 'nfs.entity_id = nfp.entity_id');
  $sql_select->join('node__field_function', 'nff', 'nfp.entity_id = nff.entity_id');
  $sql_select->join('node__field_release', 'nfr', 'nff.entity_id = nfr.entity_id');
  $sql_select->join('node__field_work_around', 'nfwa', 'nfr.entity_id = nfwa.entity_id');

  /**
  $from = " FROM  {node} n 
                  LEFT JOIN {node_revision__body} nrb ON n.nid = nrb.entity_id
                  LEFT JOIN {node__field_s_no} nfsn ON nrb.entity_id = nfsn.entity_id
                  LEFT JOIN {node__field_problem_eroffnet} nfpe  on n.nid=nfpe.entity_id
                  LEFT JOIN {node__field_problem_status} nfps ON nfsn.entity_id = nfps.entity_id
                  LEFT JOIN {node__field_services} nfs ON nfps.entity_id = nfs.entity_id
                  LEFT JOIN {node__field_processing} nfp  ON nfs.entity_id = nfp.entity_id
                  LEFT JOIN {node__field_function} nff ON nfs.entity_id = nff.entity_id
                  LEFT JOIN {node__field_release} nfr ON nff.entity_id = nfr.entity_id";
  */
  /**
  $where = " WHERE nfs.field_services_target_id in (SELECT service_id 
                                           FROM group_problems_view 
                                           WHERE group_id = " . ($_SESSION['Group_id']?$_SESSION['Group_id']:PROBLEM_MANAGEMENT) . ")";
  */

  # 20140605 droy - Sort problems by last update rather than opened
  # $order = " ORDER BY cp.field_problem_eroffnet_value DESC ";
 // $order = " ORDER BY unix_timestamp(str_to_date(nfp.field_processing_value,'%%d.%%m.%%Y')) DESC ";
 // $sql = $sql_select . $from . $where . $sql_where . $filter_where . $order;
 // $count_query = 'SELECT count(*) ' . $from . $where . $sql_where . $filter_where . $order;
  $group_problems_view_service_id_query = db_select('group_problems_view', 'gpv');
  $group_problems_view_service_id_query->Fields('gpv', array('service_id'));
  $group_problems_view_service_id_query->conditions('group_id', $_SESSION['Group_id']?$_SESSION['Group_id']: self::PROBLEM_MANAGEMENT ,'=');
  $group_problems_view_service_id = $group_problems_view_service_id_query->execute()->fetchCol();
  
  if (!empty($sql_where)) {
          $or = db_or();
    foreach ($sql_where as $where) {
      foreach ($where as $conjuction => $condition) {
        if ($conjuction == 'and') {
          $sql_select->condition($condition['field'], $condition['value'], $condition['operator']);
        } else if ( $conjuction == 'or') {
          foreach ($condition as $conditions) {
            $or->condition($conditions['field'], $conditions['value'], $conditions['operator']);
          } 
          $sql_select->condition($or); 
        }
        // $query->condition($sql_where);
      }
    }
  }
 // echo '<pre>';  print_r($sql_select); exit;
  $sql_select->condition('nfs.field_services_target_id', $group_problems_view_service_id,'IN');
  
  if($limit == 'all') {
    $result = $sql_select->execute()->fetchAll();
  }
  else {
    $page_limit = ($limit ? $limit : self::DISPLAY_LIMIT);
    // $table_sort = $sql_select->extend('Drupal\Core\Database\Query\TableSortExtender');
    $pager = $sql_select->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($page_limit);
    $result = $pager->execute()->fetchAll();
  }

  $req_service = $request->request->get('service');
  $req_release = $request->request->get('release');
  $req_function = $request->request->get('function');
  $req_string = $request->request->get('string');
  $req_limit = $request->request->get('limit');
  $page = $request->request->get('page');

  $rows = array();
  $status_msgs = array('Neues Problem', 'Known Error', 'Geschlossen', 'behoben');
  
  foreach ($result as $problems_info) {
    $problems_node = node_load($problems_info->nid);

    $service_query = db_query("select title 
      from {node_field_data}
      where nid = :mlid", array( ':mlid' => $problems_node->field_services->target_id))->fetchAssoc();
    $service = $service_query['title'];    
    $last_update = $problems_node->field_processing->value;

    unset($_SESSION['problems_query']);
        $query_params = array(
        'nid' => $problems_node->nid->value,
        'page' => $page ,
        'type' => $string , 
        'sql' => $sql_where, 
        'service' => $req_service, 
        'function' => $req_function, 
        'string' => $req_string,
        'release' => $req_release, 
        'limit'  => $limit,
        'url' => $url,
        'from' => 1 
        );
    
    $query_seralized = serialize($query_params);  
    $url = Url::fromUserInput('/node/' . $problems_node->nid->value, array(
               'attributes' => array(
                   'class' => 'problems_details_link',
                   'nid' => $problems_node->nid->value,
                   'query' => $query_seralized,
                   )));

    $download_link = array('#title' => array(
      '#markup' => $problems_node->field_s_no->value
    ), 
    '#type' => 'link', 
    '#url' => $url
  );

    $link_path = \Drupal::service('renderer')->renderRoot($download_link);
    $user_input = '/node/' . $problems_node->nid->value;
    $elements = array( 
      'service' => $service,
      'function' => $problems_node->field_function->value,
      'release' => $problems_node->field_release->value,
      'title' => $problems_node->title->value,
      'status' => $problems_node->field_problem_status->value,
      'priority' => $problems_node->field_priority->value,
      'closed' => $last_update,
      'field_version' => $problems_node->field_version->value,
    //  'actions' =>  $url_data->getGeneratedLink(),
      'actions' =>  $link_path,
      );
    if ($string == 'archived') {
      $elements['closed'] =  $problems_node->field_processing->value;
    }
    $rows[] = $elements;
  }

  $header = array(
    0 => array('data' => t('Service'), 'class' => 'service'),
    1 => array('data' => t('Function'), 'class' => 'function'),
    2 => array('data' => t('Release'), 'class' => 'release'),
    4 => array('data' => t('Title'), 'class' => 'problem_title'),
    5 => array('data' => t('Status'), 'class' => 'status'),
    6 => array('data' => t('Priority'), 'class' => 'priority'),
    7 => array('data' => t('Last Update'), 'class' => 'last_update'),
    8 => array('data' => t('Fixed With Release'), 'class' => 'field_version'),
    9 => array('data' => t('SDCallID'), 'class' => 'action'),
  );
  if ($string == 'archived') {
    $header[7] = array('data' => t('Closed On'), 'class' => 'closed');
  }

  if ($rows) {
   // $output .= theme('table', $header, $rows , array('id' => 'sortable', 'class' => 'tablesorter'));
   // return $output .= theme('pager', NULL, $page_limit, 0);
    $build['pager'] = array(
     '#type' => 'pager'
    );

    $build['problem_table'] = array(
     '#theme' => 'table', 
     '#header' => $header,
     '#rows' => $rows,
     '#empty' => t('No Data Created Yet'),
     '#attributes' => ['id' => "sortable", 'class' =>"tablesorter"],
    );
    return $build; 
  }
  return $build = array('#markup' => t("No Data Created Yet"));  
 }

static function delete_group_problems_view() {
    // $group_id = \Drupal::service('user.private_tempstore')->get()->get('Group_id');
    $group_id = $_SESSION['Group_id'];
    db_delete('group_problems_view')->condition('group_id', $group_id, '=')->execute();;
  }
}
