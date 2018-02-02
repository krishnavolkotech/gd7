<?php

namespace Drupal\problem_management;

use Drupal\Core\Link;
use Drupal\group\Entity\Group;
use Drupal\Component\Utility\Html;
use Drupal\group\Entity\GroupContent;
use Drupal\hzd_services\HzdservicesHelper;
use Drupal\node\Entity\Node;
use Drupal\problem_management\Exception\CustomException;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

if (!defined('DISPLAY_LIMIT')) {
  define('DISPLAY_LIMIT', 20);
}

/**
 *
 */
class HzdStorage {

  protected $tempStore;

  // Pass the dependency to the object constructor.
  const DISPLAY_LIMIT = 20;

  /**
   *
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    // For "mymodule_name," any unique namespace will do.
    $this->tempStore = $temp_store_factory->get('problem_management');
  }

  /**
   * Inserts the status of the import file on cron run.
   */
  static public function insert_import_status($status, $msg) {
    // Populate the node access table.
    db_insert('problem_import_history')
      ->fields(array(
        'problem_date' => time(),
        'import_status' => $status,
        'error_message' => $msg,
      ))
      ->execute();
    // $sql = "insert into {problem_import_history} (problem_date, import_status, error_message) values (%d, '%s', '%s') ";
    // db_query($sql, time(), $status, $msg);.
  }

  /**
   *
   */
  static public function insert_group_problems_view($group_id, $selected_services) {

    // $sql = 'insert into {group_problems_view} (group_id, service_id) values (%d, %d)';.
    $counter = 0;

    // $tempstore = \Drupal::service('user.private_tempstore')->get('problem_management');
    // $group_id = $tempstore->get('Group_id');.
    $group = \Drupal::routeMatch()->getParameter('group');
    if (is_object($group)) {
      $group_id = $group->id();
    }
    else {
      $group_id = $group;
    }

    if (!empty($selected_services)) {

      foreach ($selected_services as $service) {

        if (!empty($service)) {
          $counter++;
        }
        $query = \Drupal::database()
          ->insert('group_problems_view')
          ->fields(array('group_id' => $group_id, 'service_id' => $service))
          ->execute();
        // db_query($sql, $_SESSION['Group_id'], $service);.
      }
    }
    return $counter;
  }

  /**
   *
   */
  static public function import_history_display_table($limit = NULL) {
    $build = array();

    $query = \Drupal::database()->select('problem_import_history', 'pmh');
    $query->Fields('pmh', array(
      'problem_date',
      'import_status',
      'error_message'
    ));

    // $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender');.
    if ($limit != 'all') {
      $page_limit = ($limit ? $limit : 20);
      $query->orderBy('problem_date', 'desc');
      $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')
        ->limit($page_limit);
      $result = $pager->execute();
    }
    else {
      $query->orderBy('problem_date', 'desc');
      $result = $query->execute()->fetchAll();
    }

    foreach ($result as $row) {
      $elements = array(
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
      '#attributes' => array(
        'id' => 'sortable_new',
        '#class' => 'tablesorter',
      ),
    );
    $build['pager'] = array(
      '#type' => 'pager',
      '#prefix' => '<div id="pagination">',
      '#suffix' => '</div>',
    );
    // Echo '<pre>';  print_r($build); exit;.
    return $build;
  }

  /**
   * Function for populating the Functions and releses option values.
   */
  static public function get_functions_release($string = NULL, $service = NULL) {

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'problem')
      ->condition('field_services', $service);
    if ($string == 'archived_problems') {
      $query->condition('field_problem_status', 'geschlossen');
    }
    else {
      $query->condition('field_problem_status', 'geschlossen', '<>');
    }
    $ids = $query->sort('field_function')->execute();
    $entities = Node::loadMultiple($ids);
    $default_function = $default_release = [];
    foreach ($entities as $entity) {
      $default_function[$entity->get('field_function')->value] = $entity->get('field_function')->value;
//      if ($entity->hasField('field_release') && !empty($entity->get('field_release')->value)) {
      if ($entity->hasField('field_release')) {
        $default_release[$entity->get('field_release')->value] = $entity->get('field_release')->value;
      }
    }
    natcasesort($default_release);
    natcasesort($default_function);
    return array(
      'releases' => $default_release,
      'functions' => $default_function
    );

    //Optimized the code with entityquery above

    /*    $sql_query = \Drupal::database()->select('node__field_function', 'nff');
        $sql_query->join('node__field_problem_status', 'nfps', 'nff.entity_id = nfps.entity_id');
        $sql_query->join('node__field_release', 'nfr', 'nfps.entity_id = nfr.entity_id');
        $sql_query->join('node__field_services', 'nfs', 'nfr.entity_id = nfs.entity_id');
        $sql_query->addField('nff', 'field_function_value', 'function');
        $sql_query->addField('nfr', 'field_release_value', 'prob_release');
        $sql_query->condition('nfs.field_services_target_id', $service, '=');

        if ($string == 'archived_problems') {
          $sql_query->condition('nfps.field_problem_status_value', 'geschlossen', '=');
          $sql_query->orderBy('nff.field_function_value');
        } else {
          $sql_query->condition('nfps.field_problem_status_value', 'geschlossen', '!=');
          $sql_query->orderBy('nff.field_function_value');
        }
    //    $default_function[] = t("Select Function");
    //    $default_release[] = t("Select Release");
        $functions = $sql_query->execute()->fetchAll();
        foreach ($functions as $function) {
          $default_function[$function->function] = $function->function;
          if ($function->prob_release) {
            $default_release[$function->prob_release] = $function->prob_release;
          }
        }
    //    pr($default_function);exit;
        return array('releases' => $default_release, 'functions' => $default_function);*/
  }

