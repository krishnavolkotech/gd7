<?php

namespace Drupal\hzd_release_inprogress_comments\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\hzd_release_inprogress_comments\HzdReleaseCommentsStorage;
use Drupal\node\Entity\NodeType;
/**
 * Class HzdReleaseCommentsController.
 */
class HzdReleaseCommentsController extends ControllerBase {

  /**
   * @return mixed
   */
  public function view_release_comments() {

    $output['content']['#attached']['library'] = array(
      'hzd_earlywarnings/hzd_earlywarnings',
    );

    $output['content']['pretext'] = HzdReleaseCommentsStorage::release_comment_text();
    $output['content']['#prefix'] = '<div id = "earlywarnings_results_wrapper">';
    $output['content']['earlywarnings_filter_form'] = \Drupal::formBuilder()
      ->getForm('Drupal\hzd_release_inprogress_comments\Form\ReleaseCommentsFilterForm');
    $output['content']['earlywarnings_filter_table'] = HzdReleaseCommentsStorage::view_releasecomments_display_table();
    $output['content']['#suffix'] = '</div>';
    return $output;
  }

  /**
   * @param $group
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function add_release_comment($group) {
    $type = NodeType::load("release_comments");
    $samplenode = $this->entityTypeManager()->getStorage('node')->create(array(
      'type' => $type->id(),
    ));
    $node_create_form = $this->entityFormBuilder()->getForm($samplenode);
    return $node_create_form;
  }

  /**
   * @param $group
   * @return mixed
   */
  function release_comments_display($group) {
    $type = 'releaseWarnings';
    $group_id = $group->id();
    $output['content']['#attached']['library'] = array(
      'hzd_earlywarnings/hzd_earlywarnings',
    );
    $output['content']['#attached']['drupalSettings']['group_id'] = $group_id;
    $output['content']['#attached']['drupalSettings']['type'] = $type;
    $output['content']['pretext'] = HzdReleaseCommentsStorage::release_comment_text();
    $output['content']['table_header']['#markup'] = '<h2>' .
      t('Current Release Comments') . '</h2>';
    $output['content']['filter_form']['#prefix'] = "<div class = "
      . "'specific_earlywarnings'>";
    $output['content']['filter_form']['filter_form_wrapper']['#markup'] =
      "<div id = 'earlywarnings_results_wrapper'>";

    $output['content']['earlywarnings_filter_form'] = \Drupal::formBuilder()
      ->getForm('Drupal\hzd_release_inprogress_comments\Form\ReleaseCommentsFilterForm', $type);
    $output['content']['table']['#prefix'] = "<div class = 'view_earlywarnings_output'>";
    $output['content']['table'][] = self::release_comments_display_table($group);
    $output['content']['table']['#suffix'] = "</div></div></div>";
    return $output;
  }

