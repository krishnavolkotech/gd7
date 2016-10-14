<?php

namespace Drupal\hzd_earlywarnings;
use Drupal\Core\Url;
  
/**
 * Use Drupal\node\Entity\Node;
 * use Drupal\user\PrivateTempStoreFactory;
 * use Drupal\Core\Url;
 * use Drupal\Core\Database\Query\Condition;.
 */
class HzdearlywarningsStorage {

  /**
   * @filter_options:filtering options for filtering early warnings
   * @return:displays the early warnings in the table format.
   * Display table for the relese overviw of early warnings
   * In the display the responses field contains the count of comments posted on the early warning.
   */
  public function release_earlywarnings_display_table($filter_options = NULL, $release_type = KONSONS) {
    $group = \Drupal::routeMatch()->getParameter('group');
    if (is_object($group)) {
      $group_id = $group->id();
    }
    else {
      $group_id = $group;
    }

    if (!$filter_options) {
      $filter_options = $_SESSION['earlywarning_filter_option'];
      $release_type = $_SESSION['earlywarning_filter_option']['release_type'];
    }
    if ($filter_options['limit'] != 'all') {
      $page_limit = ($filter_options['limit'] ? $filter_options['limit'] : DISPLAY_LIMIT);
    }

    $release = array('data' => t('Release'), 'class' => 'release-hdr');
    $earlywarnings = array('data' => t('Early Warnings'), 'class' => 'early-warnings-hdr');
    $responses = array('data' => t('Responses'), 'class' => 'responses-hdr');
    $lastposting = array('data' => t('Last Posting'), 'class' => 'last-posting-hdr');
    $header = array($release, $earlywarnings, $responses, $lastposting);
    // $select = "SELECT  n.nid as nid , title as title, n.created as created , n.uid as uid , field_release_service_value as service, cfer.field_earlywarning_release_value as release_id ";.
    $query = db_select('node_field_data', 'nfd');
    $query->Fields('nfd', array('nid', 'title', 'created', 'uid'));
    $query->addField('field_release_service_value', 'service');
    $query->addField('nfer.field_earlywarning_release_value', 'release_id');
    $query->join('node__field_earlywarning_release', 'nfer', 'nfer.entity_id = nfrs.entity_id');
    $query->join('node__field_release_service', 'nfrs', 'nfer.nid = nfd.nid');
    $query->join('node__release_type', 'nrt', 'nrt.entity_id = nfrs.field_release_service_value');
    $query->condition('tn.tid', $release_type, '=');
    $query->condition('nfd.type', 'early_warnings', '=');

    // $where = " WHERE cfer.nid = cfrs.nid and cfer.nid = n.nid and tn.nid = cfrs.field_release_service_value
    //              and n.type = 'early_warnings'  and tn.tid = " . $release_type;.
    if (isset($filter_options)) {
      if ($filter_options['service']) {
        $query->condition('field_release_service_value', $filter_options['service'], '=');
        // $where .= " and field_release_service_value = " . $filter_options['service'];.
      }
      if ($filter_options['release']) {
        $query->condition('field_earlywarning_release_value', $filter_options['release'], '=');
        // $where .= " and field_earlywarning_release_value =  " . $filter_options['release'];.
      }
      if ($filter_options['startdate']) {
        // $startdate_info = explode('.', $filter_options['startdate']);
        // $startdate = mktime(0, 0, 0, $startdate_info[1], $startdate_info[0], $startdate_info[2]);.
        $startdate = strtotime($startdate);
        $query->condition('created', $startdate, '>');
        // $where .= " and created > ". $startdate;.
      }
      if ($filter_options['enddate']) {
        // $enddate_info = explode('.', $filter_options['enddate']);
        // $enddate = mktime(23, 59, 59, $enddate_info[1], $enddate_info[0], $enddate_info[2]);
        // $where .= " and created between " . ($startdate?$startdate:0) . " and " . $enddate;.
        $enddate = strtotime($filter_options['enddate']);
        $query->condition('created', array(($startdate ? $startdate : 0), $enddate), 'between');
      }
    }

    $in_order = ' ORDER by n.created DESC ';
    // $group_select = "SELECT  dep.nid , dep.title, dep.created , dep.uid , dep.service, dep.release_id ";
    // $group_group = " GROUP BY dep.release_id ORDER By dep.created DESC ";
    // $group_sql = $group_select . " from (" . $select . $from . $where . $in_order . " ) dep " . $group_group;.
    $query_result = $query->execute();
    $group_sql = db_select('$query_result', 'sep');
    $group_sql->Fields('dep', array('nid', 'title', 'created', 'uid', 'service', 'release_id'));
    $group_sql->groupBy('dep.release_id');
    $group_sql->orderBy('dep.created', 'DESC');

    // $count_query = " SELECT count(*) FROM (" . $group_sql . ") count";.
    /**
   * if ($page_limit) {
   * $earlywarnings_query = pager_query($group_sql, $page_limit, 0 , $count_query);
   * }
   * else {
   * $earlywarnings_query = db_query($group_sql, $page_limit);
   * }
   */
    if ($page_limit) {
      // $earlywarnings_query = pager_query($sql, $page_limit, 0 );.
      $pager = $group_sql->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($page_limit);
      $earlywarnings_query = $pager->execute()->fetchAll();
    }
    else {
      $earlywarnings_query = $group_sql->execute()->fetchAll();
      // $earlywarnings_query = db_query($sql, $page_limit, 0 , $count_query);.
    }
    // While ($earlywarnings = db_fetch_array($earlywarnings_query)) {.
    foreach ($earlywarnings_query as $earlywarnings) {
      if ($earlywarnings->release_id) {
        $warnings_lastpost = self::get_early_warning_lastposting_count($earlywarnings->release_id);
        $warning_imgpath = drupal_get_path('module', 'release_management') . "/images/icon.png";
        $warning_icon = "<img src = '/" . $warning_imgpath . "'>";
        $warningclass = ($warnings_lastpost['warnings'] >= 10 ? 'warningcount_second' : 'warningcount');

        $title_query = db_select('node_field_data', 'nfd')->Fields('nfd', array('title'))->condition('nid', $earlywarnings->release_id, '=')->execute()->fetchCol();

        /**
         * db_result(db_query("SELECT count(*) FROM {comments} c, {content_field_earlywarning_release} ctew
         * WHERE c.nid =  ctew.nid and ctew.field_earlywarning_release_value = %d", $earlywarnings->release_id))
         */
        $comment_count_query = db_select('comment', 'c');
        $comment_count_query->addField('*');
        $comment_count_query->join('node__field_earlywarning_release', 'nfer', 'c.nid =  nfer.entity_id');
        $comment_count_query->condition('nfer.field_earlywarning_release_value', $earlywarnings->release_id, '=');
        $comment_count_query->execute->fetchCol();
        $elements = array(
          // db_result(db_query("SELECT title FROM {node} where nid = %d", $earlywarnings->release_id))
          array('data' => $title_query['title'], 'class' => 'releases-cell'),
          array('data' => ($warnings_lastpost['warnings'] ? l("<span class = '" . $warningclass . "'>" . $warnings_lastpost['warnings'] . "</span>", "group/" . $group_id . "/early-warnings", array('attributes' => array('alt' => t('Read Early Warnings for this release'), 'class' => 'view-earlywarning', 'title' => t('Read Early Warnings for this release')), 'html' => TRUE, 'query' => 'ser=' . $earlywarnings->service . '&type=released' . '&rel=' . $earlywarnings->release_id . '&rel_type=' . $release_type)) : ''), 'class' => 'earlywarnings-cell'),
          array('data' => $comment_count_query, 'class' => 'responses-cell'),
          array('data' => $warnings_lastpost['lastpost'], 'class' => 'lastpostdate-cell'),
        );

        $rows[] = $elements;
      }
    }
    /**
     * if (!isset($elements)) {
     * return $output = t('No Data to be displayed');
     * }
    */
    // $output .= theme('table', $header, $rows , array('id' => 'earlywarnings_release_sortable', 'class' => 'tablesorter'));.
    // $output .= ($page_limit ? theme('pager', NULL, $page_limit, 0): '');.
    if ($rows) {
      // $output .= theme('table', $header, $rows , array('id' => 'sortable', 'class' => 'tablesorter'));
      // return $output .= theme('pager', NULL, $page_limit, 0);.
      $output['pager'] = array(
        '#type' => 'pager',
      );

      $output['problem_table'] = array(
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => t('No Data Created Yet'),
        '#attributes' => ['id' => "earlywarnings_release_sortable", 'class' => "tablesorter"],
      );
      return $output;
    }
  }
  
