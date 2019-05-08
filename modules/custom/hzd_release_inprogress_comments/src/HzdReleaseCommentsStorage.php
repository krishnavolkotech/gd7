<?php

namespace Drupal\hzd_release_inprogress_comments;


class HzdReleaseCommentsStorage {

  /**
   * @param array|mixed|null $release_type
   * @return array
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  static public function view_releasecomments_display_table($release_type = KONSONS) {
    $output = array();
    $filter_value = self::get_releasecomments_filters();
    $page_limit = 20;
    if (isset($filter_value['limit'])) {
      $page_limit = (isset($filter_value['limit']) ? $filter_value['limit'] : 20);
    }

    $earlywarnings = array(
      'data' => t('Release Comments'),
      'class' => 'early-warningslink-hdr'
    );
    $date = array('data' => t('Created On'), 'class' => 'date-hdr');
    $responses = array('data' => t('Responses'), 'class' => 'responses-hdr');
    $lastcomment = array('data' => t('Last Comment'), 'class' => 'last-comment-hdr');
    $header = array($earlywarnings, $date, $responses, $lastcomment);
    $earlywarnings_nids = \Drupal::entityQuery('node')
      ->condition('type', 'release_comments', '=');

    if (!empty($filter_value)) {
      if ($filter_value['services'] && $filter_value['services'] != 0) {
        $earlywarnings_nids->condition('field_release_service',
          $filter_value['services'], '=');
      }

      if ($filter_value['releases'] && $filter_value['releases'] != 0) {
        $earlywarnings_nids->condition('field_earlywarning_release',
          $filter_value['releases'], '=');
      }

      if ($filter_value['filter_startdate']) {
        $startdate = strtotime($filter_value['filter_startdate']);
        $earlywarnings_nids->condition('created', $startdate, '>');
      }
      if ($filter_value['filter_enddate']) {
        $enddate = strtotime($filter_value['filter_enddate']);
        $earlywarnings_nids->condition('created',
          array($startdate ? $startdate : 0, $enddate), 'between');
      }
    }
    $earlywarnings_nids->sort('created', 'DESC');

    if (isset($page_limit) && $page_limit != 'all') {
      $result = $earlywarnings_nids->pager($page_limit)->execute();
    } else {
      $result = $earlywarnings_nids->execute();
    }
    $rows = [];
    foreach ($result as $earlywarnings_nid) {
      $earlywarning = \Drupal\node\Entity\Node::load($earlywarnings_nid);

      $author_name = $earlywarning->getOwner()->getDisplayName();
      $total_responses = self::get_releasecomments_responses_info($earlywarning->id());
      $early_warningTitle = $earlywarning->toLink();
      $elements = array(
        array('data' => $early_warningTitle, 'class' => 'earlywarningslink-cell'),
        array('data' => t('@date by @username', ['@date' => date('d.m.Y', $earlywarning->created->value), '@username' => $author_name]),
          'class' => 'created-cell'),
        array('data' => $total_responses['total_responses'], 'class' => 'responses-cell'),
        array('data' => $total_responses['response_lastposted'], 'class' => 'lastpostdate-cell'),
      );
      $rows[] = $elements;
    }

    $output['earlywarnings'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No Data Created Yet'),
      '#attributes' => ['id' => "viewearlywarnings_sortable", 'class' => "tablesorter"],
      '#cache' => ['tags' => ['node_list', 'earlywarning_list']],
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
  static public function get_releasecomments_responses_info($releasecomments_nid) {
    $response_lastposted = '';
    $total_responses = \Drupal::entityQuery('comment')
      ->condition('entity_id', $releasecomments_nid, '=')
      ->execute();
    $resonses_cid = \Drupal::entityQuery('comment')
      ->condition('entity_id', $releasecomments_nid, '=')
      ->condition('entity_type', 'node')
      ->sort('cid', 'DESC')
      ->range(0, 1)
      ->execute();
    if (!empty($resonses_cid)) {
      $last_comment = \Drupal\comment\Entity\Comment::load(reset($resonses_cid));
      $responses['uid'] = $last_comment->uid->value;
      $responses['last_posted'] = date('d.m.Y', $last_comment->created->value);
      if ($responses['last_posted']) {
        $user_query = db_select('cust_profile', 'cp');
        $user_query->condition('cp.uid', $last_comment->getOwnerId(), '=')
          ->fields('cp', array('firstname', 'lastname'));
        $author = $user_query->execute()->fetchAssoc();
        $response_lastposted = t('@date by @firstname @lastname', ['@firstname' => $author['firstname'], '@lastname' => $author['lastname'], '@date' => $responses['last_posted']]);
      }
    }


    $response_info = array(
      'total_responses' => count($total_responses),
      'response_lastposted' => $response_lastposted
    );
    return $response_info;
  }

  /*
   * @return array
   * all parameters fetched from url
   */
  static public function get_releasecomments_filters() {
    $parameters = array();
    $request = \Drupal::request()->query;
    $parameters['release_type'] = ($request->get('release_type')) ?: KONSONS;
    $parameters['services'] = ($request->get('services')) ?: "0";
    $parameters['releases'] = ($request->get('releases')) ?: "0";
    $parameters['filter_startdate'] = ($request->get('filter_startdate')) ?: "";
    $parameters['filter_enddate'] = ($request->get('filter_enddate')) ?: "";
    $parameters['limit'] = ($request->get('limit')) ?: "20";
    return $parameters;
  }

  /**
   * Display release comment text.
   */
  static public function release_comment_text() {
    $release_comments_intro_text_nid = NULL;
    $release_comments_intro_text_nid = \Drupal::config('hzd_release_management.settings')->get('release_comments_intro_text_nid');
    if($release_comments_intro_text_nid) {
      $body = db_query("SELECT body_value FROM {node__body} WHERE entity_id = :eid", array(":eid" => $release_comments_intro_text_nid))->fetchField();
    }
    $output = "<div class = 'earlywarnings_text'>" . $body;
    $build['#markup'] = $output;
    return $build;
  }
}
