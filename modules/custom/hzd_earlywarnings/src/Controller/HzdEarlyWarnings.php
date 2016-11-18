<?php

namespace Drupal\hzd_earlywarnings\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\hzd_earlywarnings\HzdearlywarningsStorage;
use Drupal\hzd_release_management\HzdreleasemanagementHelper;
use Drupal\Core\Url;

/**
 * For Release Specific Early Warnings create page and 
 * replace the NODEID   with the new id as shown below 
 * define('EARLYWARNING_TEXT', NODEID);
 */
define('EARLYWARNING_TEXT', 11217);
if (!defined('KONSONS')) {
  define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));
}
define('DISPLAY_LIMIT', 20);

/**
 * Class HzdEarlyWarnings.
 *
 * @package Drupal\hzd_earlywarnings\Controller
 */
class HzdEarlyWarnings extends ControllerBase {

  /**
   * Const KONSONS = 459;.
   */
  public function view_early_warnings() {
    // drupal_add_css(drupal_get_path('module', 'downtimes') . '/downtimes.css');
    // drupal_add_js(array('type' => 'public'), 'setting');
    // drupal_add_js(drupal_get_path('module', 'hzd_customizations') . '/jquery.tablesorter.min.js');
    // drupal_add_js(drupal_get_path('module', 'earlywarnings') . '/earlywarnings.js');.
    $output['content']['#attached']['library'] = array('hzd_customizations/hzd_customizations',
      'downtimes/downtimes', 'hzd_earlywarnings/hzd_earlywarnings',
    );

    $output['content']['pretext'] = HzdearlywarningsStorage::early_warning_text();
    $output['content']['#prefix'] = '<div id = "earlywarnings_results_wrapper">';

    $request = \Drupal::request();
    $page = \Drupal::request()->query->get('page');
    // ser=383&rel=51341&type=released.
    $service = \Drupal::request()->query->get('ser');
    $release = \Drupal::request()->query->get('rel');
    $type = \Drupal::request()->query->get('type');
    $rel_type = \Drupal::request()->query->get('rel_type');
    // Echo '<pre>'; print_r($_SESSION['earlywarning_filter_option']); exit;.
    if (!isset($page)) {
      unset($_SESSION['earlywarning_filter_option']);
    }

    if (isset($service) && isset($release) && isset($type)) {
      $_SESSION['earlywarning_filter_option']['service'] = $service;
      $_SESSION['earlywarning_filter_option']['release'] = $release;
      $_SESSION['earlywarning_filter_option']['type'] = $type;
      $_SESSION['earlywarning_filter_option']['release_type'] = $rel_type;
      $_SESSION['earlywarning_filter_option']['startdate'] = '';
      $_SESSION['earlywarning_filter_option']['enddate'] = '';
      $_SESSION['earlywarning_filter_option']['limit'] = 20;
      $output['content']['earlywarnings_filter_form'] = \Drupal::formBuilder()->getForm('Drupal\hzd_earlywarnings\Form\EarlyWarningsFilterForm', $type);
    }
    else {
      $output['content']['earlywarnings_filter_form'] = \Drupal::formBuilder()->getForm('Drupal\hzd_earlywarnings\Form\EarlyWarningsFilterForm');
    }

    $output['content']['earlywarnings_filter_table'] = HzdearlywarningsStorage::view_earlywarnings_display_table();
    $output['content']['#suffix'] = '</div>';
    return $output;
  }