  /**
   * @filter_options:filtering options for filtering early warnings
   * @return:displays the early warnings in the table format.
   * Display table for the relese service specific early warnings
   * In the display the responses field contains the count of comments posted on the early warning.
   * Function for displaying the early warnings for a particular service and release
   */
  static public function view_earlywarnings_display_table($filter_options = NULL, $release_type = KONSONS) {
    $output = array();
   
    if(empty($filter_options)) {
      $filter_options['service'] = $_SESSION['earlywarning_filter_option']['service'];
      $filter_options['release'] = $_SESSION['earlywarning_filter_option']['release'];
      if (isset($_SESSION['earlywarning_filter_option']['startdate'])) {
        $filter_options['startdate'] = $_SESSION['earlywarning_filter_option']['startdate'];
      }
      if (isset($_SESSION['earlywarning_filter_option']['enddate'])) {
        $filter_options['enddate'] = $_SESSION['earlywarning_filter_option']['enddate'];
      }
        $filter_options['release_type'] = $_SESSION['earlywarning_filter_option']['release_type'];
    }
    
    if ($filter_options['limit'] != 'all') {
      $page_limit = ($filter_options['limit'] ? $filter_options['limit'] : 20);
    }

    $earlywarnings = array('data' => t('Early Warnings'), 'class' => 'early-warningslink-hdr');
    $date = array('data' => t('Created On'), 'class' => 'date-hdr');
    $responses = array('data' => t('Responses'), 'class' => 'responses-hdr');
    $lastcomment = array('data' => t('Last Comment'), 'class' => 'last-comment-hdr');
    $header = array($earlywarnings, $date, $responses, $lastcomment);

    $query = db_select('node_field_data', 'n');
    $query->join('node__field_release_service', 'nfrs', 'n.nid = nfrs.entity_id');
    $query->join('node__field_earlywarning_release', 'nfer', 'n.nid = nfer.entity_id');
    $query->join('node__release_type', 'nrt', 'nfrs.field_release_service_value = nrt.entity_id');
    $query->condition('n.type', 'early_warnings', '=');

    // if(\Drupal::request()->get('rel_type')) {.
    $release_type = $filter_options['release_type'];
    // }.
    $service = $filter_options['service'];
    $release = $filter_options['release'];
    
    
    if (!empty($filter_options)) {
      $query->condition('nrt.release_type_target_id', $filter_options['release_type'], '=');

      if ($filter_options['service'] && $filter_options['service'] != 0) {
        $query->condition('nfrs.field_release_service_value', $filter_options['service'], '=');
      }

      if ($filter_options['release'] && $filter_options['release'] != 0) {
        $query->condition('nfer.field_earlywarning_release_value', $filter_options['release'], '=');
      }

      if ($filter_options['startdate']) {
        // $startdate_info = explode('.', $filter_options['startdate']);
        // $startdate = mktime(0, 0, 0, $startdate_info[1], $startdate_info[0], $startdate_info[2]);.
        $startdate = strtotime($filter_options['startdate']);
        $query->condition('n.created', $startdate, '>');
      }
      if ($filter_options['enddate']) {
        // $enddate_info = explode('.', $filter_options['enddate']);
        // $enddate = mktime(23, 59, 59, $enddate_info[1], $enddate_info[0], $enddate_info[2]);.
        $startdate = strtotime($filter_options['enddate']);
        $query->condition('n.created', array($startdate ? $startdate : 0, $enddate), 'between');
      }
    }
    elseif ($service && $release) {
      $query->condition('nfrs.field_release_service_value', $service, '=')
        ->condition('nfer.field_earlywarning_release_value', $release, '=')
        ->condition('nrt.release_type_target_id', $release_type, '=');
    }
    $count_query = clone $query;
    $count_query->addExpression('COUNT(DISTINCT n.nid)');
    $paged_query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $paged_query->setCountQuery($count_query);
    $paged_query->fields('n', array('nid', 'title', 'created', 'uid'))
      ->orderBy('n.created', 'DESC');

    // Echo '<pre>'; print_r($query->conditions()); exit;.
    if ($filter_options['limit'] != 'all') {
      $page_limit = ($filter_options['limit'] ? $filter_options['limit'] : 20);
      $paged_query->limit($page_limit);
      $result = $paged_query->execute()->fetchAll();
    }
    else {
      $result = $query->execute()->fetchAll();
    }
    foreach ($result as $vals) {
      $user_query = db_select('cust_profile', 'cp');
      $user_query->condition('cp.uid', $vals->uid, '=')
        ->fields('cp', array('firstname', 'lastname'));
      $author = $user_query->execute()->fetchAll();
      $author_name = $author[0]->firstname . ' ' . $author[0]->lastname;
      $total_responses = self::get_earlywarning_responses_info($vals->nid);
      
      $url = Url::fromRoute('entity.node.canonical', array('node' => $vals->nid)); 
      $early_warning = \Drupal::service('link_generator')->generate($vals->title, $url);
      $elements = array(
        array('data' => $early_warning, 'class' => 'earlywarningslink-cell'),
        array('data' => date('d.m.Y', $vals->created) . ' ' . t('by') . ' ' . $author_name, 'class' => 'created-cell'),
        array('data' => $total_responses['total_responses'], 'class' => 'responses-cell'),
        array('data' => $total_responses['response_lastposted'], 'class' => 'lastpostdate-cell'),
      );
      $rows[] = $elements;
    }

    if (count($rows) == 0) {
      $output[]['#markup'] = t('No Data to be displayed');
      return $output;
    }

    $output['earlywarnings'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No Data Created Yet'),
      '#attributes' => ['id' => "sortable", 'class' => "tablesorter"],
    );

    $output['pager'] = array(
      '#type' => 'pager',
      '#prefix' => '<div id="pagination">',
      '#suffix' => '</div>',
    );

    return $output;
  }

