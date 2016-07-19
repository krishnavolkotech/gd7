<?php
/**
 * @file
 * Contains \Drupal\problem_management\Controller\ArchivedProblemsController.
 *
 */

namespace Drupal\problem_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\problem_management\HzdproblemmanagementHelper;
use Drupal\problem_management\HzdStorage;
use Drupal\node\NodeInterface;
use Drupal\Core\Url;

/**
 * Class ArchivedProblemsController
 * @package Drupal\problem_management\Controller
 */
class ArchivedProblemsController extends ControllerBase {

  function archived_problems($node) {
    $_SESSION['Group_id'] = 825;
    $_SESSION['Group_name'] = 'problem management';
    $result = array();
    $current_path = \Drupal::service('path.current')->getPath();
    $get_uri = explode('/', $current_path);
    // $group_id = \Drupal::service('user.private_tempstore')->get()->get('Group_id');
    $group_id = $_SESSION['Group_id'];
    // $group_name = \Drupal::service('user.private_tempstore')->get()->get('Group_name');
    $group_name =  $_SESSION['Group_name'];
    
    $request = \Drupal::request();
    $page = $request->get('page'); 
   //  echo $page;  exit; 
    if ((isset($get_uri['4']) && ($get_uri['4'] == 'archived_problems')) && !isset($page)) {
      // \Drupal::service('user.private_tempstore')->unset('sql_where');
      // \Drupal::service('user.private_tempstore')->unset('limit');

      unset($_SESSION['problems_query']);
      unset($_SESSION['sql_where']);
      unset($_SESSION['limit']);
    }
    
    $string = $get_uri['4'];
    HzdproblemmanagementHelper::set_breabcrumbs_problems($string);
    // drupal_add_js(array('group_id' => $group_id, 'type' => $string), 'setting');
    // drupal_add_js(array('search_string' => t('Search Title, Description, cause, Workaround, solution')), 'setting');
    $result['#attached']['drupalSettings']['group_id'] = $group_id;
    $result['#attached']['drupalSettings']['type'] = $string;
    $result['#attached']['drupalSettings']['search_string'] = t('Search Title, Description, cause, Workaround, solution'); 
    // $output .= "<div id = 'problem_search_results_wrapper'>" . drupal_get_form('problems_filter_form', $string);
    $result['content']['#prefix'] = "<div id = 'problem_search_results_wrapper'>" ;
    $result['content']['problems_filter_element'] = \Drupal::formBuilder()->getForm('Drupal\problem_management\Form\ProblemFilterFrom', $string);
    
    // array_push($result['page']['content']['problems_filter_element'], HzdproblemmanagementHelper::problem_reset_element());
    // $output .= "<div id = 'problem_search_results_wrapper'>";
    // $output .=  "<div class = 'reset_form'>";

    $result['content']['problems_reset_element']['#prefix'] = "<div class = 'reset_form'>";
    $result['content']['problems_reset_element']['form'] = HzdproblemmanagementHelper::problem_reset_element();
    $result['content']['problems_reset_element']['#suffix'] = '</div><div style = "clear:both"></div>';
    
    // $sql_where = \Drupal::service('user.private_tempstore')->get('sql_where');
    if (isset( $_SESSION['sql_where'] )) {
      $sql_where = $_SESSION['sql_where'];
    } else {
      $sql_where = NULL;
    }
    // $sql_limit = \Drupal::service('user.private_tempstore')->get('$sql_limit');  
    // $limit = $_SESSION['limit']?$_SESSION['limit']:NULL;
    if (isset( $_SESSION['limit'] )) {
      $limit = $_SESSION['limit'];
    } else {
      $limit = NULL;
    }

    $result['content']['problems_default_display'] = HzdStorage::problems_default_display($sql_where, $string, $limit);
    $result['content']['#suffix'] = "</div>";
    return $result;
  }
}