<?php
/**
 * @file
 * Contains \Drupal\hzd_earlywarnings\Controller\HzdEarlyWarnings
 */

namespace Drupal\hzd_earlywarnings\Controller;

use Drupal\Core\Database\Database;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

define('EARLYWARNING_TEXT', 11217);
/**
 * Class HzdEarlyWarnings
 * @package Drupal\hzd_earlywarnings\Controller
 */
class HzdEarlyWarnings extends ControllerBase {

 // const KONSONS = 459;
  public function view_early_warnings() {
    $output[] = $this->early_warning_text();
    $output[]['#prefix'] =     $result[]['#prefix'] = "<div id = 'earlywarnings_results_wrapper'>"; 
    $output[] =  \Drupal::formBuilder()->getForm('Drupal\hzd_earlywarnings\Form\EarlyWarningsFilterForm');
    $output[] = $this->view_earlywarnings_display_table();
    $output[]['#suffix'] = "</div>";
    return $output;
  }

  // display releases earlywarnings
  public function view_earlywarnings_display_table($limit = 10, $release_type = KONSONS) {
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
  }

  // get early warning responses info
  public function get_earlywarning_responses_info($earlywarnings_nid) {
    $total_responses = db_query("SELECT COUNT(*) FROM {comment_field_data} WHERE entity_id = :nid", 
                       array(":nid" => $earlywarnings_nid))->fetchField();
    $resonses_sql = db_query("SELECT entity_id, uid, created FROM {comment_field_data} WHERE entity_id = :eid ORDER BY created DESC limit 1", 
                    array(":eid" => $earlywarnings_nid))->fetchAll();
    foreach($resonses_sql as $vals) {
      $responses['uid'] = $vals->uid;
      $responses['last_posted'] = date('d.m.Y', $vals->created);
      if($responses['last_posted']) {
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

  // display early warning text on view warly warnings page.
  public function early_warning_text() {
    $create_icon_path = drupal_get_path('module', 'hzd_release_management') . '/images/create-icon.png';
    $create_icon = "<img height=15 src = '/" . $create_icon_path . "'>"; 
    $body = db_query("SELECT body_value FROM {node__body} WHERE entity_id = :eid", array(":eid" => EARLYWARNING_TEXT))->fetchField();
    $output = "<div class = 'earlywarnings_text'>" . $body . "<a href='/release-management/add/early-warnings?\ destination=node/339/early-warnings&amp;ser=0&amp;rel=0' title='". t("Add an Early Warning for this release"). "'>". $create_icon ."</a></div>";
    $build['#markup'] = $output;
    return $build;
  }

}