  /**
   * Get early warning responses info.
   */
  static public function get_earlywarning_responses_info($earlywarnings_nid) {
    $total_responses = db_query("SELECT COUNT(*) FROM {comment_field_data} WHERE entity_id = :nid",
                       array(":nid" => $earlywarnings_nid))->fetchField();
    $resonses_sql = db_query("SELECT entity_id, uid, created FROM {comment_field_data} WHERE entity_id = :eid ORDER BY created DESC limit 1",
                    array(":eid" => $earlywarnings_nid))->fetchAll();
    
    foreach ($resonses_sql as $vals) {
      $responses['uid'] = $vals->uid;
      $responses['last_posted'] = date('d.m.Y', $vals->created);
      if ($responses['last_posted']) {
        $user_query = db_select('cust_profile', 'cp');
        $user_query->condition('cp.uid', $vals->uid, '=')
          ->fields('cp', array('firstname', 'lastname'));
        $author = $user_query->execute()->fetchAll();
        $response_lastposted = $responses['last_posted'] . ' ' . t('by') . ' ' . $author[0]->firstname . ' ' . $author[0]->lastname;
      }
      else {
        $response_lastposted = '';
      }
    }
    
    $response_info = array('total_responses' => $total_responses, 'response_lastposted' => $response_lastposted);
    return $response_info;
  }

