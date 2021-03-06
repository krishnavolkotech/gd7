<?php

namespace Drupal\hzd_earlywarnings\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\hzd_earlywarnings\HzdearlywarningsStorage;
use Drupal\hzd_release_management\HzdreleasemanagementHelper;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\Component\Datetime\DateTimePlus;

/**
 * For Release Specific Early Warnings create page and
 * replace the NODEID   with the new id as shown below
 * define('EARLYWARNING_TEXT', NODEID);
 */
define('EARLYWARNING_TEXT', 11217);
if (!defined('KONSONS')) {
  define('KONSONS', \Drupal::config('hzd_release_management.settings')
    ->get('konsens_service_term_id'));
}

if (!defined('DISPLAY_LIMIT')) {
  define('DISPLAY_LIMIT', 20);
}



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
    
    $output['content']['#attached']['library'] = array(
//      'hzd_customizations/hzd_customizations',
//      'downtimes/downtimes',
      'hzd_earlywarnings/hzd_earlywarnings',
    );
    
    $output['content']['pretext'] = HzdearlywarningsStorage::early_warning_text();
    $output['content']['#prefix'] = '<div id = "earlywarnings_results_wrapper">';
    $output['content']['earlywarnings_filter_form'] = \Drupal::formBuilder()
      ->getForm('Drupal\hzd_earlywarnings\Form\EarlyWarningsFilterForm');
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
    $query = \Drupal::database()->select('node_field_data', 'n');
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
    $user_query = \Drupal::database()->select('cust_profile', 'cp');
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
    $total_responses = \Drupal::database()->query("SELECT COUNT(*) FROM {comment_field_data} WHERE entity_id = :nid", array(":nid" => $earlywarnings_nid))->fetchField();
    $resonses_sql = \Drupal::database()->query("SELECT entity_id, uid, created FROM {comment_field_data} WHERE entity_id = :eid ORDER BY created DESC limit 1", array(":eid" => $earlywarnings_nid))->fetchAll();
    foreach ($resonses_sql as $vals) {
      $responses['uid'] = $vals->uid;
      $responses['last_posted'] = date('d.m.Y', $vals->created);
      if ($responses['last_posted']) {
        $user_query = \Drupal::database()->select('cust_profile', 'cp');
        $user_query->condition('cp.uid', $vals->uid, '=')
          ->fields('cp', array('firstname', 'lastname'));
        $author = $user_query->execute()->fetchAll();
        $response_lastposted = $responses['last_posted'] . ' ' . t('by') . ' ' . $author[0]->firstname . ' ' . $author[0]->lastname;
      } else {
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
    //$type = node_type_load("early_warnings");
    $type = \Drupal\node\Entity\NodeType::load("early_warnings");
    $samplenode = $this->entityTypeManager()->getStorage('node')->create(array(
      'type' => $type->id(),
    ));
    $node_create_form = $this->entityFormBuilder()->getForm($samplenode);
    
    return $node_create_form;
  }
  
  function release_early_warnings_display($group) {
    $type = 'releaseWarnings';
    $user_role = get_user_role();
    $group_id = $group->id();
    $output['content']['#attached']['library'] = array(
//      'hzd_customizations/hzd_customizations',
//      'downtimes/downtimes',
//      'hzd_release_management/hzd_release_management',
      'hzd_earlywarnings/hzd_earlywarnings',
    );
    
    $output['content']['#attached']['drupalSettings']['group_id'] = $group_id;
    $output['content']['#attached']['drupalSettings']['type'] = $type;

    //$node = \Drupal\node\Entity\Node::load(EARLYWARNING_TEXT);
    $node_body = Markup::create(node_get_field_data_fast([EARLYWARNING_TEXT], 'body')[EARLYWARNING_TEXT]);
    $create_icon_path = drupal_get_path('module', 'hzd_release_management') .
      '/images/create-icon.png';
    $create_icon = "<img height=15 src = '/" . $create_icon_path . "'>";
    $is_member = $group->getMember(\Drupal::service('current_user'));

    $url = Url::fromRoute('hzd_earlywarnings.add_early_warnings', ['group' => RELEASE_MANAGEMENT]);
    $destination = Url::fromRoute('entity.group.canonical', ['group' => RELEASE_MANAGEMENT,])->toString();
    
    if ($is_member || in_array($user_role, array('site_administrator','administrator'))) {
      $output['content']['pretext']['#prefix'] = "<div class = 'earlywarnings_text'>";
      $output['content']['pretext']['body']['#markup'] = $node_body;
//      $output['content']['pretext']['#suffix'] = "<a href='" . $url . "?destination=" . $destination . "?services=0&amp;releases=0' title='" . t("Add an Early Warning for this release") . "'>" . $create_icon . "</a></div>";
      $output['content']['pretext']['body'][] = [
        '#type'=>'link',
        '#title'=>Markup::create($create_icon),
        '#url'=>$url
      ];
      $output['content']['pretext']['#suffix'] = '</div>';
//      $output['content']['pretext']['#suffix'] = "<a href='" . $url . "?destination=" . $destination . "?services=0&amp;releases=0' title='" . t("Add an Early Warning for this release") . "'>" . $create_icon . "</a></div>";
    } else {
      $output['content']['pretext']['#prefix'] = "<div class = 'earlywarnings_text'>";
      $output['content']['pretext']['#markup'] = $node_body;
      $output['content']['pretext']['#suffix'] = "<a href='" . $url . "?destination=" . $destination . "?services=0&amp;releases=0' title='" . t("Add an Early Warning for this release") . "'>" . $create_icon . "</a></div>";
    }

    $output['content']['table_header']['#markup'] = '<h2>' .
      t('Current Early Warnings') . '</h2>';
    $output['content']['filter_form']['#prefix'] = "<div class = "
      . "'specific_earlywarnings'>";
    $output['content']['filter_form']['filter_form_wrapper']['#markup'] =
      "<div id = 'earlywarnings_results_wrapper'>";

    $output['content']['earlywarnings_filter_form'] = \Drupal::formBuilder()
      ->getForm('Drupal\hzd_earlywarnings\Form\EarlyWarningsFilterForm', $type);
//    $output['content']['filter_form']['reset_form']['#prefix'] = "<div class = 'reset_form'>";
//    $output['content']['filter_form']['reset_form']['reset_button'] = HzdreleasemanagementHelper::releases_reset_element();
//    $output['content']['filter_form']['reset_form']['#suffix'] = "<div class = 'reset_form'>";
//    $output['content']['filter_form']['clear']['#markup'] = "<div style = 'clear:both'></div>";


    $output['content']['table']['#prefix'] = "<div class = 'view_earlywarnings_output'>";
    $output['content']['table'][] = self::release_earlywarnings_display_table($group);
    $output['content']['table']['#suffix'] = "</div></div></div>";
    return $output;
  }
  
  /**
   * @filter_options:filtering options for filtering early warnings
   * @return:displays the release specific early warnings in the table format.
   * Display table for the release overview of early warnings
   * In the display the responses field contains the count of comments
   * posted on the early warning.
   */
  static public function release_earlywarnings_display_table($group) {
    // pr(Node::load(60694)->get('comment_no_subject'));exit;

    $filter_value = HzdearlywarningsStorage::get_earlywarning_filters();
    $group_id = $group->id();
    $rows = array();

    if (isset($filter_value['release_type'])) {
      $default_type = $filter_value['release_type'];
    } else {
      $default_type = null;
      if (isset($group_id) && $group_id != RELEASE_MANAGEMENT) {
        $default_type = \Drupal::database()->query("SELECT release_type FROM "
          . "{default_release_type} WHERE group_id = :gid",
          array(
            ":gid" => $group_id
          )
        )->fetchField();
      }
      $default_type = $default_type ? $default_type : KONSONS;
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
    $query = \Drupal::database()->select('node_field_data','nfd');
    $query->addExpression("count(nfd.nid)",'ew_count');
    $query->addExpression("nfd.nid",'early_warning');
    $query->addExpression("max(ces.cid)",'comment_id');
//    $query->addExpression("group_concat(cfd.changed)",'last_cmt_changed');
//    $query->addExpression("group_concat(nfd.changed)",'last_node_changed');
    $query->addExpression("CASE WHEN max(cfd.changed) is not null THEN max(cfd.changed) ELSE max(nfd.changed) END",'last_changed');
    $query->fields('nfer',['field_release_ref_target_id']);
    $query->leftJoin('comment_field_data','cfd','nfd.nid = cfd.entity_id');
    $query->leftJoin('comment_entity_statistics','ces','nfd.nid = ces.entity_id');
    $query->innerJoin('node__field_release_ref','nfer','nfer.entity_id = nfd.nid');
    $query->InnerJoin('node__field_service','nfrs','nfrs.entity_id = nfd.nid');
    $query->groupBy('nfer.field_release_ref_target_id');
    // $query->groupBy('ces.entity_id');
    $query->condition('nfd.type','early_warnings');
    $query->orderBy('last_changed','desc');
    // pr($query->execute()->fetchAll());exit;
    if (isset($filter_value['services']) && $filter_value['services'] != 0) {
      $query->condition('nfrs.field_service_target_id',$filter_value['services'],'=');
    }else{
      $group_release_view_service_id_query = \Drupal::database()
        ->select('group_releases_view', 'grv');
      $group_release_view_service_id_query->fields('grv', array('service_id'));
      $group_release_view_service_id_query->condition('group_id',
        $group_id, '=');
      $group_release_view_service = $group_release_view_service_id_query
        ->execute()->fetchCol();
      if(empty($group_release_view_service)){
      $group_release_view_service = [-1];
      }
      $services = \Drupal::entityQuery('node')
        ->condition('type', 'services', '=')
        ->condition('release_type', $default_type, '=')
        ->condition('nid', (array)$group_release_view_service, 'IN')
        ->execute();
      $query->condition('nfrs.field_service_target_id', $services, 'IN');
    }
    if (isset($filter_value['releases']) && $filter_value['releases'] != 0) {
      $query->condition('nfer.field_release_ref_target_id', $filter_value['releases'], '=');
    }
    if ($filter_value['filter_startdate']) {
      $startDate = DateTimePlus::createFromFormat('d.m.Y|', $filter_value['filter_startdate'], null, ['validate_format' => FALSE])->getTimestamp();
      $query->condition('nfd.created', $startDate, '>');
    }
    if ($filter_value['filter_enddate']) {
      $endDate = DateTimePlus::createFromFormat('d.m.Y|', $filter_value['filter_enddate'], null, ['validate_format' => FALSE])->getTimestamp();
      $query->condition('nfd.created', $endDate, '<');
    }
    
    if (isset($filter_value['limit']) && $filter_value['limit'] != 'all') {
      $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');$query->limit($filter_value['limit']);
    }elseif(!isset($filter_value['limit'])){
      $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
      $query->limit(DISPLAY_LIMIT);
    }
    $results = $query->execute()->fetchAll();
    // pr($results);exit;
    foreach ($results as $key => $value) {
      $release_specifc_earlywarnings = \Drupal::entityQuery('node')
        ->condition('type', 'early_warnings', '=')
        ->condition('field_release_ref', $value->field_release_ref_target_id, '=')
        ->sort('changed', 'DESC');
      $earlywarnings_nids = $release_specifc_earlywarnings->execute();
      
      if (isset($earlywarnings_nids) && !empty($earlywarnings_nids)) {
        $release = node_get_title_fast([$value->field_release_ref_target_id])[$value->field_release_ref_target_id];
        if (count($earlywarnings_nids) >= 10) {
          $warningclass = 'warningcount_second';
        } else {
          $warningclass = 'warningcount';
        }
        
        if ($release) {
          $relase_title = $release;
        }
        
        $options = null;
        $earlywarining_view_link =
          "<span class = '" . $warningclass . "'>" .
          count($earlywarnings_nids) . "</span>";
        
        
        $options['query'][] = array(
//          'services' => $release->field_relese_services->target_id,
          'releases' => $value->field_release_ref_target_id,
          'r_type' => 'released',
          'release_type' => $default_type
        );
        $options['attributes'] = array(
          'alt' => t('Read Early Warnings for this release'),
          'class' => 'view-earlywarning',
          'title' => t('Read Early Warnings for this release')
        );
        
        $url = Url::fromRoute(
          'hzd_earlywarnings.view_early_warnings', array(
          'group' => $group_id
        ), $options
        );
        
        $earlywarining_link = \Drupal::service('link_generator')
          ->generate(t($earlywarining_view_link), $url);

        $nid = reset($earlywarnings_nids);
/*        $cids = \Drupal::entityQuery('comment')
          ->condition('entity_id', $value->early_warning)
          ->condition('entity_type', 'node')
          ->sort('changed', 'DESC')
          ->range(0,1)
          ->execute();*/
          if($value->comment_id){
            $comment = \Drupal\comment\Entity\Comment::load($value->comment_id);
            $userName = $comment->getOwner()->getDisplayName();
            $lastCreated = t('@date by @username',['@date' => date('d.m.Y', $value->last_changed), '@username' => $userName]);
          }else {
            $uid = $earlyWarningNode_tmp = node_get_entity_property_fast([$nid], 'uid')[$nid];
            $name = [];
            $userName = "";
            $fetched_name = user_get_cust_profile_fast([$uid]);
            if (key_exists($uid, $fetched_name)) {
              $name[] = $fetched_name[$uid]->firstname;
              $name[] = $fetched_name[$uid]->lastname;
              $userName = implode(" ", $name);
            }
            $lastCreated = t('@date by @username',['@date' => date('d.m.Y', $value->last_changed), '@username' => $userName]);
          }

        $commentsCount = \Drupal::database()->select('comment_entity_statistics', 'ces', $options);
          $commentsCount->addExpression('SUM(ces.comment_count)','com_cnt');
          $commentsCount->condition('ces.entity_id', $earlywarnings_nids, 'IN');
          $commentsCount->condition('ces.entity_type', 'node');
          $commentsCount = $commentsCount->execute()->fetch();
          // pr($commentsCount);
        $elements = array(
          array(
            'data' => $relase_title,
            'class' => 'releases-cell'
          ),
          array(
            'data' => $earlywarining_link,
            'class' => 'earlywarnings-cell'
          ),
          array(
            'data' => $commentsCount->com_cnt,
            'class' => 'responses-cell'
          ),
          array(
            'data' => isset($lastCreated) ?
              $lastCreated : '',
            'class' => 'lastpostdate-cell'),
        );                                                                            
        
        $rows[] = $elements;
      }
    }
    // exit;
    $output['release_early_warning_table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No Data Created Yet'),
      '#attributes' => ['id' => "earlywarnings_release_sortable", 'class' => "tablesorter"],
      '#cache'=>['tags'=>['node_list', 'earlywarning_list']],
    );
    
    $output['pager'] = array(
      '#type' => 'pager',
      '#prefix' => '<div id="pagination">',
      '#suffix' => '</div>',
      '#exclude_from_print'=>1
    );
    
    return $output;
  }
  
  /*
   * Returns last posting warnings count,responses
   */
  
  static public function get_early_warning_lastposting_count($release_id) {
    $release_specifc_earlywarnings = \Drupal::entityQuery('node')
      ->condition('type', 'early_warnings', '=')
      ->condition('field_earlywarning_release',
        $release_id, '=')
      ->execute();
    
    $lastposting_cid = \Drupal::entityQuery('comment')
      ->condition('entity_id', $release_specifc_earlywarnings, 'IN')
      ->condition('entity_type', 'node')
      ->sort('cid', 'DESC')
      ->range(0, 1)
      ->execute();
    
    if (isset($lastposting_cid) && !empty($lastposting_cid)) {
      $last_comment = \Drupal\comment\Entity\Comment::load(current($lastposting_cid));
      $user_query = \Drupal::database()->select('cust_profile', 'cp');
      $user_query->condition('cp.uid', $last_comment->uid->target_id, '=')
        ->fields('cp', array('firstname', 'lastname'));
      $author = $user_query->execute()->fetchAssoc();
      $lastpost = date('d.m.Y', $last_comment->created->value) .
        ' ' . t('by') . ' ' . $author['firstname'] . ' ' . $author['lastname'];
      return array(
        'warnings' => count($release_specifc_earlywarnings),
        'lastpost' => $lastpost
      );
    } else {
      return array();
    }
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
