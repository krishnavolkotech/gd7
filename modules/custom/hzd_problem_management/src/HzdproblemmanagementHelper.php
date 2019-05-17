<?php

namespace Drupal\problem_management;

use Drupal\hzd_services\HzdservicesStorage;
use Drupal\hzd_services\HzdservicesHelper;
use Drupal\problem_management\Exception\CustomException;

if (!defined('DISPLAY_LIMIT')) {
  define('DISPLAY_LIMIT', 20);
}

/**
 * problem management common functions defined in this class
 */
class HzdproblemmanagementHelper {

  /**
   * Return the problems listing view (ProblemFilterFrom and table).
   * @param array $type
   *   current or archive.
   *
   * @return array
   *   The ProblemFilterFrom and table renderable array.
   */
  static public function problems_tabs_callback_data($type) {
    $result = array();
    $group = get_group_id();
    global $base_url;
    /**
     * Attach javascript files to be rendered in problems listing view page
     */
    $result['#attached']['library'] = array(
      'problem_management/problem_management',
//      'hzd_customizations/hzd_customizations',
    );
    /**
     * send php variables to javascript file
     */
    $result['#attached']['drupalSettings']['search_string'] = t('Search Title, '
      . 'Description, cause, Workaround, solution');
    $result['#attached']['drupalSettings']['group_id'] = $group;
    $result['#attached']['drupalSettings']['type'] = $type;
    $result['#attached']['drupalSettings']['base_url'] = $base_url;

    /**
     * add ProblemFilterFrom
     */
    $result['#prefix'] = "<div id = 'problem_search_results_wrapper'>";
    $result['problems_filter_element'] = \Drupal::formBuilder()->getForm(
      '\Drupal\problem_management\Form\ProblemFilterFrom', $type, DISPLAY_LIMIT);
    $result['problems_default_display'] =
      HzdStorage::problems_default_display($type, DISPLAY_LIMIT);
    $result['#suffix'] = "</div>";
    $result['problems_default_display']['#cache'] = ['tags' => ['node_list', 'hzd_problem_management:prob']];
// sid load
    return $result;
  }

  /**
   * Add River flow display of content on group home page.
   */
  public function _river_flow_content_field(&$form, $default = 0) {
    if (user_access('create group content')) {
      $arg = arg(1);
      if (is_numeric($arg)) {
        $node = node_load($arg);
      }
      // Add fieldset without affecting any other elements there.
      $form['river_flow']['#type'] = 'fieldset';
      $form['river_flow']['#title'] = t('Home Page Display');
      $form['river_flow']['#collapsible'] = TRUE;
      $form['river_flow']['river_flow_content'] = array(
        '#type' => 'radios',
        '#options' => array('Default Page', 'Content River Flow'),
        '#default_value' => \Drupal::config('problem_management.settings')
          ->get('og_default_homepage_display_' . $node->nid),
        // '#default_value' => variable_get('og_default_homepage_display_' . $node->nid, 0),.
      );
    }
  }

}
