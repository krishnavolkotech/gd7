<?php

namespace Drupal\problem_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\problem_management\HzdStorage;
// Use Drupal\hzd_services\HzdservicesHelper;
// use Drupal\Core\Url;.
use Drupal\Core\Link;
use Drupal\Core\Url;

// Use Drupal\node\NodeInterface;.
/**
 * Class ImportHistoryController.
 *
 * @package Drupal\problem_management\Controller
 */
class ImportHistoryController extends ControllerBase {

  /**
   * Callback import history
   * Table format display of import history.
   */
  public function import_history() {
    $response = array();

    $current_path = \Drupal::service('path.current')->getPath();
    $get_uri = explode('/', $current_path);

    // If ($get_uri[4] == 'import_history') {
    //    unset($_SESSION['history_limit']);
    //  }.
    $request = \Drupal::request();
    $page = $request->get('page');
    if (!$page) {
      unset($_SESSION['history_limit']);
    }

    // Echo 'kjsdhfpsdk';    exit;
    // drupal_add_js(drupal_get_path('module', 'hzd_customizations') . '/jquery.tablesorter.min.js');
    // drupal_add_js(drupal_get_path('module', 'problem_management') . '/problem_management.js');
    // $response['#attached']['drupalSettings']['search_string'].
    $response['#attached']['library'] = array('problem_management/problem_management',
//      'hzd_customizations/hzd_customizations',
    );

    $breadcrumb = array();
    $breadcrumb[] = Link(t('Home'), Url::fromUserInput('/'));
    // $group_id = \Drupal::service('tempstore.private')->get()->get('Group_id');
    // $group_name = \Drupal::service('tempstore.private')->get()->get('Group_name');
    $request = \Drupal::request();
    $route_match = \Drupal::routeMatch();

    $title = \Drupal::service('title_resolver')->getTitle($request, $route_match->getRouteObject());

    if (isset($group_name)) {
      // $breadcrumb[] = \Drupal::l(t($group_name), Url::fromEntityUri(array('node', $group_id)));
      // $breadcrumb[] = \Drupal::l($title, Url::fromEntityUri(array('node', $group_id, 'problems')));.
    }
    else {
      // $breadcrumb[] = \Drupal::l($title, Url::fromRoute('problem_management.problems', array($node)));
    }

    $breadcrumb[] = t('Import History');
    // drupal_set_breadcrumb($breadcrumb);
    // Breadcrumb::setLinks($breadcrumb);
    $breadcrumb_build = array();

    $breadcrumb_build = array(
      Link::createFromRoute(t('Home'), '<front>'),
      // Link::createFromRoute(t('Forums'), 'forum.index'),
      //  Link::createFromRoute($this->forumContainer['name'], 'forum.page', array('taxonomy_term' => $this->forumContainer['tid'])),
      //  Link::createFromRoute($this->forum['name'], 'forum.page', array('taxonomy_term' => $this->forum['tid'])),.
    );

    $breadcrumb = array(
      '#theme' => 'breadcrumb',
      '#links' => $breadcrumb_build,
    );

    \Drupal::service('renderer')->renderRoot($breadcrumb);
    $result[]['#attached']['library'] = array('problem_management/problem_management');
    $result[]['#prefix'] = "<div>";
    $result[]['import_status_filter_form']['form'] = \Drupal::formBuilder()->getForm('\Drupal\problem_management\Form\ProblemImportstatusFrom');

    $result[]['#suffix'] = '</div><div style = "clear:both"></div>';
    if (isset($_SESSION['history_limit'])) {
      $limit = $_SESSION['history_limit'];
    }
    else {
      $limit = NULL;
    }

    $result[]['import_search_results_wrapper']['#prefix'] = "<div id = 'import_search_results_wrapper' > ";
    $result[]['import_search_results_table'] = HzdStorage::import_history_display_table($limit);
    $result[]['#suffix'] = "</div>";

    return $result;
  }

}