  /**
   * @param $group
   * @return mixed
   */
  static public function release_comments_display_table($group) {
    $filter_value = HzdReleaseCommentsStorage::get_releasecomments_filters();
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
      'data' => t('Release Comments'),
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
    $temp = \Drupal::database()->query('SET sql_mode = \'\'');
    $query = \Drupal::database()->select('node_field_data', 'nfd');
    $query->addExpression("count(nfd.nid)", 'ew_count');
    $query->addExpression("nfd.nid", 'release_comments');
    $query->addExpression("max(ces.cid)", 'comment_id');
    $query->addExpression("CASE WHEN max(cfd.changed) is not null THEN max(cfd.changed) ELSE max(nfd.changed) END", 'last_changed');
    $query->fields('nfer', ['field_earlywarning_release_value']);
    $query->leftJoin('comment_field_data', 'cfd', 'nfd.nid = cfd.entity_id');
    $query->leftJoin('comment_entity_statistics', 'ces', 'nfd.nid = ces.entity_id');
    $query->innerJoin('node__field_earlywarning_release', 'nfer', 'nfer.entity_id = nfd.nid');
    $query->InnerJoin('node__field_release_service', 'nfrs', 'nfrs.entity_id = nfd.nid');
    $query->groupBy('nfer.field_earlywarning_release_value');
    $query->condition('nfd.type', 'release_comments');
    $query->orderBy('last_changed', 'desc');
    if (isset($filter_value['services']) && $filter_value['services'] != 0) {
      $query->condition('nfrs.field_release_service_value', $filter_value['services'], '=');
    } else {
      $group_release_view_service_id_query = \Drupal::database()
        ->select('group_releases_view', 'grv');
      $group_release_view_service_id_query->fields('grv', array('service_id'));
      $group_release_view_service_id_query->condition('group_id',
        $group_id, '=');
      $group_release_view_service = $group_release_view_service_id_query
        ->execute()->fetchCol();
      if (empty($group_release_view_service)) {
        $group_release_view_service = [-1];
      }
      $services = \Drupal::entityQuery('node')
        ->condition('type', 'services', '=')
        ->condition('release_type', $default_type, '=')
        ->condition('nid', (array)$group_release_view_service, 'IN')
        ->execute();
      $query->condition('nfrs.field_release_service_value', $services, 'IN');
    }
    if (isset($filter_value['releases']) && $filter_value['releases'] != 0) {
      $query->condition('nfer.field_earlywarning_release_value', $filter_value['releases'], '=');
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
      $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
      $query->limit($filter_value['limit']);
    } elseif (!isset($filter_value['limit'])) {
      $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender');
      $query->limit(DISPLAY_LIMIT);
    }
    $results = $query->execute()->fetchAll();
    foreach ($results as $key => $value) {
      $release_specifc_earlywarnings = \Drupal::entityQuery('node')
        ->condition('type', 'release_comments', '=')
        ->condition('field_earlywarning_release', $value->field_earlywarning_release_value, '=')
        ->sort('changed', 'DESC');
      $earlywarnings_nids = $release_specifc_earlywarnings->execute();

      if (isset($earlywarnings_nids) && !empty($earlywarnings_nids)) {
        $release = node_get_title_fast([$value->field_earlywarning_release_value])[$value->field_earlywarning_release_value];
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
          'releases' => $value->field_earlywarning_release_value,
          'r_type' => 'released',
          'release_type' => $default_type
        );
        $options['attributes'] = array(
          'alt' => t('Read Release Comments for this release'),
          'class' => 'view-earlywarning',
          'title' => t('Read Release Comments for this release')
        );

        $url = Url::fromRoute(
          'hzd_release_inprogress_comments.view_release_comments', array(
          'group' => $group_id
        ), $options
        );

        $earlywarining_link = \Drupal::service('link_generator')
          ->generate(t($earlywarining_view_link), $url);
        $nid = reset($earlywarnings_nids);
        if ($value->comment_id) {
          $comment = \Drupal\comment\Entity\Comment::load($value->comment_id);
          $userName = $comment->getOwner()->getDisplayName();
          $lastCreated = t('@date by @username', ['@date' => date('d.m.Y', $value->last_changed), '@username' => $userName]);
        } else {
          $uid = $earlyWarningNode_tmp = node_get_entity_property_fast([$nid], 'uid')[$nid];
          $name = [];
          $userName = "";
          $fetched_name = user_get_cust_profile_fast([$uid]);
          if (key_exists($uid, $fetched_name)) {
            $name[] = $fetched_name[$uid]->firstname;
            $name[] = $fetched_name[$uid]->lastname;
            $userName = implode(" ", $name);
          }
          $lastCreated = t('@date by @username', ['@date' => date('d.m.Y', $value->last_changed), '@username' => $userName]);
        }

        $commentsCount = \Drupal::database()->select('comment_entity_statistics', 'ces', $options);
        $commentsCount->addExpression('SUM(ces.comment_count)', 'com_cnt');
        $commentsCount->condition('ces.entity_id', $earlywarnings_nids, 'IN');
        $commentsCount->condition('ces.entity_type', 'node');
        $commentsCount = $commentsCount->execute()->fetch();
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
    $output['release_early_warning_table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No Data Created Yet'),
      '#attributes' => ['id' => "earlywarnings_release_sortable", 'class' => "tablesorter"],
      '#cache' => ['tags' => ['node_list', 'earlywarning_list']],
    );

    $output['pager'] = array(
      '#type' => 'pager',
      '#prefix' => '<div id="pagination">',
      '#suffix' => '</div>',
      '#exclude_from_print' => 1
    );
    return $output;
  }

}