  /**
   * Problems display table.
   *
   * @sql_where: sql query for filtering the problems.
   * @string: type of problem(current, archived)
   * @limit: limit of problems to display per page.
   *
   * archived problems will have the status "geschlossen".
   * problems which does not have the status 'geschlossen' will come under current
   * Details will displays the detailed problems display page where back to search is available.
   * While back from search same value need to be shown to user.
   * values are stored in session for showing the same results while back to search.
   */
  static public function problems_default_display($string = NULL, $limit = NULL) {
    $group_id = get_group_id();
    $filter_parameter = self::get_problem_filters();
    $filterData = \Drupal::request()->query;
    $exposedFilterData = $filterData->all();
    $problem_node_ids = \Drupal::entityQuery('node')
      ->condition('type', 'problem', '=');
    $build = array();
    if ($string == 'archived_problems') {
      $problem_node_ids->condition('field_problem_status', 'geschlossen', 'LIKE');
    }
    else {
      $problem_node_ids->condition('field_problem_status', 'geschlossen', 'NOT LIKE');
    }
    if (isset($filter_parameter['service']) && $filter_parameter['service'] != 0) {
      $problem_node_ids->condition('field_services', $filter_parameter['service'], '=');
    }

    if (isset($filter_parameter['function']) && $filter_parameter['function'] != '0') {
      $problem_node_ids->condition('field_function', trim($filter_parameter['function']), '=');
    }
    if (isset($filter_parameter['release']) && $filter_parameter['release'] != '0') {
      if (empty($filter_parameter['release'])) {
        $problem_node_ids->notExists('field_release');
      }
      else {
//          $problem_node_ids->condition('field_release', trim($filter_parameter['release']), '=');
        $problem_node_ids->condition('field_release', $filter_parameter['release'], '=');
      }
    }

    if (isset($filter_parameter['string']) && t($filter_parameter['string']) != t('Search Title, Description, Cause, Workaround, Solution')) {
      $group = $problem_node_ids->orConditionGroup()
        ->condition('field_s_no', $filter_parameter['string'], '=')
        ->condition('title', '%' . $filter_parameter['string'] . '%', 'LIKE')
        ->condition('body', '%' . $filter_parameter['string'] . '%', 'LIKE')
        ->condition('field_work_around', '%' . $filter_parameter['string'] . '%', 'LIKE');
      $problem_node_ids->condition($group);
    }

    if (isset($filter_parameter['limit'])) {
      $limit = $filter_parameter['limit'];
    }
    $group_problems_view = self::get_problems_services($group_id);
    if (!empty($group_problems_view)) {
      $problem_node_ids->condition('field_services', $group_problems_view, 'IN');
    }
//    $problem_node_ids->addTag('debug');
    //As sort on fields with format d.m.Y cannot be supported by entity query I m using database api to achieve it.
    $ids = $problem_node_ids->execute();
    if (empty($ids)) {
      $ids = [-1];
    }
    $conn = \Drupal::database()->select('node_field_data', 'nfd');
    $conn->addField('nfd', 'nid', 'dsa');
    $conn = $conn->condition('nfd.nid', $ids, 'IN')
      ->orderBy('unix_order', 'desc');
//        if ($string == 'archived_problems') {
//            $conn->addExpression("STR_TO_DATE(nfp.field_closed_value,'%d.%m.%Y')",'unix_order');
//            $conn->leftJoin('node__field_closed','nfp','nfp.entity_id = nfd.nid');
//        }else{
    $conn->addExpression("STR_TO_DATE(nfp.field_processing_value,'%d.%m.%Y')", 'unix_order');
    $conn->leftJoin('node__field_processing', 'nfp', 'nfp.entity_id = nfd.nid');
//        }
    if ($limit == 'all') {
      $result = $conn->execute()->fetchCol();
    }
    else {
      $page_limit = ($limit ? $limit : DISPLAY_LIMIT);
      $pager = $conn->extend('Drupal\Core\Database\Query\PagerSelectExtender');
      $result = $pager->limit($page_limit)->execute()->fetchCol();
    }

    $rows = array();
    $status_msgs = array(
      'Neues Problem',
      'Known Error',
      'Geschlossen',
      'behoben'
    );
    foreach ($result as $problems_info) {
      $problems_node = \Drupal\node\Entity\Node::load($problems_info);
      $node_problem_group_id = \Drupal::entityQuery('group_content')
        ->condition('type', 'open-group_node-problem', '=')
        ->condition('entity_id', $problems_node->id(), '=')
        ->execute();
      $groupContentEntity = \Drupal\group\Entity\GroupContent::load(
        current($node_problem_group_id));
      $groupContentItemUrl = NULL;
      if ($groupContentEntity instanceof \Drupal\group\Entity\GroupContent) {
        $groupContentItemUrl = Link::fromTextAndUrl($problems_node->field_s_no->value, $problems_node->toUrl('canonical', [
          'absolute' => 1,
          'query' => $exposedFilterData
        ]));
//        $groupContentItemUrl = Link::fromTextAndUrl($problems_node->field_s_no->value, Url::fromRoute('cust_group.group_content_view', ['group' => $groupContentEntity->getGroup()->id(), 'group_content' => $groupContentEntity->id(), 'type' => 'problems'], ['absolute' => 1, 'query' => $exposedFilterData]));
//                $groupContentItemUrl = $groupContentEntity->toLink(
//                    $problems_node->field_s_no->value, 'canonical', ['absolute' => 1,
//                        'query' => $exposedFilterData,
//                    ]
//                );
      }
      // redirect to the node view if a specified SDCallID is searched for
      if (is_numeric($filterData->get('string', NULL)) && count($result) == 1) {
        $response = new RedirectResponse($groupContentEntity->getEntity()
          ->toUrl()
          ->toString());
        $response->send();
      }


      $service_query = \Drupal\node\Entity\Node::load(
        $problems_node->field_services->target_id);
      $service = $service_query->get('title')->value;
      $last_update = $problems_node->field_processing->value;
      $user_input = '/node/' . $problems_node->nid->value;
      $elements = array(
        'service' => $service,
        'function' => $problems_node->field_function->value,
        'release' => $problems_node->field_release->value,
        'title' => Html::decodeEntities($problems_node->title->value),
        'status' => $problems_node->field_problem_status->value,
        'priority' => $problems_node->field_priority->value,
        'closed' => $last_update,
        'actions' => $groupContentItemUrl,
      );

      if ($string == 'archived_problems') {
        $elements['field_version'] = $problems_node->field_version->value;
        $elements['closed'] = $last_update;
      }
//      $elements['actions'] = $link_path;

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
    );
    $header[] = array(
      'data' => t('SDCallID'),
      'class' => 'action',
    );
    if ($string == 'archived_problems') {

      $header[] = array(
        'data' => t('Fixed With Release'),
        'class' => 'field_version',
      );

      $header[7] = array('data' => t('Closed On'), 'class' => 'closed');
    }
//        pr($header);exit;
//        if ($rows) {
    $build['problem_table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No Data Created Yet'),
      '#attributes' => ['id' => "sortable", 'class' => "tablesorter"],
      '#caption' => t('Problems'),
      '#cache'=>['tags'=>['node_list']]
    );

