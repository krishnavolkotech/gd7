<?php

namespace Drupal\problem_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\problem_management\HzdproblemmanagementHelper;
use Drupal\problem_management\HzdStorage;

if (!defined('DISPLAY_LIMIT')) {
  define('DISPLAY_LIMIT', 20);
}

/**
 * Class ArchivedProblemsController.
 *
 * @package Drupal\problem_management\Controller
 */
class ArchivedProblemsController extends ControllerBase {

  /**
   *
   */
  public function archived_problems() {
    $string = 'archived_problems';
    $group = \Drupal::routeMatch()->getParameter('group');
    $result = array();
    $current_path = \Drupal::service('path.current')->getPath();
    $get_uri = explode('/', $current_path);
    $group_id = get_group_id();
    $group_name = $group->label();
    /**
     * Attach javascript files to be rendered in problems listing view page
     */
    $result['#attached']['library'] = array(
      'problem_management/problem_management',
      'hzd_customizations/hzd_customizations',
    );
    $result['#attached']['drupalSettings']['group_id'] = $group_id;
    $result['#attached']['drupalSettings']['type'] = $string;
    $result['#attached']['drupalSettings']['search_string'] = t('Search Title, Description, cause, Workaround, solution');
    // $output .= "<div id = 'problem_search_results_wrapper'>" . drupal_get_form('problems_filter_form', $string);.
    $result['#prefix'] = "<div id = 'problem_search_results_wrapper'>";
    $result['problems_filter_element'] = \Drupal::formBuilder()->getForm(
        'Drupal\problem_management\Form\ProblemFilterFrom', $string);

    // array_push($result['page']['content']['problems_filter_element'], HzdproblemmanagementHelper::problem_reset_element());
    // $output .= "<div id = 'problem_search_results_wrapper'>";
    // $output .=  "<div class = 'reset_form'>";.
//    $result['problems_reset_element']['#prefix'] = "<div class = 'reset_form'>";
//    $result['problems_reset_element']['form'] = HzdproblemmanagementHelper::problem_reset_element();
//    $result['problems_reset_element']['#suffix'] = '</div><div style = "clear:both"></div>';
//
//  
    $result['problems_default_display']['table'] = HzdStorage::problems_default_display($string, DISPLAY_LIMIT);
    // $result['content']['problems_default_display']['#suffix'] = '</div>';.
    $result['#suffix'] = "</div>";
    return $result;
  }

}
