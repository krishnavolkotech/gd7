<?php

namespace Drupal\hzd_release_management\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 *
 */
class DeployedReleases extends ControllerBase {

  /**
   *
   */
  public function OverView() {
    $output = $this->display_deployed_release_table();
    return [
      '#type' => 'markup',
      '#markup' => $output,
    ];
  }

  /**
   *
   */
  public function display_deployed_release_table() {
    // Need to create configuration form for release type.
    // for now giving static relese type.
    
   // $release_type = 459;
    $release_type = \Drupal::config('hzd_release_management.settings')
        ->get('konsens_service_term_id');
    $db = \Drupal::database();
    $states = $db->select('states', 's')
      ->condition('s.id', 1, '!=')
      ->fields('s', array('abbr', 'id'))
      ->orderBy('s.abbr');
    $states = $states->execute()->fetchAll();

    $services = db_query("SELECT nfd.title FROM node_field_data nfd
      LEFT JOIN node__field_release_name nfrn ON nfd.nid = nfrn.entity_id AND (nfrn.deleted = '0' AND nfrn.langcode = nfd.langcode)
      INNER JOIN node__release_type nrt ON nfd.nid = nrt.entity_id AND (nrt.deleted = '0' AND nrt.langcode = nfd.langcode)
      INNER JOIN group_releases_view grv ON grv.service_id = nfd.nid
      WHERE grv.group_id = {RELEASE_MANAGEMENT} AND nrt.release_type_target_id = 459 AND nfd.status = 1 AND nfd.type = 'services' AND nfrn.field_release_name_value 
      IS NOT NULL ORDER BY nfd.title")->fetchCol();

    if (count($services) > 0) {
      $state = array();
      foreach ($states as $state_details) {
        $state[$state_details->abbr] = $state_details->id;
      }

      foreach ($services as $services_names) {
        $deployed_values[] = array_merge(array('title' => $services_names), $state);
      }

      $table = "<div id = 'released_results_wrapper'>";
      $table .= "<table border='1' cellpadding='0' cellspacing='0' class = "
          . "'view-deployed-releases'><thead>"
          . "<tr><th style = 'min-width:170px;'>" . $this->t('Service') . "</th>";
      unset($state[0]);

      // Get the empty states and unset the empty states.
      foreach ($state as $key => $val) {
        $count = 0;
        foreach ($deployed_values as $values) {
          $service_id = db_query("SELECT nid FROM node_field_data WHERE title = :title", array(":title" => $values['title']))->fetchField();
          $release_per_state = get_releases_per_state($service_id, $val, $release_type);

          if (count($release_per_state) == 0) {
            $count++;
          }
          else {
            break;
          }
        }
        if ($count == count($services)) {
          $empty_release_states[] = $key;
        }
      }
      foreach ($empty_release_states as $value) {
        unset($state[$value]);
      }

      // $services = db_query("SELECT field_release_name_value FROM {content_type_services}
      // WHERE field_release_name_value != 'NULL' ORDER BY field_release_name_value");.
      $services_query = db_select('node_field_data', 'n');
      $services_query->join('node__field_release_name', 'nfrn', 'n.nid = nfrn.entity_id');
      $services_query->join('node__release_type', 'nrt', 'n.nid = nrt.entity_id');
      // $services_query->join('group_releases_view', 'grv', 'grv.service_id = n.nid');.
      $services_query->isNotNull('nfrn.field_release_name_value');
      $services_query->condition('n.type', 'services', '=')
        ->condition('n.status', 1, '=')
                  // ->condition('grv.group_id', $group_id, '=')
        ->condition('nrt.release_type_target_id', $release_type, '=');
      $services_query->fields('n', array('title'));
      $services_query->orderBy('n.title', 'ASC');
      $result = $services_query->execute()->fetchAll();

      foreach ($result as $key => $services_list) {
        $deployed_release_name[] = array_merge(array('title' => $services_list->title), $state);
      }

      foreach ($state as $key => $val) {
        $table .= "<th style = 'min-width:170px;'>" . $key . "</th>";
      }
      $table .= "</tr></thead>";

      foreach ($deployed_release_name as $key => $values) {
        $table .= "<tbody><tr><td style = 'min-width:170px;'>" . $values['title'] . "</td>";
        $service_id = db_query("SELECT n.nid FROM node_field_data n, node__release_type nrt WHERE  n.nid = nrt.entity_id AND n.type = 'services' AND n.title = :title AND nrt.release_type_target_id = :release_type", array(":title" => $values['title'], ':release_type' => $release_type))->fetchField();
        unset($values['title']);

        $deployed_release = get_deployed_releases_list($values, $service_id, $release_type);

        $table .= $deployed_release . "</tr>";
      }
      $table .= "</tbody></table></div>";
      return $output = $table;
    }
    else {
      return $output = t("No data created yet");
    }

    // Return "test";.
  }

}

/**
 * Get the deployed releases.
 *
 * @param $values
 *   stated id
 * @param $service_id
 *   service id
 *
 * @return
 *   deployed releases per state table.
 */
function get_deployed_releases_list($values, $service_id, $release_type = KONSONS) {

  foreach ($values as $deployed_services) {
    $deployed_releases = get_releases_per_state($service_id, $deployed_services, $release_type);
    $releases_table .= "<td style = 'min-width:170px;'>";

    foreach ($deployed_releases as $vals) {
      $release_title = db_query("SELECT title FROM {node_field_data} 
                                           WHERE nid = :nid", array(":nid" => $vals->field_earlywarning_release_value))->fetchField();
      $release_title_sort[$release_title] = $release_title;
      natsort($release_title_sort);
    }
    if (!empty($release_title_sort)) {
      foreach ($release_title_sort as $val) {
        $releases_table .= "<div>" . $val . "</div>";
      }
      unset($release_title_sort);
    }
    $releases_table .= "&nbsp;</td>";
  }
  return $releases_table;
}

/**
 * Get releases of particular service and state.
 */
function get_releases_per_state($service_id, $deployed_services, $release_type) {
  $group_id = get_group_id();
  $group_id = ($group_id ? $group_id : RELEASE_MANAGEMENT);
  $deployed_query = db_select('node_field_data', 'n');
  $deployed_query->join('node__field_earlywarning_release', 'nfer', 'n.nid = nfer.entity_id');
  $deployed_query->join('node__field_release_service', 'nfrs', 'n.nid = nfrs.entity_id');
  $deployed_query->join('node__field_archived_release', 'nfar', 'n.nid = nfar.entity_id');
  $deployed_query->join('group_releases_view', 'grv', 'grv.service_id = nfrs.field_release_service_value');
  $deployed_query->join('node__release_type', 'nrt', 'grv.service_id = nrt.entity_id');
  $deployed_query->join('node__field_environment', 'nfe', 'n.nid = nfe.entity_id');
  $deployed_query->join('node__field_user_state', 'nfus', 'n.nid = nfus.entity_id');
  $deployed_query->condition('n.type', 'deployed_releases', '=')
    ->condition('nfrs.field_release_service_value', $service_id, '=')
    ->condition('nfus.field_user_state_value', $deployed_services, '=')
    ->condition('grv.group_id', $group_id, '=')
    ->condition('nrt.release_type_target_id', $release_type, '=')
    ->condition('nfe.field_environment_value', 1, '=')
    ->condition('nfar.field_archived_release_value', 0);
  // ->condition(db_or()->isNotNull('nfar.field_archived_release_value')->condition('nfar.field_archived_release_value', 0));.
  $deployed_query->fields('nfer', array('field_earlywarning_release_value'));
  $result = $deployed_query->execute()->fetchAll();
  return $result;

  /*$deployed_release = db_query("SELECT field_earlywarning_release_value as release_id
  FROM {node} n, {content_field_earlywarning_release} cfer,
  {content_field_release_service} cfrs,
  {content_type_deployed_releases} ctds,
  {content_field_archived_release} cfar,
  {group_releases_view} grv,
  {term_node} tn
  WHERE n.nid = cfer.nid and
  n.nid = cfrs.nid and
  ctds.nid = n.nid and
  cfar.nid = n.nid and
  grv.service_id = tn.nid and

  n.type = 'deployed_releases' and
  grv.service_id = cfrs.field_release_service_value and

  (field_archived_release_value = 0 or
  field_archived_release_value IS NULL)

  AND field_release_service_value = %d and field_user_state_value = %d
  and grv.group_id = %d and tn.tid = %d AND field_environment_value = %d",
  $service_id, $deployed_services, $group_id, $release_type, 1);
  return $deployed_release;*/
}