  // display releases earlywarnings
  /* public function view_earlywarnings_display_table($filter_options = NULL, $release_type = KONSONS) {
    $limit = 10;
    $earlywarnings = array('data' => t('Early Warnings'), 'class' => 'early-warningslink-hdr');
    $date = array('data' => t('Created On'), 'class' => 'date-hdr');
    $responses = array('data' => t('Responses'), 'class' => 'responses-hdr');
    $lastcomment = array('data' => t('Last Comment'), 'class' => 'last-comment-hdr');
    $header = array($earlywarnings, $date, $responses, $lastcomment);
    0
    $query = db_select('node_field_data', 'n');
    $query->join('node__field_release_service', 'nfrs', 'n.nid = nfrs.entity_id');
    $query->join('node__field_earlywarning_release', 'nfer', 'n.nid = nfer.entity_id');
    $query->join('node__release_type', 'nrt', 'nfrs.field_release_service_value = nrt.entity_id');
    $query->condition('n.type', 'early_warnings', '=');
    if(\Drupal::request()->get('rel_type')) {
    $release_type = \Drupal::request()->get('rel_type');
    }

    $service = \Drupal::request()->get('ser');
    $release = \Drupal::request()->get('rel');
    if($service && $release) {
    $query->condition('nfrs.field_release_service_value', $service, '=')
    ->condition('nfer.field_earlywarning_release_value', $release, '=')
    ->condition('nrt.release_type_target_id', $release_type, '=');
    }
    elseif(isset($filter_options)) {
    // need to write the code after passing the values in table
    }

    $count_query = clone $query;
    $count_query->addExpression('COUNT(DISTINCT n.nid)');

    $paged_query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $paged_query->limit($limit);
    $paged_query->setCountQuery($count_query);

    $paged_query->fields('n', array('nid', 'title', 'created', 'uid'))
    ->orderBy('n.created', 'DESC');
    $result = $paged_query->execute()->fetchAll();

    foreach($result as $vals) {
    $user_query = db_select('cust_profile', 'cp');
    $user_query->condition('cp.uid', $vals->uid, '=')
    ->fields('cp', array('firstname', 'lastname'));
    $author = $user_query->execute()->fetchAll();
    $author_name = $author[0]->firstname . ' ' . $author[0]->lastname;
    $total_responses = $this->get_earlywarning_responses_info($vals->nid);

    $elements = array(
    array('data' => $vals->title, 'class' => 'earlywarningslink-cell'),
    array('data' => date('d.m.Y', $vals->created) . ' ' . t('by') . ' ' . $author_name, 'class' => 'created-cell'),
    array('data' => $total_responses['total_responses'], 'class' => 'responses-cell'),
    array('data' => $total_responses['response_lastposted'], 'class' => 'lastpostdate-cell'),
    );
    $rows[] = $elements;
    }
    if(count($rows) == 0) {
    return $output = t('No Data to be displayed');
    }

    $output['earlywarnings'] = array(
    '#theme' => 'table',
    '#rows' => $rows,
    '#header' => $header,
    );

    $output['pager'] = array(
    '#type' => 'pager',
    '#quantity' => 5,
    '#prefix' => '<div id="pagination">',
    '#suffix' => '</div>',
    );
    return $output;
    } */

