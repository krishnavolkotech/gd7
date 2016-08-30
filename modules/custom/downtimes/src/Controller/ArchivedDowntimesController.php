<?php

namespace Drupal\downtimes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\hzd_customizations\HzdcustomisationStorage;

/**
 * Class ArchivedDowntimesController.
 *
 * @package Drupal\downtimes\Controller
 */
class ArchivedDowntimesController extends ControllerBase {

  /**
   * Group.
   *
   * @return string
   *   Return Hello string.
   */
  public function archivedDowntimes($group) {
    $string = 'archived';
    $archived_data = \Drupal::formBuilder()->getForm('\Drupal\downtimes\Form\DowntimesFilter', $string, $group);

    $sql_where = " and resolved = 1 ";
    $string = 'archived';
    $default_downtimes = HzdcustomisationStorage::current_incidents($sql_where, $string);

    $result = array();
    $result['archive_form_render']['#prefix'] = "<div class ='curr_incidents_form'>";
    $result['archive_form_render']['archive_form'] = $archived_data;
    $result['archive_form_render']['archive_reset_form'] = HzdcustomisationStorage::reset_form();
    $result['archive_form_render']['#suffix'] = "</div>";
    $result['archive_table_render']['#prefix'] = "<div id = 'archived_search_results_wrapper'>";
    $result['archive_table_render']['archive_table'] = $default_downtimes;
    $result['archive_table_render']['#suffix'] = '</div>';
    $response = $result;

    return $response;
  }

}
