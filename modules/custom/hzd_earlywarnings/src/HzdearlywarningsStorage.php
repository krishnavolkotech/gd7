<?php

namespace Drupal\hzd_earlywarnings;

use Drupal\Core\Url;
use Drupal\cust_group\CustGroupHelper;

/**
 * Use Drupal\node\Entity\Node;
 * use Drupal\user\PrivateTempStoreFactory;
 * use Drupal\Core\Url;
 * use Drupal\Core\Database\Query\Condition;.
 */
class HzdearlywarningsStorage
{
    
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
        } else {
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
        $query = \Drupal::database()->select('node_field_data', 'nfd');
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
        $group_sql = \Drupal::database()->select('$query_result', 'sep');
        $group_sql->Fields('dep', array('nid', 'title', 'created', 'uid', 'service', 'release_id'));
        $group_sql->groupBy('dep.release_id');
        $group_sql->orderBy('dep.created', 'DESC');
        
        // $count_query = " SELECT count(*) FROM (" . $group_sql . ") count";.
        /**
         * if ($page_limit) {
         * $earlywarnings_query = pager_query($group_sql, $page_limit, 0 , $count_query);
         * }
         * else {
         * $earlywarnings_query = \Drupal::database()->query($group_sql, $page_limit);
         * }
         */
        if ($page_limit) {
            // $earlywarnings_query = pager_query($sql, $page_limit, 0 );.
            $pager = $group_sql->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($page_limit);
            $earlywarnings_query = $pager->execute()->fetchAll();
        } else {
            $earlywarnings_query = $group_sql->execute()->fetchAll();
            // $earlywarnings_query = \Drupal::database()->query($sql, $page_limit, 0 , $count_query);.
        }
        // While ($earlywarnings = db_fetch_array($earlywarnings_query)) {.
        foreach ($earlywarnings_query as $earlywarnings) {
            if ($earlywarnings->release_id) {
                $warnings_lastpost = self::get_early_warning_lastposting_count($earlywarnings->release_id);
                $warning_imgpath = drupal_get_path('module', 'release_management') . "/images/icon.png";
                $warning_icon = "<img src = '/" . $warning_imgpath . "'>";
                $warningclass = ($warnings_lastpost['warnings'] >= 10 ? 'warningcount_second' : 'warningcount');
                
                $title_query = \Drupal::database()->select('node_field_data', 'nfd')->Fields('nfd', array('title'))->condition('nid', $earlywarnings->release_id, '=')->execute()->fetchCol();
                
                /**
                 * \Drupal::database()->result(\Drupal::database()->query("SELECT count(*) FROM {comments} c, {content_field_earlywarning_release} ctew WHERE c.nid =  ctew.nid and ctew.field_earlywarning_release_value = %d", $earlywarnings->release_id))
                 */
                $comment_count_query = \Drupal::database()->select('comment', 'c');
                $comment_count_query->addField('*');
                $comment_count_query->join('node__field_earlywarning_release', 'nfer', 'c.nid =  nfer.entity_id');
                $comment_count_query->condition('nfer.field_earlywarning_release_value', $earlywarnings->release_id, '=');
                $comment_count_query->execute->fetchCol();
                $elements = array(
                    // db_result(\Drupal::database()->query("SELECT title FROM {node} where nid = %d", $earlywarnings->release_id))
                    array('data' => $title_query['title'], 'class' => 'releases-cell'),
                    array('data' => ($warnings_lastpost['warnings'] ? l("<span class = '" .
                        $warningclass . "'>" . $warnings_lastpost['warnings'] . "</span>",
                        "group/" . $group_id . "/early-warnings", array(
                            'attributes' => array(
                                'alt' => t('Read Early Warnings for this release'),
                                'class' => 'view-earlywarning',
                                'title' => t('Read Early Warnings for this release')
                            ),
                            'html' => TRUE,
                            'query' => 'services=' . $earlywarnings->service .
                                '&type=released' .
                                '&releases=' . $earlywarnings->release_id .
                                '&release_type=' . $release_type
                        )
                    ) : ''), 'class' => 'earlywarnings-cell'),
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
    static public function view_earlywarnings_display_table($release_type = KONSONS) {
        $output = array();
        $filter_value = HzdearlywarningsStorage::get_earlywarning_filters();
        $page_limit = 20;
        if (isset($filter_value['limit'])) {
            $page_limit = (isset($filter_value['limit']) ? $filter_value['limit'] : 20);
        }
//        pr($page_limit);exit;
        $earlywarnings = array(
            'data' => t('Early Warnings'),
            'class' => 'early-warningslink-hdr'
        );
        $date = array('data' => t('Created On'), 'class' => 'date-hdr');
        $responses = array('data' => t('Responses'), 'class' => 'responses-hdr');
        $lastcomment = array('data' => t('Last Comment'), 'class' => 'last-comment-hdr');
        $header = array($earlywarnings, $date, $responses, $lastcomment);
        $earlywarnings_nids = \Drupal::entityQuery('node')
            ->condition('type', 'early_warnings', '=');
        
        if (!empty($filter_value)) {
//      $earlywarnings_nids->condition('release_type_target_id', 
//          $filter_value['release_type'], '=');
//
            if ($filter_value['services'] && $filter_value['services'] != 0) {
                $earlywarnings_nids->condition('field_release_service',
                    $filter_value['services'], '=');
            }
            
            if ($filter_value['releases'] && $filter_value['releases'] != 0) {
                $earlywarnings_nids->condition('field_earlywarning_release',
                    $filter_value['releases'], '=');
            }
//  @to do 
            if ($filter_value['filter_startdate']) {
                // $startdate_info = explode('.', $filter_options['startdate']);
                // $startdate = mktime(0, 0, 0, $startdate_info[1], $startdate_info[0], $startdate_info[2]);.
                $startdate = strtotime($filter_value['filter_startdate']);
                $earlywarnings_nids->condition('created', $startdate, '>');
            }
            if ($filter_value['filter_enddate']) {
                // $enddate_info = explode('.', $filter_options['enddate']);
                // $enddate = mktime(23, 59, 59, $enddate_info[1], $enddate_info[0], $enddate_info[2]);.
                $enddate = strtotime($filter_value['filter_enddate']);
                $earlywarnings_nids->condition('created',
                    array($startdate ? $startdate : 0, $enddate), 'between');
            }
        }
        $earlywarnings_nids->sort('created', 'DESC');
        
        // Echo '<pre>'; print_r($query->conditions()); exit;.
        if (isset($page_limit) && $page_limit != 'all') {
            $result = $earlywarnings_nids->pager($page_limit)->execute();
        } else {
            $result = $earlywarnings_nids->execute();
        }
        $rows = [];
        foreach ($result as $earlywarnings_nid) {
            $earlywarning = \Drupal\node\Entity\Node::load($earlywarnings_nid);

            $author_name = $earlywarning->getOwner()->getDisplayName();
            $total_responses = self::get_earlywarning_responses_info($earlywarning->id());
            $early_warningTitle = $earlywarning->toLink();
            $elements = array(
                array('data' => $early_warningTitle, 'class' => 'earlywarningslink-cell'),
                array('data' => t('@date by @username',['@date' => date('d.m.Y', $earlywarning->created->value), '@username' => $author_name]),
                    'class' => 'created-cell'),
                array('data' => $total_responses['total_responses'], 'class' => 'responses-cell'),
                array('data' => $total_responses['response_lastposted'], 'class' => 'lastpostdate-cell'),
            );
            $rows[] = $elements;
        }
        
        /*if (count($rows) == 0) {
            $output[]['#markup'] = t('<div id="no-result"> No Data to be displayed </div>');
            return $output;
        }*/
        
        $output['earlywarnings'] = array(
            '#theme' => 'table',
            '#header' => $header,
            '#rows' => $rows,
            '#empty' => t('No Data Created Yet'),
            '#attributes' => ['id' => "viewearlywarnings_sortable", 'class' => "tablesorter"],
            '#cache'=>['tags'=>['node_list', 'earlywarning_list']],
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
        $total_responses = array();
        $response_lastposted = '';
        
        $total_responses = \Drupal::entityQuery('comment')
            ->condition('entity_id', $earlywarnings_nid, '=')
            ->execute();
//     dpm($total_responses);
        $resonses_cid = \Drupal::entityQuery('comment')
            ->condition('entity_id', $earlywarnings_nid, '=')
            ->condition('entity_type', 'node')
            ->sort('cid', 'DESC')
            ->range(0, 1)
            ->execute();
//        pr($resonses_cid);
//        exit;
        if (!empty($resonses_cid)) {
            $last_comment = \Drupal\comment\Entity\Comment::load(reset($resonses_cid));

//    foreach ($resonses_cids as $resonses_cid) {
            
            $responses['uid'] = $last_comment->uid->value;
            $responses['last_posted'] = date('d.m.Y', $last_comment->created->value);
            if ($responses['last_posted']) {
                $user_query = \Drupal::database()->select('cust_profile', 'cp');
                $user_query->condition('cp.uid', $last_comment->getOwnerId(), '=')
                    ->fields('cp', array('firstname', 'lastname'));
                $author = $user_query->execute()->fetchAssoc();
//        dpm($author);
        
//                $response_lastposted = $responses['last_posted'] .
//                    ' ' . t('by') . ' ' . $author['firstname'] . ' ' . $author['lastname'];
                $response_lastposted = t('@date by @firstname @lastname',['@firstname'=>$author['firstname'], '@lastname'=>$author['lastname'],'@date'=>$responses['last_posted']]);
            }
        }
        
        
        $response_info = array(
            'total_responses' => count($total_responses),
            'response_lastposted' => $response_lastposted
        );
        return $response_info;
    }
    
    /**
     * Returns last posting warnings count,responses.
     */
    public function get_early_warning_lastposting_count($release_id) {
        
        // $sql = \Drupal::database()->query("SELECT n.nid, n.uid, n.created  FROM {node} n , {content_field_earlywarning_release} ctew   WHERE n.nid = ctew.nid and field_earlywarning_release_value = %d and n.type = '%s' order by created DESC", $release_id, 'early_warnings');.
        $sql_query = \Drupal::database()->select('node', 'n');
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
        
        // $resonses_sql_query = \Drupal::database()->query("select nid, uid, timestamp from {comments} WHERE nid in (%s) order by timestamp DESC limit 1", $nids);.
        $resonses_sql_query = \Drupal::database()->select('comment', 'c');
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
//        $create_icon_path = drupal_get_path('module', 'hzd_release_management') . '/images/create-icon.png';
//        $create_icon = "<img height=15 src = '/" . $create_icon_path . "'>";
        $body = \Drupal::database()->query("SELECT body_value FROM {node__body} WHERE entity_id = :eid", array(":eid" => EARLYWARNING_TEXT))->fetchField();
//        $url = Url::fromRoute('hzd_earlywarnings.add_early_warnings', ['group' => RELEASE_MANAGEMENT]);
//        $link = \Drupal::service('link_generator')->generate(t($create_icon), $url->setOptions(['query' => ['destination' => 'group/' . RELEASE_MANAGEMENT . '/early-warnings']]));
        $output = "<div class = 'earlywarnings_text'>" . $body;
        //$output = "<div class = 'earlywarnings_text'>" . $body .
        //"<a href='/release-management/add/early-warnings?\
        //destination=group/32/early-warnings&amp;services=0&amp;releases=0'
        //title='" . t("Add an Early Warning for this release") . "'>" .
        //$create_icon . "</a></div>";
        $build['#markup'] = $output;
        return $build;
    }
    
    /*
     * @return array
     * all parameters fetched from url
     */
    static public function get_earlywarning_filters() {
        $parameters = array();
        $request = \Drupal::request()->query;
        $parameters['release_type'] = $request->get('release_type');
        $parameters['services'] = $request->get('services');
        $parameters['releases'] = $request->get('releases');
        $parameters['filter_startdate'] = $request->get('filter_startdate');
        $parameters['filter_enddate'] = $request->get('filter_enddate');
        $parameters['limit'] = $request->get('limit');
        return $parameters;
    }
}