  /**
   * Get early warning responses info.
   */
  public function get_earlywarning_responses_info($earlywarnings_nid) {
    $total_responses = db_query("SELECT COUNT(*) FROM {comment_field_data} WHERE entity_id = :nid", array(":nid" => $earlywarnings_nid))->fetchField();
    $resonses_sql = db_query("SELECT entity_id, uid, created FROM {comment_field_data} WHERE entity_id = :eid ORDER BY created DESC limit 1", array(":eid" => $earlywarnings_nid))->fetchAll();
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
   *
   */
  public function add_early_warnings() {
    // Replace this with the node type in which we need to display the form for.
    $type = node_type_load("early_warnings");
    $samplenode = $this->entityManager()->getStorage('node')->create(array(
      'type' => $type->id(),
    ));
    $node_create_form = $this->entityFormBuilder()->getForm($samplenode);

    return array(
      '#type' => 'markup',
      '#markup' => render($node_create_form),
    );
  }

  function release_early_warnings_display() {
    
    if (!isset($page)) {
      unset($_SESSION['earlywarning_filter_option']);
    }
    
    $type = 'releaseWarnings';
    $user_role = get_user_role();
    $group = \Drupal::routeMatch()->getParameter('group');
    if (is_object($group)) {
      $group_id = $group->id();
    }
    else {
      $group_id = $group;
    }
    $output['content']['#attached']['library'] = array(
      'hzd_customizations/hzd_customizations',
      'downtimes/downtimes',
      'hzd_earlywarnings/hzd_earlywarnings',
    );

    $output['content']['#attached']['drupalSettings']['group_id'] = $group_id;
    $output['content']['#attached']['drupalSettings']['type'] = $type;

    $node = \Drupal\node\Entity\Node::load(EARLYWARNING_TEXT);
    $group = \Drupal\group\Entity\Group::load($group_id);
    $create_icon_path = drupal_get_path('module', 'hzd_release_management') . '/images/create-icon.png';
    $create_icon = "<img height=15 src = '/" . $create_icon_path . "'>";
    $is_member = $group->getMember(\Drupal::service('current_user'));

    if ($is_member || in_array($user_role, array('site_administrator'))) {
      $output['content']['pretext']['#prefix'] = "<div class = 'earlywarnings_text'>";
      $output['content']['pretext']['#markup'] = t($node->body->value);
      $output['content']['pretext']['#suffix'] = "<a href='/release-management/add/early-warnings?\ destination=node/32/early-warnings&amp;ser=0&amp;rel=0' title='" . t("Add an Early Warning for this release") . "'>" . $create_icon . "</a></div>";
    }
    else {
      $output['content']['pretext']['#prefix'] = "<div class = 'earlywarnings_text'>";
      $output['content']['pretext']['#markup'] = t($node->body->value);
      $output['content']['pretext']['#suffix'] = "<a href='/release-management/add/early-warnings?\ destination=node/32/early-warnings&amp;ser=0&amp;rel=0' title='" . t("Add an Early Warning for this release") . "'>" . $create_icon . "</a></div>";
    }

    $output['content']['table_header']['#markup'] = '<h2>' . t('Current Early Warnings') . '</h2>';
    $output['content']['filter_form']['#prefix'] = "<div class = 'specific_earlywarnings'>";
    $output['content']['filter_form']['filter_form_wrapper']['#markup'] = "<div id = 'earlywarnings_results_wrapper'>";
    

    if (isset($service) && isset($release) && isset($type)) {
      $_SESSION['earlywarning_filter_option']['service'] = $service;
      $_SESSION['earlywarning_filter_option']['release'] = $release;
      $_SESSION['earlywarning_filter_option']['type'] = $type;
      $_SESSION['earlywarning_filter_option']['release_type'] = $rel_type;
      $_SESSION['earlywarning_filter_option']['startdate'] = '';
      $_SESSION['earlywarning_filter_option']['enddate'] = '';
      $_SESSION['earlywarning_filter_option']['limit'] = 20;
      $output['content']['earlywarnings_filter_form'] = \Drupal::formBuilder()->getForm('Drupal\hzd_earlywarnings\Form\EarlyWarningsFilterForm', $type);
    }
    else {
      $output['content']['earlywarnings_filter_form'] = \Drupal::formBuilder()->getForm('Drupal\hzd_earlywarnings\Form\EarlyWarningsFilterForm', $type);
    }

    $output['content']['filter_form']['reset_form']['#prefix'] = "<div class = 'reset_form'>";
    $output['content']['filter_form']['reset_form']['reset_button'] = HzdreleasemanagementHelper::releases_reset_element();
    $output['content']['filter_form']['reset_form']['#suffix'] = "<div class = 'reset_form'>";
    $output['content']['filter_form']['clear']['#markup'] = "<div style = 'clear:both'></div>";

    $output['content']['table']['#prefix'] = "<div class = 'view_earlywarnings_output'>";
    $output['content']['table'][] = HzdEarlyWarnings::release_earlywarnings_display_table();
    $output['content']['table']['#suffix'] = "</div></div></div>";

    return $output;
  }

  /**
   * @filter_options:filtering options for filtering early warnings
   * @return:displays the early warnings in the table format. 
   * Display table for the release overview of early warnings
   * In the display the responses field contains the count of comments posted on the early warning.
   */
  static public function release_earlywarnings_display_table($filter_options = NULL, $release_type = KONSONS) {
    if ($filter_options['limit'] != 'all') {
      $page_limit = isset($filter_options['limit']) ? $filter_options['limit'] : DISPLAY_LIMIT;
    }
    $release = array(
      'data' => t('Release'), 
      'class' => 'release-hdr'
      );
    $earlywarnings = array(
      'data' => t('Early Warnings'), 
      'class' => 'early-warnings-hdr'
      );
    $responses = array(
      'data' => t('Responses'), 
      'class' => 'responses-hdr'
      );
    $lastposting = array(
      'data' => t('Last Posting'), 
      'class' => 'last-posting-hdr'
      );
    $header = array($release, $earlywarnings, $responses, $lastposting);

    $sql_select = \Drupal::database()->select('node_field_data', 'nfd');
    $sql_select->join('node__field_release_service', 'nfrs', 'nfd.nid = nfrs.entity_id');
    $sql_select->join('node__field_earlywarning_release', 'nfer', 'nfer.entity_id = nfrs.entity_id');
    $sql_select->join('node__release_type', 'nrt', 'nfrs.field_release_service_value = nrt.entity_id');
    $sql_select->addExpression('nfer.field_earlywarning_release_value', 'release_id');   
    $sql_select->distinct('true');
    $sql_select->condition('nfd.type', 'early_warnings', '=');


    if (isset($filter_options)) {
      $sql_select->condition('nrt.release_type_target_id', $filter_options['release_type'], '=');
      if ($filter_options['service']) {
        $sql_select->condition('field_release_service_value', $filter_options['service'], '=');
      }
      if ($filter_options['release']) {
        $sql_select->condition('field_earlywarning_release_value', $filter_options['release'], '=');
      }
      if ($filter_options['startdate']) {
        $startdate_info = explode('.', $filter_options['startdate']);
        $startdate = mktime(0, 0, 0, $startdate_info[1], $startdate_info[0], $startdate_info[2]);
        $sql_select->condition('created', $startdate, '>');
      }
      if ($filter_options['enddate']) {
        $enddate_info = explode('.', $filter_options['enddate']);
        $enddate = mktime(23, 59, 59, $enddate_info[1], $enddate_info[0], $enddate_info[2]);
        $sql_select->condition('created', array(($startdate ? $startdate : 0), $enddate), 'between');
      }
    }
     $sql_select->groupBy('release_id');
    $sql_select->orderBy('nfd.created', 'DESC');
    if (!$page_limit) {
      $result = $sql_select->execute()->fetchAll();
    }
    else {
      $page_limit = ($page_limit ? $page_limit : DISPLAY_LIMIT);
      $pager = $sql_select->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($page_limit);
      $result = $pager->execute()->fetchAll();
    }
    foreach ($result as $earlywarning) {
      if ($earlywarning->release_id) {
        $warnings_lastpost = HzdEarlyWarnings::get_early_warning_lastposting_count($earlywarning->release_id);
        $warningclass = ($warnings_lastpost['warnings'] >= 10 ? 'warningcount_second' : 'warningcount');

        $sql_select = \Drupal::database()->select('node_field_data', 'nfd');
        $sql_select->addfield('nfd', 'title');
        $sql_select->condition('nfd.nid', $earlywarning->release_id, '=');
        $relase_title = $sql_select->execute()->fetchField();

        $sql_select = \Drupal::database()->select('comment_field_data', 'cfd');
        $sql_select->addfield('cfd', 'cid');
        $sql_select->leftJoin('node__field_earlywarning_release', 'nfer', 'cfd.entity_id = nfer.entity_id');
        $sql_select->condition('nfer.field_earlywarning_release_value', $earlywarning->release_id, '=');
        $sql_select->orderBy('cfd.created', 'DESC');
        $comments_count = $sql_select->countQuery()->execute()->fetchField();
        $options = null;
        $earlywarining_view_link = "<span class = '" . $warningclass . "'>" . $warnings_lastpost['warnings'] . "</span>";
        
        $release = \Drupal\node\Entity\Node::load($earlywarning->release_id);
        $options['query'][] = array(
          'ser' => $release->field_relese_services['0']->target_id,
          'rel' => $earlywarning->release_id,
          'type' => 'released',
          'rel_type' => $release_type
        );
        $options['attributes'] = array(
          'alt' => t('Read Early Warnings for this release'),
          'class' => 'view-earlywarning',
          'title' => t('Read Early Warnings for this release')
        );

        $group = \Drupal::routeMatch()->getParameter('group');
        if (is_object($group)) {
          $group_id = $group->id();
        }
        else {
          $group_id = $group;
        }

        $url = Url::fromRoute(
                'hzd_earlywarnings.view_early_warnings', array(
              'group' => $group_id
                ), $options
        );

        $earlywarining_link = \Drupal::service('link_generator')
            ->generate(t($earlywarining_view_link), $url);
        $elements = array(
          array('data' => $relase_title, 'class' => 'releases-cell'),
          array('data' => isset($warnings_lastpost['warnings']) ? $earlywarining_link : '', 'class' => 'earlywarnings-cell'),
          array('data' => $comments_count, 'class' => 'responses-cell'),
          array('data' => $warnings_lastpost['lastpost'], 'class' => 'lastpostdate-cell'),
        );

        $rows[] = $elements;
      }
    }

    $output['release_early_warning_table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No Data Created Yet'),
      '#attributes' => ['id' => "earlywarnings_release_sortable", 'class' => "tablesorter"],
    );

    $output['pager'] = array(
      '#type' => 'pager',
      '#prefix' => '<div id="pagination">',
      '#suffix' => '</div>',
    );

    return $output;
  }

