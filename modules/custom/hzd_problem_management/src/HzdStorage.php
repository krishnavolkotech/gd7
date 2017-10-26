<?php

namespace Drupal\problem_management;

use Drupal\Core\Link;
use Drupal\group\Entity\Group;
use Drupal\Component\Utility\Html;
use Drupal\group\Entity\GroupContent;
use Drupal\hzd_services\HzdservicesHelper;
use Drupal\node\Entity\Node;
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
   * Function for saving problem node.
   */
  static public function saving_problem_node($values) {
    $status = t('Error');
    $mail = \Drupal::config('problem_management.settings')->get('import_mail');
    $subject = 'Error while problem csv import';
    $msg = t('Required Field Values Are Missing');
    try {
      if (($values['title'] == '') || ($values['status'] == '')) {
        $message = t('Required Field Values Are Missing');
        \Drupal::logger('problem_management')->error($message);
        $body = t("There is an issue while importing of the file. Required field values are missing.");
        HzdservicesHelper::send_problems_notification('problem_management_read_csv', $mail, $subject, $body);
        HzdStorage::insert_import_status($status, $msg);
        return FALSE;
      }
      /**
       * if (!is_int($values['sno'])) {
       * $message = t(' sno must be integer');
       * \Drupal::logger('problem_management')->error($message);
       * $mail = \Drupal::config('problem_management.settings')->get('import_mail');
       * $subject = 'Error while import';
       * $body = t("There is an issue while importing of the file. sno must be integer.");
       * HzdservicesHelper::send_problems_notification('problem_management_read_csv', $mail, $subject, $body);
       * $status = t('Error');
       * $msg = t('sno must be integer.');
       * HzdStorage::insert_import_status($status, $msg);
       * return FALSE;
       * }
       */
      $values['sno'] = (int) $values['sno'];
      $query = \Drupal::database()->select('groups_field_data', 'gfd');
      $query->Fields('gfd', array('id'));
      $query->condition('label', 'problem management', '=');
      $group_id = $query->execute()->fetchCol();

      $query = \Drupal::entityQuery('node')
              ->condition('type', 'problem')
              ->condition('field_s_no', $values['sno'])
              ->execute();

      $node = \Drupal\node\Entity\Node::load(reset($query));

      if ($node) {
        $nid = $node->id();
        $created = $node->getCreatedTime();
      }
      /*        pr($node);exit;
        $query = \Drupal::database()->select('node_field_data', 'n');
        $query->join('node__field_s_no', 'nfsn', 'n.nid = nfsn.entity_id');
        $query->Fields('n', array('nid', 'vid', 'created'));
        $query->condition('field_s_no_value', $values['sno'], '=');
        $node_infos = $query->execute()->fetchAll();
        pr($node_info);exit;
        foreach ($node_infos as $node_info) {
        $nid = $node_info->nid;
        $vid = $node_info->vid;
        $created = $node_info->created;
        } */
      // The erofnet date field conversion.
      $replace = array('/' => '.', '-' => '.');
      $formatted_date = strtr($values['eroffnet'], $replace);

      $date_time = explode(" ", $formatted_date);

      if (isset($date_time['0'])) {
        $date_format = explode(".", $date_time['0']);
      }
      if (isset($date_time['1'])) {
        $time_format = explode(":", $date_time['1']);
      }

      if (isset($date_format['1']) && isset($date_format['0']) && isset($date_format['2'])) {
        if (isset($time_format['0']) && isset($time_format['1']) && isset($time_format['2'])) {
          $date = mktime((int) $time_format['0'], (int) $time_format['1'], (int) $time_format['2'], (int) $date_format['1'], (int) $date_format['0'], (int) $date_format['2']);
        }
      }


      $eroffnet = (isset($date) ? $date : time());
      // Generate notifications for updated problems.
      if (isset($nid)) {
        unset($values['sno']);
        $exist_node = $node;
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
        $existing_node_vals['closed'] = $exist_node->field_closed->value;
        $existing_node_vals['ticketstore_link'] = $exist_node->field_ticketstore_link->value;
        /**
         * $existing_node_vals['problem_eroffnet'] = $exist_node->field_problem_eroffnet->value;
         * // $existing_node_vals['problem_status'] = $exist_node->field_problem_status->value;
         * $existing_node_vals['ticketstore_count'] = $exist_node->field_ticketstore_count->value;
         * $existing_node_vals['ticketstore_link'] = $exist_node->field_ticketstore_link->value;
         */
        if (count(array_diff($existing_node_vals, $values)) != 0) {
          // $node_array['status'] = 1;.
          $problem_node = $node;
          $problem_node->setTitle(Html::escape($values['title']));
          $problem_node->set('status', 1);
          $problem_node->set('created', $created ? $created : time());
          $problem_node->set('body', $values['body']);
          $problem_node->set('comment', array(
              'status' => 2,
              'cid' => 0,
              'last_comment_timestamp' => 0,
              'last_comment_name' => '',
              'last_comment_uid' => 0,
              'comment_count' => 0,
                  )
          );
          $problem_node->set('field_attachment', Html::escape($values['attachment']));
          $problem_node->set('field_closed', $values['closed']);
          $problem_node->set('field_comments', array(
              'value' => $values['comment'],
              'format' => 'basic_html',
                  )
          );
          $problem_node->set('field_services', array(
              'target_id' => $values['service'],
                  )
          );
          $problem_node->set('field_diagnose', $values['diagnose']);
          $problem_node->set('field_eroffnet', $values['eroffnet']);
          $problem_node->set('field_function', $values['function']);
          $problem_node->set('field_priority', $values['priority']);
          $problem_node->set('field_problem_eroffnet', $eroffnet);
          $problem_node->set('field_problem_status', $values['status']);
          $problem_node->set('field_processing', $values['processing']);
          $problem_node->set('field_release', $values['release']);
          // $problem_node->set('field_sdcallid', $values['sdcallid']);.
          $problem_node->set('field_solution', array(
              'value' => $values['solution'],
              'format' => 'basic_html',
          ));
          // $problem_node->set('field_s_no', $values['sno']);
          // $problem_node->set('field_release', $values['release']);.
          $problem_node->set('field_task_force', array($values['taskforce']));
          // $problem_node->set('field_release', $values['release']);
          // $problem_node->set('field_ticketstore_count', $values['ticketstore_count']);
          // $problem_node->set('field_release', $values['release']);
          $problem_node->set('field_ticketstore_link', $values['ticketstore_link']);
          // $problem_node->set('field_timezone', $values['timezone']);.
          $problem_node->set('field_version', $values['version']);
          $problem_node->set('field_work_around', array(
              'value' => $values['workaround'],
              'format' => 'basic_html',
          ));
          $problem_node->save();
          return TRUE;
        }
      } else {
        $node_array = array(
            'nid' => array(''),
            'vid' => array(''),
            'type' => 'problem',
            'title' => Html::escape($values['title']),
            'uid' => 1,
            'status' => 1,
            'created' => time(),
            'body' => array(
                'summary' => '',
                'value' => $values['body'],
                'format' => 'basic_html',
            ),
            'comment' => array(
                'status' => 2,
                'cid' => 0,
                'last_comment_timestamp' => 0,
                'last_comment_name' => '',
                'last_comment_uid' => 0,
                'comment_count' => 0,
            ),
            'field_attachment' => $values['attachment'],
            'field_closed' => $values['closed'],
            'field_comments' => array(
                'value' => $values['comment'],
                'format' => 'basic_html',
            ),
            'field_diagnose' => $values['diagnose'],
            'field_eroffnet' => $values['eroffnet'],
            'field_function' => array(
                'value' => $values['function'],
            ),
            'field_priority' => $values['priority'],
            'field_problem_eroffnet' => $eroffnet,
            'field_problem_status' => Html::escape($values['status']),
            'field_processing' => $values['processing'],
            'field_release' => $values['release'],
            // 'field_sdcallid' => $values['sdcallid'],.
            'field_services' => array(
                'target_id' => $values['service'],
            ),
            // 'field_timezone' => $values['timezone'],.
            'field_solution' => array(
                'value' => $values['solution'],
                'format' => 'basic_html',
            ),
            'field_s_no' => $values['sno'],
            'field_task_force' => $values['taskforce'],
            // 'field_ticketstore_count' => $values['ticketstore_count'],
            'field_ticketstore_link' => array(
                'value' => $values['ticketstore_link'],
                'format' => 'basic_html',
            ),
  //                'field_ticketstore_link' => $values['ticketstore_link'],
            'field_version' => $values['version'],
            'field_work_around' => array(
                'value' => $values['workaround'],
                // 'timezone' =>  $values['timezone'],.
                'format' => 'basic_html',
            ),
            'status' => 1,
        );

        $node = Node::create($node_array);
        $node->save();
        $nid = $node->id();
        if ($nid) {
          // $group_id = \Drupal::routeMatch()->getParameter('group');
          $group = Group::load($group_id['0']);
          // Adding node to group.
          $group_content = GroupContent::create([
                      'type' => $group->getGroupType()->getContentPlugin('group_node:problem')->getContentTypeConfigId(),
                      'gid' => $group_id,
                      'entity_id' => $node->id(),
                      'request_status' => 1,
                      'label' => $values['title'],
                      'uid' => 1,
          ]);
          $group_content->save();
        }
        return TRUE;
      }
    }
    catch (Exception $e) {
      $body = $e->getMessage();
      \Drupal::logger('problem_management')->error($body);
      HzdservicesHelper::send_problems_notification('problem_management_read_csv', $mail, $subject, $body);
      HzdStorage::insert_import_status($status, $body);
    }
    return FALSE;
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
    } else {
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
    $query->Fields('pmh', array('problem_date', 'import_status', 'error_message'));

    // $table_sort = $query->extend('Drupal\Core\Database\Query\TableSortExtender');.
    if ($limit != 'all') {
      $page_limit = ($limit ? $limit : 20);
      $query->orderBy('problem_date', 'desc');
      $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($page_limit);
      $result = $pager->execute();
    } else {
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
    } else {
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
    return array('releases' => $default_release, 'functions' => $default_function);
    
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
    } else {
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
        } else {
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
    } else {
      $page_limit = ($limit ? $limit : DISPLAY_LIMIT);
      $pager = $conn->extend('Drupal\Core\Database\Query\PagerSelectExtender');
      $result = $pager->limit($page_limit)->execute()->fetchCol();
    }

    $rows = array();
    $status_msgs = array('Neues Problem', 'Known Error', 'Geschlossen', 'behoben');
    foreach ($result as $problems_info) {
      $problems_node = \Drupal\node\Entity\Node::load($problems_info);
      $node_problem_group_id = \Drupal::entityQuery('group_content')
              ->condition('type', 'open-group_node-problem', '=')
              ->condition('entity_id', $problems_node->id(), '=')
              ->execute();
      $groupContentEntity = \Drupal\group\Entity\GroupContent::load(
                      current($node_problem_group_id));
      $groupContentItemUrl = null;
      if ($groupContentEntity instanceof \Drupal\group\Entity\GroupContent) {
        $groupContentItemUrl = Link::fromTextAndUrl($problems_node->field_s_no->value, $problems_node->toUrl('canonical', ['absolute' => 1, 'query' => $exposedFilterData]));
//        $groupContentItemUrl = Link::fromTextAndUrl($problems_node->field_s_no->value, Url::fromRoute('cust_group.group_content_view', ['group' => $groupContentEntity->getGroup()->id(), 'group_content' => $groupContentEntity->id(), 'type' => 'problems'], ['absolute' => 1, 'query' => $exposedFilterData]));
//                $groupContentItemUrl = $groupContentEntity->toLink(
//                    $problems_node->field_s_no->value, 'canonical', ['absolute' => 1,
//                        'query' => $exposedFilterData,
//                    ]
//                );
      }
      // redirect to the node view if a specified SDCallID is searched for
      if (is_numeric($filterData->get('string', null)) && count($result) == 1) {
        $response = new RedirectResponse($groupContentEntity->getEntity()->toUrl()->toString());
        $response->send();
      }


      $service_query = \Drupal\node\Entity\Node::load(
                      $problems_node->field_services->target_id);
      $service = $service_query->get('field_problem_name')->value;
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
    \Drupal::database()->delete('group_problems_view')->condition('group_id', $group_id, '=')
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
