<?php

namespace Drupal\hzd_earlywarnings\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\hzd_earlywarnings\HzdearlywarningsStorage;

define('EARLYWARNING_TEXT', 11217);
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
    $page = $request->get('page');

    // ser=383&rel=51341&type=released.
    $service = $request->query->get('ser');
    $release = $request->query->get('rel');
    $type = $request->query->get('type');

    // Echo '<pre>'; print_r($_SESSION['earlywarning_filter_option']); exit;.
    if (!isset($page)) {
      unset($_SESSION['earlywarning_filter_option']);
    }

    if (isset($service) && isset($release) && isset($type)) {
      $_SESSION['earlywarning_filter_option']['service'] = $service;
      $_SESSION['earlywarning_filter_option']['release'] = $release;
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
  /*public function view_earlywarnings_display_table($filter_options = NULL, $release_type = KONSONS) {
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
  }*/

  /**
   * Get early warning responses info.
   */
  public function get_earlywarning_responses_info($earlywarnings_nid) {
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

}