  /*
   * Returns last posting warnings count,responses 
   */

  static public function get_early_warning_lastposting_count($release_id) {
    $sql_select = \Drupal::database()->select('node_field_data', 'nfd');
    $sql_select->Fields('nfd', array('nid', 'created', 'uid'));
    $sql_select->leftJoin('node__field_earlywarning_release', 'nfer', 'nfd.nid = nfer.entity_id');
    $sql_select->condition('nfer.field_earlywarning_release_value', $release_id, '=');
    $sql_select->condition('nfd.type', 'early_warnings', '=');
    $sql_select->orderBy('nfd.created', 'DESC');
    $lastposting_infos = $sql_select->execute()->fetchAll();
    $responses = 0;
    foreach ($lastposting_infos as $lastposting_info) {
      $posted_date = $lastposting_info->created;
      $node_ids[] = $lastposting_info->nid;
      if ($responses == 0) {
        $user_query = \Drupal::database()->select('cust_profile', 'cp');
        $user_query->condition('cp.uid', $lastposting_info->uid, '=')
            ->fields('cp', array('firstname', 'lastname'));
        $author = $user_query->execute()->fetchAll();
        $lastpost = date('d.m.Y', $lastposting_info->created) . ' ' . t('by') . ' ' . $author['0']->firstname . ' ' . $author['0']->lastname;
      }
      $responses++;
    }
    if (isset($node_ids)) {
      $comments = HzdEarlyWarnings::earlywarnings_lastposting($node_ids);
      if (!empty($comments)) {
        $user_query = \Drupal::database()->select('cust_profile', 'cp');
        $user_query->condition('cp.uid', $comments['uid'], '=')
            ->fields('cp', array('firstname', 'lastname'));
        $author = $user_query->execute()->fetchAll();
        $lastpost = date('d.m.Y', $comments['last_posted']) . ' ' . t('by') . ' ' . $author['0']->firstname . ' ' . $author['0']->lastname;
      }
    }
    return array('warnings' => $responses, 'lastpost' => $lastpost);
  }

  static public function earlywarnings_lastposting($earlywarnings_nid) {
    $responses = array();
    $resonses_sql = \Drupal::database()->select('comment_field_data', 'cfd');
    $resonses_sql->Fields('cfd', array('entity_id', 'uid', 'created'));
    $resonses_sql->condition('entity_id', $earlywarnings_nid, 'IN');
    $resonses_sql->orderBy('cfd.created', 'DESC');
    $resonses_sql->range('0', '1');
    $responses_infos = $resonses_sql->execute()->fetchAll();

    foreach ($responses_infos as $responses_info) {
      $responses['uid'] = $responses_info->uid;
      $responses['last_posted'] = $responses_info->created;
    }

    return $responses;
  }

}