  /**
   * Returns last posting warnings count,responses.
   */
  public function get_early_warning_lastposting_count($release_id) {

    // $sql = db_query("SELECT n.nid, n.uid, n.created  FROM {node} n , {content_field_earlywarning_release} ctew   WHERE n.nid = ctew.nid and field_earlywarning_release_value = %d and n.type = '%s' order by created DESC", $release_id, 'early_warnings');.
    $sql_query = db_select('node', 'n');
    $sql_query->Fields('n', array('nid', 'uid', 'created'));
    $sql_query->join('node__field_earlywarning_release', 'nfer', 'n.nid = nfer.entity_id');
    $sql_query->condition('nfer.field_earlywarning_release_value', $release_id, '=');
    $sql_query->condition('n.type', 'early_warnings', '=');
    $sql_query->orderBy('created', 'DESC');

    $sql = $sql_query->execute()->fetchAll();
    $responses = 0;

    foreach ($sql as $lastposting_info) {
      $posted_date = $lastposting_info->created;
      $node_ids[] = $lastposting_info->nid;
      if ($responses == 0) {
        $author = user_load($lastposting_info->uid);
        $lastpost = date('d.m.Y', $lastposting_info->created) . ' ' . t('by') . ' ' . $author->user_firstname . ' ' . $author->user_lastname;
      }
      $responses++;
    }
    if (isset($node_ids)) {
      $comments = self::earlywarnings_lastposting($node_ids);
      if (!empty($comments)) {
        $author = user_load($comments['uid']);
        $lastpost = date('d.m.Y', $comments['last_posted']) . ' ' . t('by') . ' ' . $author->user_firstname . ' ' . $author->user_lastname;
      }
    }
    return array('warnings' => $responses, 'lastpost' => $lastpost);
  }

