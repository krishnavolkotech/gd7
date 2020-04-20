<?php

namespace Drupal\hzd_artifact_comments;

use Drupal\Core\Render\Markup;
use Drupal\Core\Url;

class HzdArtifactCommentStorage {

  public static function fillCommentsCell($artifactName) {
    $connection = \Drupal::database();
    $count = $connection->select('node__field_artifact_name')
      ->condition('field_artifact_name_value', $artifactName)
      ->countQuery()
      ->execute()
      ->fetchField();

      // $cmt_query = db_select('node_field_data', 'n');
      // $cmt_query->join('node__field_earlywarning_release', 'nfer', 'n.nid = nfer.entity_id');
      // $cmt_query->join('node__field_release_service', 'nfrs', 'n.nid = nfrs.entity_id');
      // $cmt_query->condition('n.type', 'release_comments', '=')
        // ->condition('nfer.field_earlywarning_release_value', $release_id, '=')
        // ->condition('nfrs.field_release_service_value', $service_id, '=');
      // $cmt_count = $cmt_query->countQuery()->execute()->fetchField();

    // pr($count);exit;
    // @todo besser mit get_group_id() - aber wie bringt man ihm das bei?
    $group_id = 82; 
    if ($count > 0) {

      $cmtclass = ($count >= 10 ? 'warningcount_second' : 'commentcount');

      $cmt_view_options['query'] = array(
        'artifact' => $artifactName
      );

      $cmt_view_options['attributes'] = array(
        'class' => 'view-comment',
        'title' => t('Read comments for this artifact'));

      // $view_cmt_url = Url::fromUserInput('/group/' . $group_id . '/artefakt-kommentare', $cmt_view_options);
      $view_cmt_url = Url::fromRoute('hzd_artifact_comments.view_artifact_comments',[], $cmt_view_options);

      $viewComment = array(
        '#title' => array('#markup' => "<span class = '" . $cmtclass . "'>" . $count . "</span> "),
        '#type' => 'link',
        '#url' => $view_cmt_url,
      );

      $viewComment = \Drupal::service('renderer')->renderRoot($viewComment);
    }
    else {
      $viewComment = Markup::create('<span class="nonecommentcount">&nbsp;</span>');
    }
    // Comments create icon.
    // $cmt_options['query']['destination'] = 'group/' . $group_id . '/releases/in_progress';
    $cmt_create_icon_path = drupal_get_path('module', 'hzd_artifact_comments') . '/images/create-green-icon.png';
    $cmt_create_icon = '<img height=15 src = "/' . $cmt_create_icon_path . '">';

//    $artifactRepo = explode('_', $artifactRepo);
//    $artifactRepo = explode('-', $artifactRepo[1]);
//    $artifactClass = $artifactRepo[0];
//    $artifactStatus = $artifactRepo[2];

    $cmt_options['attributes'] = array('class' => 'create_comment', 'title' => t('Add comment for this artifact'));
    $cmt_options['query'][] = array(
      'artifact' => $artifactName,
//      'class' => $artifactClass,
//      'status' => $artifactStatus,
    );

    $create_cmt_url = Url::fromRoute('hzd_artifact_comments.add_artifact_comment', ['group' => $group_id], $cmt_options);

    $createCommentRenderArray = array(
      '#title' => array(
        '#markup' => $cmt_create_icon),
      '#type' => 'link',
      '#url' => $create_cmt_url
    );

    $createComment = \Drupal::service('renderer')->renderRoot($createCommentRenderArray);

    $artifactComment = t('@create @view', array('@create' => $createComment, '@view' => $viewComment));
    $artifactCommentCell = array(
          'data' => $artifactComment,
          'class' => 'artifact-comment-cell'
        );

    return $artifactCommentCell;

  }

  /**
   * Display release comment text.
   */
  static public function artifact_comment_text() {
    $release_comments_intro_text_nid = NULL;
    $release_comments_intro_text_nid = \Drupal::config('hzd_release_management.settings')->get('release_comments_intro_text_nid');
    if($release_comments_intro_text_nid) {
      $body = db_query("SELECT body_value FROM {node__body} WHERE entity_id = :eid", array(":eid" => $release_comments_intro_text_nid))->fetchField();
    }
    $output = "<div class = 'earlywarnings_text'>" . $body;
    $build['#markup'] = $output;
    return $build;
  }

  /**
   * @return array
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  static public function getArtifactCommentsTable($artifact = NULL) {

    $output = array();

//    $artifactNameHeader = array('data' => t('Artifact'), 'class' => 'date-hdr');
    $artifactNameHeader = t('Artifact');
    $artifactComments = array(
      'data' => t('Topics'),
      'class' => 'early-warningslink-hdr'
    );

    $date = array('data' => t('Created On'), 'class' => 'date-hdr');
    $responses = array('data' => t('Responses'), 'class' => 'responses-hdr');
    $lastcomment = array('data' => t('Last Comment'), 'class' => 'last-comment-hdr');
    $header = array($artifactNameHeader, $artifactComments, $date, $responses, $lastcomment);

    $artifactCommentsNids = \Drupal::entityQuery('node')
      ->condition('type', 'artefakt_kommentar', '=');
    if ($artifact) {
      $artifactCommentsNids->condition('field_artifact_name', $artifact, '=');
    }
    $artifactCommentsNids->sort('created', 'DESC');

    $result = $artifactCommentsNids->execute();
    $rows = [];
    foreach ($result as $artifactCommentsNid) {
      $artifactComment = \Drupal\node\Entity\Node::load($artifactCommentsNid);

      $artifactName = $artifactComment->get('field_artifact_name')->getValue();
      $author_name = $artifactComment->getOwner()->getDisplayName();
      $total_responses = self::getCommentResponseData($artifactComment->id());
      $artifactCommentTitle = $artifactComment->toLink();
      $elements = array(
        array('data' => $artifactName[0]['value'], 'class' => 'artifact-name-cell'),
        array('data' => $artifactCommentTitle, 'class' => 'earlywarningslink-cell'),
        array('data' => t('@date by @username', ['@date' => date('d.m.Y', $artifactComment->created->value), '@username' => $author_name]),
          'class' => 'created-cell'),
        array('data' => $total_responses['total_responses'], 'class' => 'responses-cell'),
        array('data' => $total_responses['response_lastposted'], 'class' => 'lastpostdate-cell'),
      );
      $rows[] = $elements;
    }

    $output['artifactComments'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No Data Created Yet'),
      '#attributes' => ['id' => "viewearlywarnings_sortable", 'class' => "tablesorter"],
      '#cache' => ['tags' => ['node_list', 'earlywarning_list']],
    );

    return $output;
  }

  /**
   * Get early warning responses info.
   */
  static public function getCommentResponseData($artifactCommentNid) {
    $response_lastposted = '';
    $total_responses = \Drupal::entityQuery('comment')
      ->condition('entity_id', $artifactCommentNid, '=')
      ->execute();
    $resonses_cid = \Drupal::entityQuery('comment')
      ->condition('entity_id', $artifactCommentNid, '=')
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

}