    $build['pager'] = array(
      '#type' => 'pager',
      '#prefix' => '<div id="pagination">',
      '#suffix' => '</div>',
      '#exclude_from_print' => 1,
    );
    return $build;
//        }
    /*        return $build = array(
      '#prefix' => '<div id="no-result">',
      '#markup' => t("No Data Created Yet"),
      '#suffix' => '</div>',
      ); */
  }

  /**
   *
   */
  static public function delete_group_problems_view($group_id = NULL) {
    if (!$group_id) {
      return FALSE;
    }
    // $group_id = \Drupal::service('user.private_tempstore')->get()->get('Group_id');
    \Drupal::database()
      ->delete('group_problems_view')
      ->condition('group_id', $group_id, '=')
      ->execute();
  }

  /*
   * @return array
   * all parameters fetched from url
   */

  static public function get_problem_filters() {
    $parameters = array();
    $request = \Drupal::request()->query;
    $parameters['service'] = $request->get('service');
    $parameters['function'] = $request->get('function');
    $parameters['release'] = $request->get('release');
    $parameters['string'] = $request->get('string');
    $parameters['limit'] = $request->get('limit');
    return $parameters;
  }

  static public function get_problems_services($group_id) {
    $group_problems_view_service_id_query = \Drupal::database()->select(
      'group_problems_view', 'gpv');
    $group_problems_view_service_id_query->addField('gpv', 'service_id');
    $group_problems_view_service_id_query->condition('group_id', isset($group_id) ? $group_id : PROBLEM_MANAGEMENT, '=');
    $group_problems_view_service_id_query->condition('service_id', '0', '!=');
    $group_problems_view_service = $group_problems_view_service_id_query
      ->execute()->fetchAll();
    $group_problems_view = array();
    if (!empty($group_problems_view_service)) {
      foreach ($group_problems_view_service as $service) {
        $group_problems_view[] = $service->service_id;
      }
    }
    return $group_problems_view;
  }

}