  /**
   *
   */
  public function earlywarnings_lastposting($earlywarnings_nid) {
    $nids = implode(',', $earlywarnings_nid);

    // $resonses_sql_query = db_query("select nid, uid, timestamp from {comments} WHERE nid in (%s) order by timestamp DESC limit 1", $nids);.
    $resonses_sql_query = db_select('comment', 'c');
    $resonses_sql_query->Fields('c', array('nid', 'uid', 'timestamp'));
    $resonses_sql_query->condition('nid', $nids, 'IN');
    $resonses_sql_query->orderBy('timestamp', 'DESC');
    $resonses_sql_query->range(1);
    $resonses_sql = $resonses_sql_query->execute()->fetchAll();
    foreach ($resonses_sql as $responses_info) {
      $responses['uid'] = $responses_info->uid;
      $responses['last_posted'] = $responses_info->timestamp;
    }

    return $responses;
  }

  /**
   * Display early warning text on view warly warnings page.
   */
  static public function early_warning_text() {
    $create_icon_path = drupal_get_path('module', 'hzd_release_management') . '/images/create-icon.png';
    $create_icon = "<img height=15 src = '/" . $create_icon_path . "'>";
    $body = db_query("SELECT body_value FROM {node__body} WHERE entity_id = :eid", array(":eid" => EARLYWARNING_TEXT))->fetchField();
    $output = "<div class = 'earlywarnings_text'>" . $body . "<a href='/release-management/add/early-warnings?\ destination=group/32/early-warnings&amp;ser=0&amp;rel=0' title='" . t("Add an Early Warning for this release") . "'>" . $create_icon . "</a></div>";
    $build['#markup'] = $output;
    return $build;
  }

}
