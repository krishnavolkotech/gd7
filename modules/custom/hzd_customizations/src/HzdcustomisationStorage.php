<?php

namespace Drupal\hzd_customizations;

use Drupal\cust_group\Controller\CustNodeController;
use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Core\Config\Config;
use Drupal\Core\Template\Attribute;
// Use Drupal\node\Entity\Node;
// use Drupal\user\PrivateTempStoreFactory;.
use Drupal\Core\Path\Path;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\hzd_services\HzdservicesStorage;
use Drupal\Component\Utility\Unicode;

if (!defined('MAINTENANCE_GROUP_ID')) {
  define('MAINTENANCE_GROUP_ID', \Drupal::config('downtimes.settings')->get('maintenance_group_id'));
}

if (!defined('PAGE_LIMIT')) {
  define('PAGE_LIMIT', 20);
}

/**
 *
 */
class HzdcustomisationStorage {

  /**
   * Change url alias when the path to problems or releases is changed.
   */
  static public function change_url_alias($dst_path = NULL, $src_path = NULL) {
    // $result = db_query("SELECT nid,title FROM {node} where type = '%s'", 'group');.
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->Fields('n', array('nid', 'title'));
    $query->condition('type', 'group', '=');
    $group = $query->execute()->fetchObject();

    $dst = "$group->title/$dst_path";
    $src = "node/$group->nid/$src_path";
    // Check if the url alias of releases existed or not. if not, inserrt them. otherwise, update them.
    $query = \Drupal::database()->select('url_alias', 'ua');
    $query->Fields('ua', array('pid'));
    $query->condition('source', $src, '=');
    $url_alias = $query->execute()->fetchCol();

    if (!empty($url_alias)) {
      if (!isset($url_alias['0'])) {
        // Populate the node access table.
        \Drupal::database()->insert('url_alias')
            ->fields(array(
              'source' => $src,
              'alias' => $dst,
            ))->execute();
      }
      else {
        \Drupal::database()->update('url_alias')
            ->fields(array(
              'source' => $src,
              'alias' => $dst,
            ))->condition('pid', $url_alias['0'], '=')->execute();
      }
    }
    // Need to clear the menu cache to get the new menu item affected.
    // menu_cache_clear_all();
  }

  /**
   * Function for documentation link.
   */
  public function documentation_link_download($params, $values) {

    $title = $params['title'];
    $field_release_value = $params['release_value'];
    $field_date_value = $params['date_value'];
    $field_documentation_link_value = $params['doku_link'];
    $values_service = $values['service'];
    $values_title = $values['title'];
    $link = $values['documentation_link'];
    $values_date = $values['date'];

    $service = strtolower(db_result(db_query("SELECT title FROM {node} where nid= %d", $values_service)));
    $nid = db_result(db_query("SELECT nid FROM {node} where title = '%s' ", $values_title));

    // Create url alias.
    $release_value_type = db_result(db_query("SELECT field_release_type_value
                                            FROM {content_type_release} WHERE nid = %d ", $nid));
    if ($release_value_type != 3) {
      $url_alias = create_url_alias($nid, $service, $values);
    }

    $count_nid = db_result(db_query("SELECT count(*)
                                   FROM {release_doc_failed_download_info}
                                   WHERE nid = %d", $nid));
    $field_release_type_value = db_result(db_query("SELECT field_release_type_value
                                                  FROM {content_type_release}
                                                  WHERE nid=%d", $nid));

    // Checked documentation link empty or not.
    if ($link != '') {

      /* Check It is new release or not
       * Check Release status changes from inprogress to released
       * Check how many times release import attempted.If three attempts unsuccesssful failure is perment.
       * Check released release date/time changed or not.
       */
      if ((!$title) || ($field_release_value == 2 && $field_release_type_value == 1) || (($count_nid < 3) && ($count_nid > 0)) || (($field_date_value != $values_date) && ($field_release_type_value == 1))) {

        list($release_title, $product, $release, $compressed_file, $link_search) = get_release_details_from_title($values_title, $link);

        // Check secure-download string is in documentation link. If yes excluded from documentation download.
        if (empty($link_search)) {
          $root_path = file_directory_path();
          $path = file_directory_path() . "/releases";
          $service = "'" . $service . "'";
          $release_title = strtolower($release_title);
          $paths = $path . "/" . $service . "/" . $product . "/" . $release_title . "/dokumentation";

          // Check the directory exist or not.
          if (!is_dir(str_replace("'", "", $paths))) {
            shell_exec("mkdir -p " . $path . "/" . $service . "/" . $product . "/" . $release_title . "/dokumentation");
          }
          $existing_zip_file = $paths . "/" . $compressed_file;

          /*
           * Remove Documentation directory folders.
           * Check Release status changes from inprogress to released.
           * Check released release date/time changed or not.
           */
          if (($values_date != $field_date_value) || ($field_release_value == 2 && $field_release_type_value == 1)) {

            $dokument_zip = explode("/", $field_documentation_link_value);
            $dokument_zip_file_name = strtolower(array_pop($dokument_zip));
            $root_path = file_directory_path();
            $zip_version_path = $root_path . "/releases/downloads/" . $title . "_doku.zip";
            if (!empty($dokument_zip_file_name)) {
              if (file_exists($zip_version_path)) {
                shell_exec("rm -rf " . $zip_version_path);
              }
            }
            $remove_docs = scandir(str_replace("'", "", $paths));
            foreach ($remove_docs as $doc_files) {
              shell_exec("rm -rf " . $paths . "/" . $doc_files);
            }
            cache_clear_all('release_doc_import_' . $nid, 'cache');
          }
          $existing_paths_replace = str_replace("'", "", $paths);
          $scan_docu = scandir($existing_paths_replace);
          unset($scan_docu[0]);
          unset($scan_docu[1]);

          // Check Documentation directory empty or not.
          if (empty($scan_docu[2])) {
            if (is_dir($paths)) {
              $scan_dir = scandir($paths);
            }
            $username = variable_get('release_import_username', NULL);
            $password = variable_get('release_import_password', NULL);
            release_documentation_link_download($username, $password, $paths, $link, $compressed_file, $nid);
            $nid_count = db_result(db_query("SELECT count(*)
                                           FROM {release_doc_failed_download_info} WHERE nid = %d", $nid));
            if ($nid_count == 3) {
              release_not_import_mail($nid);
            }
          }
        }
      }
    }
  }

  /**
   * When atleast one service is selected, then the menu should be created.
   * When all services are deselected then the menu should be hidden.
   */
  static public function reset_menu_link($counter = 0, $link_title = NULL, $link_path = NULL, $menu_name = NULL, $gid = NULL) {
    // droy: Replaced unique identifier link_title by link_path because of issues with German special characters in link_title which result in no sql query results found.
    $group = \Drupal::routeMatch()->getParameter('group')->id();
    $group_link = \Drupal::database()->select('menu_link_content_data', 'mlcd')
            ->fields('mlcd', array('id'))
            ->condition('link__uri', '%' . $link_path, 'LIKE')
            ->condition('menu_name', $menu_name, 'LIKE')
            ->execute()->fetchField();
    // pr($group_link);echo $counter;exit;.
    if ($counter > 0) {
      if (empty($group_link)) {
        $menu_link = MenuLinkContent::create([
              'title' => t($link_title),
              'link' => ['uri' => 'internal:/group/' . $group . '/problems'],
              'menu_name' => $menu_name,
              'expanded' => FALSE,
        ]);
        $menu_link->save();

        // menu_link_save($flink);
        // Need to clear the menu cache to get the new menu item affected.
        menu_cache_clear_all();
        // Need to unset the array so that a new is built. otherwise it overwrites the array and only the last menu link gets created.
        unset($flink);

        // droy: Create a URL alias for the new downtimes view
        // This is probably not the right place to do this so please move when you see this comment.
        if ($link_path == 'downtimes') {
          // $group_path = db_result(db_query("select dst from url_alias where src = '%s'", "node/$gid"));.
          $query = \Drupal::database()->select('url_alias', 'ua')
              ->Fields('ua', array('dst'))
              ->condition('source', "node/$gid", '=');
          $group_path = $query->execute()->fetchAssoc();
          // path_set_alias('node/' . $gid . '/' . $link_path, $group_path['dst'] . '/' . 'stoerungen');.
          Path::save('node/' . $gid . '/' . $link_path, $group_path['dst'] . '/' . 'stoerungen');
        }
      }
      else {
        // The item is present but it is in hidden state. so make the hidden value to 0. The $group_link contains mlid and also place the correct router_path and link_path for old groups.
        // check once the hidden value before updating.
        // $hidden_value = db_result(db_query("SELECT hidden from {menu_links} WHERE mlid = %d", $group_link));.
        $query = \Drupal::database()->select('menu_link_content_data', 'mlcd');
        $query->fields('mlcd', array('enabled'));
        $query->condition('id', $group_link, '=');
        $hidden_value = $query->execute()->fetchAssoc();
        if ($hidden_value['enabled'] == 0) {
          // droy: When hiding a menu entry, we only set hidden = 1. Why the need to update many more fields when unhiding?
          // db_query("UPDATE {menu_links} set hidden = %d, link_path= '%s', router_path = '%s' WHERE mlid = %d", 0, "node/$gid/$link_path", "node/%/$link_path", $group_link);
          // db_query("UPDATE {menu_links} set hidden = %d WHERE mlid = %d", 0, $group_link);.
          \Drupal::database()->update('menu_link_content_data')->fields(array('enabled' => 1))->condition('id', $group_link, '=')->execute();

          // Need to clear the menu cache to get the new menu item affected.
          menu_cache_clear_all();
        }
      }
    }
    else {
      // All links were unset. So we need to make the menu item hidden.
      if ($group_link) {
        // db_query("UPDATE {menu_links} set hidden = %d WHERE mlid = %d", 1, $group_link);.
        \Drupal::database()->update('menu_link_content_data')->fields(array('enabled' => 0))->condition('id', $group_link, '=')->execute();
        // Need to clear the menu cache to get the new menu item affected.
        menu_cache_clear_all();
      }
    }
  }

  /**
   * Display published services.
   */
  static public function service_profiles() {
    // $servicesdata['#markup']['#title'] = Drupal::config()->get('system.site')->get('name');
    // $servicesdata['#markup']['#title'] = "<p>" . t("Please select a Service") . "</p>";.
    $query = \Drupal::database()->select('node_field_data', 'nfd');
    $query->fields('nfd', ['nid']);
    $query->addField('nfd', 'title', 'service');
    $query->join('node__field_enable_downtime', 'nfed', 'nfd.nid = nfed.entity_id');
    $query->join('node__field_downtime_type', 'nfdt', 'nfed.entity_id = nfdt.entity_id');
    $query->condition('nfdt.field_downtime_type_value', 'Publish');
    $query->condition('nfed.field_enable_downtime_value', '1');
    $query->orderBy('service');
    $services = $query->execute()->fetchAll();

    foreach ($services as $service) {
      $query = \Drupal::database()->select('node__field_dependent_service', 'nfds');
      $query->addField('nfds', 'entity_id');
      $query->condition('nfds.field_dependent_service_target_id', $service->nid);
      $query->range(0, 1);
      $id = $query->execute()->fetchField();
      if ($id) {
        $query = \Drupal::database()->select('url_alias', 'ua');
        $query->addField('ua', 'alias');
        $query->condition('ua.source', '/node/' . $id);
        $query->range(0, 1);
        $path_alias = $query->execute()->fetchField();
        $text = $service->service;
        // $url = Url::fromUserInput($path_alias . '/edit');.
        $url = Url::fromUserInput('/node/' . $id . '/edit');
        $data[] = \Drupal::l($text, $url);
      }
      else {
        $text = $service->service;
        $url = Url::fromUserInput('/node/' . MAINTENANCE_GROUP_ID . '/add/service_profile?service=' . $service->nid);
        // $link = Link::fromTextAndUrl($text, $url);.
        $data[] = Link::fromTextAndUrl($text, $url);
        // Echo '<pre>';  print_r($check);  exit;.
      }
    }

    $service_profiile_data = array();
    // $service_profiile_data[] = "<p>" . t("Please select a Service") . "</p>";.
    $service_profiile_data[] = array(
      '#theme' => 'item_list',
      '#items' => $data,
      '#prefix' => "<div class='service-profile'>",
      '#suffix' => "</div>",
      '#title' => t("Please select a Service"),
    );

    return $service_profiile_data;
  }

  /**
   * Get States.
   */
  static public function get_states($active = 0) {
    // $servicesdata['#markup']['#title'] = Drupal::config()->get('system.site')->get('name');
    // $servicesdata['#markup']['#title'] = "<p>" . t("Please select a Service") . "</p>";.
    $query = \Drupal::database()->select('states', 's');
    $query->isNotNull('s.abbr');
    $query->fields('s');
    if ($active) {
      $query->condition('active', 1);
    }
    $states = $query->execute()->fetchAll();
    // $data[0] = 'Bundesland';.
    foreach ($states as $state) {
      if ($state->state == NULL) {
        $data[$state->id] = 'Bundesland';
      }
      else {
        $data[$state->id] = t($state->state . " ($state->abbr)");
      }
    }
    return $data;
  }

  /**
   *
   */
  static public function get_all_user_state_abbr() {
    $user_states = \Drupal::database()->select("states", "s");
    $user_states->fields('s');
    $user_states->execute()->fetchAll();
    $states = array();
    foreach ($user_states as $user_state) {
      $states[$states_values->id] = $states_values->abbr;
    }
    return $states;
  }

  /**
   * Get Published Services.
   */
  static public function get_published_services() {
    $query = \Drupal::database()->select('node_field_data', 'nfd');
    $query->fields('nfd', ['nid']);
    $query->addField('nfd', 'title', 'service');
    $query->join('node__field_enable_downtime', 'nfed', 'nfd.nid = nfed.entity_id');
    $query->join('node__field_downtime_type', 'nfdt', 'nfed.entity_id = nfdt.entity_id');
    $query->condition('nfdt.field_downtime_type_value', 'Publish');
    $query->condition('nfed.field_enable_downtime_value', '1');
    $query->orderBy('service');
    $services = $query->execute()->fetchAll();

    $data = array();
    foreach ($services as $service) {
      $query = \Drupal::database()->select('node__field_dependent_service', 'nfds');
      $query->addField('nfds', 'entity_id');
      $query->condition('nfds.field_dependent_service_target_id', $service->nid);
      $query->range(0, 1);
      $id = $query->execute()->fetchField();
      if ($id) {
        $data[$id] = t($service->service);
      }
    }
    return $data;
  }

  /**
   * Display published and which are enabled for downtimes services.
   */
  static public function get_maintenance_related_services($type, $nid = NULL, $downtime_services = NULL, $option_type = NULL) {
    $query = \Drupal::database()->select('node_field_data', 'nfd');
    $query->fields('nfd', ['nid']);
    $query->addField('nfd', 'title', 'service');
    $query->join('node__field_enable_downtime', 'nfed', 'nfd.nid = nfed.entity_id');
    $query->join('node__field_downtime_type', 'nfdt', 'nfed.entity_id = nfdt.entity_id');
    $query->condition('nfdt.field_downtime_type_value', 'Publish');
    $query->condition('nfed.field_enable_downtime_value', '1');
    $query->orderBy('service');
    $services = $query->execute()->fetchAllKeyed();

    $img = drupal_get_path('theme', 'hzd') . '/images/i-icon-26.png';
    foreach ($services as $service_nid => $service) {
      $query = \Drupal::database()->select('node__field_dependent_service', 'nfds');
      $query->addField('nfds', 'entity_id');
      $query->condition('nfds.field_dependent_service_target_id', $service_nid);
      $query->range(0, 1);
      $id = $query->execute()->fetchField();
      $sdata = self::get_service_data($service_nid, $service);
      if ($id && !empty($sdata) && $option_type != 'select') {
        $c_data = trim($service) . "|<div class='service-tooltip' id = '" . $service_nid . "'><img height=10 src = '/" . $img . "'></div><div class='service-profile-data service-profile-data-" . $service_nid . "' style='display:none'><div class='wrapper'><div class='service-profile-close' style='display:none' id='close-" . $service_nid . "'><a id='close-" . $service_nid . "'>Close</a></div>" . $sdata . "</div></div>";
        $service_names[$service_nid] = $c_data;
      }
      else {
        $service_names[$service_nid] = $service;
      }
    }

    // In maintenance edit form display unpublished services which were already selected.
    if ($nid) {
      foreach ($downtime_services as $val) {
        if (!array_key_exists($val, $service_names)) {
          $service_title = db_query("SELECT title FROM {node_field_data} WHERE nid = $val")->fetchField();
          $sdata = self::get_service_data($val, $service_title);
          $c_data = trim($services[$val]) . "|<div class='service-tooltip' style='display:none' id = '" . $val . "'><img height=10 src = '/" . $img . "'></div><div class='service-profile-data service-profile-data-" . $nid . "' style='display:none'><div class='wrapper'><div class='service-profile-close' style='display:none' id='close-" . $nid . "'><a id='close-" . $nid . "'>Close</a></div>" . $sdata . "</div></div>";
          $service_names[$val] = $c_data;
        }
      }
    }
    return $service_names;
  }

  static function getDependantServices($serviceId) {

    $services = \Drupal::entityQuery('node')->condition('field_dependent_downtimeservices', $serviceId)->execute();
    $service = \Drupal\node\Entity\Node::loadMultiple($services);
//    $dependantServicesList = $service->get('field_dependent_services')->getValue();
    $dependantServices = [];
    foreach ($service as $val) {
      $dependantServices[] = $val->get('field_dependent_service')->referencedEntities()[0]->id();
//      pr($dependantServices);exit;
    }
    return $dependantServices;
  }

  /**
   * Get each service data, displayed in downtimes.
   */
  static public function get_service_data($sid, $service_name) {
    $states = self::get_states();
    $downtime_services = HzdservicesStorage::get_related_services('downtimes');

    $query = \Drupal::database()->select('node__field_dependent_service', 'nfd');
    $query->fields('nfd', ['entity_id']);
    $query->fields('nfi', ['field_impact_value']);
    $query->fields('nfmat', ['field_maintenance_advance_time_value']);
    $query->leftJoin('node__field_impact', 'nfi', 'nfi.entity_id = nfd.entity_id');
    $query->join('node__field_maintenance_advance_time', 'nfmat', 'nfmat.entity_id = nfd.entity_id');
    $query->condition('nfd.field_dependent_service_target_id', $sid);
    $services = $query->execute()->fetchAll();
    if (empty($services)) {
      return;
    }
    $service_recipients = $service_impact = $service_operators = $service_depends = array();
    foreach ($services as $service) {
      $nid = $service->entity_id;
      $service_advance_time = $service->field_maintenance_advance_time_value;
      $service_impact = $service->field_impact_value;
      if ($service_advance_time) {
        $data['service_advance_time'] = $service_advance_time;
      }
      if ($service_impact) {
        $data['service_impact'] = $service_impact;
      }
    }

    // Get service operator data.
    $query = \Drupal::database()->select('node__field_service_operator', 'nfd');
    $query->fields('nfd', ['field_service_operator_value']);
    $query->condition('nfd.entity_id', $nid);
    $query->isNotNull('field_service_operator_value');
    $service_operators_data = $query->execute()->fetchAll();

    foreach ($service_operators_data as $service_operator_vals) {
      if (isset($states[$service_operator_vals->field_service_operator_value])) {
        $service_operators[] = $states[$service_operator_vals->field_service_operator_value];
      }
    }
    $data['service_operators'] = $service_operators;

    // Get service recipient data.
    $query = \Drupal::database()->select('node__field_service_recipient', 'nfd');
    $query->fields('nfd', ['field_service_recipient_value']);
    $query->condition('nfd.entity_id', $nid);
    $query->isNotNull('field_service_recipient_value');
    $service_recipient_data = $query->execute()->fetchAll();
    foreach ($service_recipient_data as $service_recipient_vals) {
      if (isset($states[$service_recipient_vals->field_service_recipient_value])) {
        $service_recipients[] = $states[$service_recipient_vals->field_service_recipient_value];
      }
    }
    $data['service_recipients'] = $service_recipients;

    // Get dependent services list.
    $query = \Drupal::database()->select('node__field_dependent_downtimeservices', 'nfd');
    $query->fields('nfd', ['field_dependent_downtimeservices_target_id']);
    $query->condition('nfd.entity_id', $nid);
    $query->isNotNull('field_dependent_downtimeservices_target_id');
    $service_depends_data = $query->execute()->fetchAll();

    $service_depends = array();
    foreach ($service_depends_data as $service_depends_vals) {
      if (isset($downtime_services[$service_depends_vals->field_dependent_downtimeservices_target_id])) {
        $service_depends[] = $downtime_services[$service_depends_vals->field_dependent_downtimeservices_target_id];
      }
    }
    $data['service_depends'] = $service_depends;

    // Get service time.
    $query = \Drupal::database()->select('service_profile_maintenance_service_time', 'nfd');
    $query->fields('nfd', ['day_time']);
    $query->condition('nfd.nid', $nid);
    $service_time = $query->execute()->fetchField();

    $unserialize_service_time = unserialize($service_time);
    if ($unserialize_service_time) {
      $get_service_time = array_chunk($unserialize_service_time, 3, TRUE);
      $service_vals = '';
      $flag = 0;
      foreach ($get_service_time as $time) {
//        $service_vals .= "<tr>";
        $i = 1;
        // dpm($time);
        $service_vals_data = '';
        foreach ($time as $key => $val) {
          if ($i == 1 && $val != 1) {
            $i = 1;
            $flag = 0;
            break;
          }
          else {
            $flag = 1;
            if ($i == 1) {
              if ($val) {
                $day = explode("_", $key);
                $service_vals_data .= "<td>" . t($day[2]) . "</td>";
              }
            }
            else {
              if ($val != '') {
                $service_vals_data .= "<td>" . date('H:i', strtotime($val)) . "</td>";
              }
            }
            $i++;
          }
        }
        // If $flag == 1 Then one row 
        if ($flag == 1)
          $service_vals .= "<tr>" . $service_vals_data . "</tr>";
      }
      $data['service_time'] = $service_vals;
    }

    // Maintenance windows time.
    $query = \Drupal::database()->select('service_profile_maintenance_windows', 'nfd');
    $query->fields('nfd', ['day', 'day_until', 'from_time', 'to_time']);
    $query->condition('nfd.nid', $nid);
    $maintenance_windows_time = $query->execute()->fetchAll();

    $vals = '';
    foreach ($maintenance_windows_time as $maintenance_windows_time_vals) {
      if ($maintenance_windows_time_vals->day_until == '' || $maintenance_windows_time_vals->day_until == NULL) {
        $maintenance_windows_time_vals->day_until = $maintenance_windows_time_vals->day;
      }
      $vals .= "<tr><td>" . t($maintenance_windows_time_vals->day) . "</td><td>" . date('H:i', strtotime($maintenance_windows_time_vals->from_time)) . "</td><td>" . t($maintenance_windows_time_vals->day_until) . "</td><td>" . date('H:i', strtotime($maintenance_windows_time_vals->to_time)) . "</td></tr>";
    }
    $data['maintenance_windows_time'] = $vals;
    $data['service_name'] = $service_name;

    return self::get_theme_service_data($data);
  }

  /**
   *
   */
  static public function get_theme_service_data($data) {
    $downtime_service_data = "<table>";
    $downtime_service_data .= "<tr><td class='left'><div><b>" . t("Service Name:") . "</b></div></td><td class='right'><div>" . $data['service_name'] . "</div></td></tr>";
    if (isset($data['service_operators'])) {
      $downtime_service_data .= "<tr><td class='left'><div><b>" . t("Service Operator:") . "</b></div></td><td class='right'><div>" . implode(', ', $data['service_operators']) . "</div></td></tr>";
    }
    if (isset($data['service_recipients'])) {
      $downtime_service_data .= "<tr><td class='left'><div><b>" . t("Service Recipients:") . "</b></div></td><td class='right'><div>" . implode(', ', $data['service_recipients']) . "</div></td></tr>";
    }
    if (isset($data['service_depends'])) {
      $downtime_service_data .= "<tr><td class='left'><div><b>" . t("Dependent Services:") . "</b></div></td><td class='right'><div>" . implode(', ', $data['service_depends']) . "</div></td></tr>";
    }
    /* else {
      $downtime_service_data .= "<tr><td class='left'><div><b>" . t("Dependent Services:") . "</b></div></td><td class='right'><div>" . t('NONE') . "</div></td></tr>";
      } */
    if (isset($data['service_advance_time'])) {
      $downtime_service_data .= "<tr><td class='left'><div><b>" . t("Maintenance Advance Time:") . "</b></div></td><td class='right'><div>" . $data['service_advance_time'] . " Stunden</div></td></tr>";
    }
    if (isset($data['service_time']) && !empty(trim($data['service_time']))) {
      $downtime_service_data .= "<tr><td class='left'><div><b>" . t("Service Time:") . "</b></div></td><td class='right'><div><table><tr><th>" . t("Day") . "</th><th>" . t("Start Time") . "</th><th>" . t("End Time") . "</th></tr>" . $data['service_time'] . "</table></div></td></tr>";
    }
    if (isset($data['maintenance_windows_time']) && !empty($data['maintenance_windows_time'])) {
      $downtime_service_data .= "<tr><td class='left'><div><b>" . t("Maintenance Windows:") . "</b></div></td><td class='right'><div><table><tr><th>" . t("Day") . "</th><th>" . t("Start Time") . "</th><th>" . t("Day") . "</th><th>" . t("End Time") . "</th></tr>" . $data['maintenance_windows_time'] . "</table></div></td></tr>";
    }
    if (isset($data['service_impact']) && trim($data['service_impact']) != '') {
      $downtime_service_data .= "<tr><td class='left'><div><b>" . t("Impact:") . "</b></div></td><td class='right'><div>" . $data['service_impact'] . "</div></td></tr>";
    }
    $downtime_service_data .= "</table>";
    return $downtime_service_data;
  }

  /**
   *
   */
  static public function downtime_services_names($service) {
    $ids = explode(',', $service);
    $query = \Drupal::database()->select('node_field_data', 'n');
    $query->fields('n', ['title']);
    $query->condition('n.nid', $ids, 'IN');
//    echo $query->__toString();
    return $query->execute()->fetchCol();
//    $service_name = $query->execute()->fetchCol();
//pr($service_name);exit;
//    return $service;
  }

  /**
   *
   */
  static public function resolve_link_display($content_state_id = NULL, $owner_id = NULL) {
    $user = \Drupal::currentUser();
    $group_id = \Drupal::routeMatch()->getParameter('group')->id();
    $owner_state = db_query('SELECT state_id FROM {cust_profile} WHERE uid = :id', array('id' => $user->id()))->fetchField();
    if ($owner_state) {
      $current_user_state_id = $owner_state;
    }
    else {
      $current_user_state_id = '';
    }
    // dsm(\Drupal\user\Entity\User::load($owner_id));
    $owner_state = db_query('SELECT state_id FROM {cust_profile} WHERE uid = :id', array('id' => $owner_id))->fetchField();
    if ($owner_state) {
      $owner_state_id = $owner_state;
    }
    else {
      $owner_state_id = '';
    }
    $is_group_admin = CustNodeController::isGroupAdmin($group_id);
    array_push($content_state_id, $owner_state_id);
    if (in_array(SITE_ADMIN_ROLE, $user->getRoles())) {
      return TRUE;
    }
    elseif ($is_group_admin) {
      return TRUE;
    }
    elseif (in_array($current_user_state_id, $content_state_id)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   *
   */
  static public function reset_form() {
    return $form['reset'] = array(
      '#type' => 'button',
      '#value' => t('Reset'),
      // '#attributes' => array('onclick' => "reset_form_elements()"),
      '#prefix' => " <div class = 'reset_form'><div class = 'reset_all'>",
      '#suffix' => '</div><div style = "clear:both"></div> </div>',
    );
  }

  /**
   *
   */
  static public function current_incidents($sql_where, $string = NULL, $service_id = NULL, $search_string = NULL, $limit = NULL, $state_id = NULL, $end_date = NULL) {
    /**
     * to do need to clean up code 
     */
    $user = \Drupal::currentUser();
    $group = \Drupal::routeMatch()->getParameter('group');
    $group_id = $group->id();

    /*    $reasons = array(
      t('Please select a reason here'),
      t('Urgency of the maintenance'),
      t('No staff available during maintenance hours'),
      t('No service partner (State) available during maintenance hours'),
      t('External service partner required'),
      t('Internal regulations do not allow maintenances during KONSENS maintenance windows'),
      t('Public holiday or weekend'),
      t('No service partner (KONSENS) available during maintenance hours'),
      t('No service interruption planned'),
      ); */

    if (isset($_REQUEST['services_effected']) && !empty($_REQUEST['services_effected'])) {
      $service_id = $_REQUEST['services_effected'];
    }
    else {
      $group_downtimes_view_service_query = db_select('group_downtimes_view', 'gdv');
      $group_downtimes_view_service_query->Fields('gdv', array('service_id'));
      $group_downtimes_view_service_query->condition('group_id', $group_id, '=');
      $group_downtimes_view_service_query->condition('service_id', 0, '!=');
      $group_downtimes_view_service = $group_downtimes_view_service_query->execute()->fetchCol();
      $group_downtimes_view_services_ids = implode(",", $group_downtimes_view_service);
    }
    if (!empty($_REQUEST['states'])) {
      $states = $_REQUEST['states'];
    }

    /* $serialized_data = unserialize($_SESSION['downtimes_query']);
      if ($string == $serialized_data['downtime_type']) {
      $sql_where = $serialized_data['sql'] ? $serialized_data['sql'] : $sql_where;
      $service_id = $serialized_data['service'] ? $serialized_data['service'] : $service_id;
      $state_id = $serialized_data['state'] ? $serialized_data['state'] : $state_id;
      $search_string = $serialized_data['search_string'] ? $serialized_data['search_string'] : $search_string;
      $limit = $serialized_data['limit'];
      unset($_SESSION['downtimes_query']);
      } */

    // drupal_add_css(drupal_get_path('module', 'downtimes') . '/downtimes_tables.css');.
    $states = self::get_all_user_state_abbr();

    /* $pagination = $_GET['pagination'];
      $pos_slash = strripos($_GET['q'], '/');
      $url_flag = substr($_GET['q'], $pos_slash + 1); */
    $sort_order = ($string == 'maintenance' ? 'asc' : 'desc');

    if (isset($service_id) && $service_id != 0) {

      $service = "  FIND_IN_SET(gdv.service_id, ds.service_id) and gdv.service_id = $service_id";
    }
    else {
      if (empty($group_downtimes_view_services_ids)) {
        $group_downtimes_view_services_ids = '-1';
      }

      $service = "  FIND_IN_SET(gdv.service_id, ds.service_id)  and  gdv.service_id  in ($group_downtimes_view_services_ids)";
    }
    if (isset($state_id) && $state_id != 1) {
      $state = " ( ds.state_id LIKE '" . $state_id . ",%' or ds.state_id LIKE '%," . $state_id . ",%' or  ds.state_id LIKE '%," . $state_id . "' ) ";
    }
    else {
      $state = " ds.state_id != 0";
    }
    if ($string == 'archived') {
      $sql_where = str_replace('and resolved = 1', 'and (resolved = 1 or cancelled = 1)', $sql_where);
      $select = "select group_concat(distinct title separator'<br>') as service,
                       if(scheduled_p = 1, 'MAINTENANCE', 'INCIDENT') as type,
                       n.uid, downtime_id, sd.startdate_reported, sd.enddate_reported,group_concat(distinct s.abbr separator', ') as abbr, description, startdate_planned,oa.id as group_content_id";

      if (isset($end_date) && !empty($end_date)) {
        $end_date = explode('.', $end_date);
        $day = $end_date[0];
        $month = $end_date[1];
        $year = $end_date[2];
        $filter_end_date = mktime(23, 59, 59, $month, $day, $year);
      }

      if (isset($group_id)) {
        $inner_where = " where $service and $state and group_id = $group_id ";
        $inner_select = "select distinct(gdv.service_id) as state_service_id from {group_downtimes_view} gdv, {downtimes} ds $inner_where ";
        $sql_select = "$select,sd.cancelled from {downtimes} sd,
                       {node_field_data} n, {group_content_field_data} oa,
                       {states} s where sd.service_id = n.nid and (sd.resolved = 1 or sd.cancelled = 1) and
                       sd.downtime_id = oa.entity_id and oa.type like '%group_node%' and s.id= $state_id  and
                       sd.service_id in
                             ($inner_select) ";
        if (!empty($filter_end_date)) {
          $sql_select .= " and (ri.end_date <= $filter_end_date) ";
        }
      }
      else {
        $inner_where = " where $service ";
        $inner_select = "select service_id as state_service_id from {group_downtimes_view} $inner_where ";
        $sql_select = "$select,sd.cancelled from {downtimes} sd,
                       {node_field_data} n,{states} s
                       where sd.service_id = n.nid and (sd.resolved = 1 or sd.cancelled = 1) and s.id= $state_id  and
                       sd.service_id in
                             ($inner_select) ";
        if (!empty($filter_end_date)) {
          $sql_select .= " and (ri.end_date <= $filter_end_date) ";
        }
      }
    }
    else {
      $sql_where .= " and sd.cancelled = 0";
      $select = "select distinct(sd.downtime_id),sd.startdate_reported, sd.enddate_reported, sd.downtime_id,n.uid,sd.downtime_id, sd.description, sd.startdate_planned, sd.enddate_planned, sd.service_id,
        if(sd.scheduled_p = 1, 'MAINTENANCE', 'INCIDENT') as type,
        sd.state_id,oa.id as group_content_id ";

      if (isset($group_id)) {
        $inner_where = " where $service and $state and group_id =  " . $group_id;
        $inner_select = "select distinct(ds.service_id) as state_service_id from {group_downtimes_view} gdv, {downtimes} ds $inner_where ";
        $sql_select = "$select from {downtimes} sd, {node_field_data} n, {group_content_field_data} oa,  {states} s
                 where sd.service_id = n.nid and
                       sd.downtime_id = oa.entity_id and
                       sd.service_id in
                             ($inner_select) ";
      }
      else {
        $inner_where = " where $service and $state";
        $inner_select = "select distinct(service_id) as state_service_id from {group_downtimes_view} gdv $inner_where ";
        $sql_select = "$select from {downtimes} sd, {node} n, {states} s
                 where sd.service_id = n.nid and s.id= $state_id  and
                       sd.service_id in
                             ($inner_select) ";
      }
    }
    $sql_group_by = " group by sd.downtime_id,sd.service_id,sd.state_id,n.uid,sd.downtime_id,oa.id, sd.description, sd.startdate_reported, sd.enddate_reported, sd.startdate_planned, sd.enddate_planned, sd.scheduled_p,sd.cancelled order by sd.startdate_planned $sort_order";

    $sql = $sql_select . $sql_where . $sql_group_by;
    // Table header.
    $header = array();
    array_push($header, array('data' => t('Service'), 'class' => 'service'));
    $url = '';
    if ($string == 'archived') {
      // array_push($header, array('data' => t('Flag'), 'class' => 'flag'));.
      $url = 'node/' . $group_id . '/downtimes';
    }
    if ($string == 'archived') {
      if ($group->label()) {
        $url = 'node/' . $group_id . '/downtimes/archived_downtimes';
      }
      array_push($header, array('data' => t('Type'), 'class' => 'type'));
    }
    array_push($header, array('data' => t('State'), 'class' => 'state'));
    array_push($header, array('data' => t('Start'), 'class' => 'start'));
    array_push($header, array('data' => t('End'), 'class' => 'end'));
    array_push($header, array('data' => t('Reported By'), 'class' => 'reported_by'));
    array_push($header, array('data' => t('Action'), 'class' => 'action'));

    $master_group = INCEDENT_MANAGEMENT;

    $output = "<br>";
    if ($string == 'archived') {
//     $limit = 5;
      $limit = ($limit ? $limit : PAGE_LIMIT);
      if ($limit != 'all') {
        $query = db_select('downtimes', 'sd');
        $query->Fields('sd', array('cancelled', 'resolved', 'state_id', 'service_id', 'startdate_reported', 'enddate_reported', 'downtime_id', 'description', 'startdate_planned', 'enddate_planned', 'scheduled_p'));
        // $query->addExpression("Group_concat(DISTINCT s.abbr SEPARATOR', ')", 'abbr');
        // $query->addExpression("Group_concat(DISTINCT title SEPARATOR' ')", 'service');.
        $query->addExpression("IF(scheduled_p = 1, 'MAINTENANCE', 'INCIDENT')", 'type');
        $query->addExpression("oa.id", 'group_content_id');
        $query->join('node_field_data', 'n', 'sd.service_id = n.nid');
        $query->join('group_content_field_data', 'oa', 'sd.downtime_id = oa.entity_id');
        $query->join('resolve_cancel_incident', 'ri', 'ri.downtime_id = sd.downtime_id');
        // $query->join('states', 's', 's.id=sd.state_id');.
        $query->groupBy('sd.service_id, sd.state_id, sd.downtime_id,oa.id, n.uid, sd.downtime_id, sd.description, sd.startdate_reported, sd.enddate_reported, sd.startdate_planned, sd.enddate_planned, sd.scheduled_p, sd.cancelled, sd.resolved ');
        $query->orderBy('sd.downtime_id', 'desc');
        $query->where('sd.service_id = n.nid AND (sd.resolved = 1 OR sd.cancelled = 1) AND sd.service_id IN (SELECT gdv.service_id AS state_service_id FROM   {group_downtimes_view} gdv,  {downtimes} ds WHERE  ' . $service . ' AND ' . $state . ' AND group_id = ' . $group_id . ' ) ' . $sql_where);
        $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($limit);
        $result = $pager->execute();
      }
      else {
        $result = db_query($sql, $search_string)->fetchAll();
      }
    }
    else {

      if ($search_string) {
        $result = db_query($sql, $search_string)->fetchAll();
      }
      else {
        $result = db_query($sql)->fetchAll();
      }
    }
    $rows = array();
    $renderer = \Drupal::service('renderer');
    foreach ($result as $client) {
//      pr($client);exit;
      $services = self::downtime_services_names($client->service_id);
      // $user_state = display_update($states[$client->state_id]);.
      $user_state = db_query("SELECT Group_concat(DISTINCT abbr SEPARATOR', ') FROM {states} WHERE id IN (" . $client->state_id . ")")->fetchField();
      $startdate = ($client->startdate_planned ? date('d-m-Y H:i', $client->startdate_planned) : "unbekannt");
      if ($string == 'archived') {
        if (isset($client->cancelled) && $client->cancelled == 1) {
          $enddate_cancelled = db_query("select end_date,date_reported from {resolve_cancel_incident} where downtime_id = ?", array($client->downtime_id))->fetchObject();
          if (!empty($enddate_cancelled)) {
            if (!empty($enddate_cancelled->end_date)) {
              $enddate = date("d-m-Y H:i", $enddate_cancelled->end_date);
            }
            else {
              $enddate = date("d-m-Y H:i", $enddate_cancelled->date_reported);
            }
          }
          else {
            $enddate = ($client->enddate_planned ? date("d-m-Y H:i", $client->enddate_planned) : "");
          }
        }
        else {
          $enddate_resolved = db_query("select end_date,date_reported from {resolve_cancel_incident} where downtime_id = ?", array($client->downtime_id))->fetchObject();
          if (!empty($enddate_resolved)) {
            if (!empty($enddate_resolved->end_date)) {
              $enddate = date("d-m-Y H:i", $enddate_resolved->end_date);
            }
            else {
              $enddate = date("d-m-Y H:i", $enddate_resolved->date_reported);
            }
          }
          else {
            $enddate = ($client->enddate_planned ? date("d-m-Y H:i", $client->enddate_planned) : "");
          }
        }
      }
      else {
        $enddate = ($client->enddate_planned ? date("d-m-Y H:i", $client->enddate_planned) : "");
      }
      $reporter_uid = db_query("SELECT uid FROM {node_field_data} WHERE nid = $client->downtime_id")->fetchField();
//      $name = db_query("select concat(firstname,' ',lastname) as name from {cust_profile} where uid = $reporter_uid")->fetchField();
//      $user_url = Url::fromUserInput('/user/' . $reporter_uid);
//      $user_name = ($user->id() ? \Drupal::l($name, $user_url) : $name);

      $downtime_ids = array();
      $downtime_ids = explode(',', $client->state_id);
      $show_resolve = self::resolve_link_display($downtime_ids, $reporter_uid);
      $maintenance_group = \Drupal\group\Entity\Group::load(MAINTENANCE_GROUP_ID);
      // $maintenance_edit = saved_quickinfo_og_is_member(MAINTENANCE_GROUP_ID);
      if ($maintenance_group->getMember(\Drupal::currentUser()) || \Drupal::currentUser()->id() == 1) {
        $maintenance_edit = TRUE;
      }
      else {
        $maintenance_edit = FALSE;
      }
      if ($string == 'archived' && isset($client->cancelled) && $client->cancelled == 1) {
        $flag = "<span class='cancelled-downtime'>" . t('Cancelled') . "</span>";
      }
      else {
        $flag = "";
      }
      $elements = $headersNew = [];
      $downtimeTypes = [0 => 'Incident', 1 => 'Maintenance'];
      if ($string == 'archived') {
        $headersNew = array_merge($headersNew, ['type' => 'Type']);
        $elements = array_merge($elements, ['type' => $downtimeTypes[$client->scheduled_p]]);
      }
      $serviceList = ['#items' => $services, '#theme' => 'item_list', '#type' => 'ul'];
      $elements = array_merge($elements, array(
        'description' => Markup::create(Unicode::truncate($client->description, 100, TRUE, TRUE, 1)),
        'service' => $renderer->render($serviceList),
        'state' => $user_state));
      $headersNew = array_merge($headersNew, ['description' => 'Beschreibung', 'service' => 'Verfahren', 'state' => 'Land']);
      if ($string == 'archived') {
        $headersNew = array_merge($headersNew, ['status' => 'Status']);
        $status = null;
        if ($client->cancelled) {
          $status = t('Storniert');
        }
        if ($client->resolved) {
          $status = t('Behoben');
        }
        $elements = array_merge($elements, ['status' => $status]);
      }
      $headersNew = array_merge($headersNew, ['start_date' => 'Beginn', 'end_date' => 'Ende']);
      $elements = array_merge($elements, array(
        'start_date' => $startdate,
        'end_date' => $enddate,
//        'name' => $user_name,
      ));

      $query_params = array(
        'nid' => $client->downtime_id,
        'sql' => $sql_where,
        'state' => $state_id,
        'service' => $service_id,
        'url' => $url,
        'search_string' => $search_string,
        'from' => 1,
        'downtime_type' => $string,
        'limit' => $limit,
      );

      if (isset($_REQUEST['type'])) {
        $query_params['type'] = $_REQUEST['type'];
      }
      else {
        $query_params['type'] = "";
      }
      if (isset($_REQUEST['filter_startdate'])) {
        $query_params['filter_startdate'] = $_REQUEST['filter_startdate'];
      }
      else {
        $query_params['filter_startdate'] = "";
      }
      if (isset($_REQUEST['filter_enddate'])) {
        $query_params['filter_enddate'] = $_REQUEST['filter_enddate'];
      }
      else {
        $query_params['filter_enddate'] = "";
      }
      if (isset($_REQUEST['time_period'])) {
        $query_params['time_period'] = $_REQUEST['time_period'];
      }
      else {
        $query_params['time_period'] = "";
      }
      $query_seralized = serialize($query_params);
      // $_SESSION['downtimes_query'] = $query_seralized;.
//      $downtime_url = Url::fromUserInput('/group/' . $group_id . '/node/' . $client->group_content_id);
//      $downtime_url = Url::fromRoute('entity.group_content.group_node__deployed_releases.canonical', ['group' => $group_id, 'group_content' => $client->group_content_id], ['attributes' => ['class' => ['downtimes_details_link']]]);
//      $links = Link::fromTextAndUrl(t('Details'), $downtime_url)->toString();
      $links = [];
      $links['action']['view'] = [
        '#title' => t('Details'),
        '#type' => 'link',
        '#url' => Url::fromRoute('entity.group_content.group_node__deployed_releases.canonical', ['group' => $group_id, 'group_content' => $client->group_content_id], ['attributes' => ['class' => ['downtimes_details_link']]])
      ];
//      $links = \Drupal::l(t('Details')->__toString(), $downtime_url, $params = array(
//            'attributes' => array(
//              'class' => 'downtimes_details_link',
//              'nid' => $client->downtime_id,
//              'query' => $query_seralized,
//            ),
//              )
//      );

      $downtime_type = db_query("SELECT scheduled_p FROM {downtimes} WHERE downtime_id = $client->downtime_id")->fetchField();
      if ($downtime_type == 1) {
        if ($maintenance_edit && ($master_group == $group_id) && $string != 'archived') {
//          $resolve_url = Url::fromUserInput('/group/' . $group_id . '/resolve' . '/' . $client->downtime_id);
          $links['action']['resolve'] = [
            '#title' => t('Resolve'),
            '#type' => 'link',
            '#url' => Url::fromRoute('downtimes.resolve', ['group' => $group_id, 'node' => $client->downtime_id], ['attributes' => ['class' => ['downtimes_resolve_link']]])
          ];
//          $cancel_url = Url::fromUserInput('/group/' . $group_id . '/cancel' . '/' . $client->downtime_id);
          $links['action']['cancel'] = [
            '#title' => t('Cancel Maintenance'),
            '#type' => 'link',
            '#url' => Url::fromRoute('downtimes.cancel', ['group' => $group_id, 'node' => $client->downtime_id], ['attributes' => ['class' => ['downtimes_cancel_link']]])
          ];
//          $edit_url = Url::fromUserInput('/node/' . $client->downtime_id . '/edit');
          $links['action']['edit'] = [
            '#title' => t('Update'),
            '#type' => 'link',
            '#url' => Url::fromRoute('entity.node.edit_form', [ 'node' => $client->downtime_id], ['attributes' => ['class' => ['downtimes_update_link']]])
          ];
//          $links .= "<br>" . \Drupal::l(t('Update'), $edit_url) . " <br>";
          // l(t('Resolve'), 'node/' . $_SESSION['Group_id'] . '/resolve' . '/' . $client->downtime_id);.
//          if ($client->startdate_planned > time()) {
//            $links .= \Drupal::l(t('Cancel Maintenance'), $cancel_url);
//          }
//          else {
//            $links .= \Drupal::l(t('Resolve'), $resolve_url);
//          }
        }
      }
      else {
        if ($show_resolve && ($master_group == $group_id)) {
          if ($string != 'archived') {
            $links['action']['edit'] = [
              '#title' => t('Update'),
              '#type' => 'link',
              '#url' => Url::fromRoute('entity.node.edit_form', [ 'node' => $client->downtime_id], ['attributes' => ['class' => ['downtimes_update_link']]])
            ];
            $links['action']['resolve'] = [
              '#title' => t('Resolve'),
              '#type' => 'link',
              '#url' => Url::fromRoute('downtimes.resolve', ['group' => $group_id, 'node' => $client->downtime_id], ['attributes' => ['class' => ['downtimes_resolve_link']]])
            ];

//            $update_url = Url::fromUserInput('/node/' . $client->downtime_id . '/edit');
//            $resolve_url = Url::fromUserInput('/group/' . $group_id . '/resolve' . '/' . $client->downtime_id);
//            $links .= "<br>" . \Drupal::l(t('Update'), $update_url) . " <br>" . \Drupal::l(t('Resolve'), $resolve_url);
            // array_push($elements, \Drupal::l(t('Update'), $group->label() . '/downtimes/' . $client->downtime_id . '/edit') . "<br>" . l(t('Resolve'), $_SESSION['Group_name'] . '/resolve' . '/' . $client->downtime_id));.
          }
        }
      }
      $headersNew = array_merge($headersNew, ['action' => 'Action']);
      $elements['action'] = $renderer->render($links);
//      $elements['table_type'] = $string;
      $rowClass = '';
      if ($client->startdate_planned > REQUEST_TIME || $client->type == 'INCIDENT') {
        $rowClass = 'text-danger';
      }
      $rows[] = ['data' => $elements, 'class' => $rowClass];
    }
//    pr($rows);exit;
    /* if (!$rows) {
      $rows['no_data'] = array(array('data' => t('No data created yet.'), 'colspan' => 3));
      } */
    // $output .= theme('downtimes_table', $header, $rows, array('id' => 'sortable', 'class' => "tablesorter downtimes_$string"), $string);
    /* if ($string == 'archived') {
      $output .= theme('pager', NULL, $limit, 0);
      $output .= \Drupal::formBuilder()->getForm('archive_rows_per_page', $limit, $string);
      } */
    
    $variables = array('header' => $headersNew, 'rows' => $rows, 'footer' => NULL, 'attributes' => array(), 'caption' => NULL, 'colgroups' => array(), 'sticky' => FALSE, 'responsive' => TRUE, 'empty' => 'No data created yet.');
    self::downtimes_display_table($variables);
    $build['problem_table'] = array(
      '#header' => $variables['header'],
      '#rows' => $rows,
      '#attributes' => $variables['attributes'],
      '#empty' => $variables['empty'],
//      '#header_columns' => $variables['header_columns'],
      '#type' => 'table',
    );
    $build['pager'] = array(
      '#type' => 'pager',
      '#prefix' => '<div id="pagination">',
      '#suffix' => '</div>',
    );
    return $build;
  }

  /**
   *
   */
  static public function downtimes_display_table(&$variables) {
    // Format the table columns:
    if (!empty($variables['colgroups'])) {
      foreach ($variables['colgroups'] as &$colgroup) {
        // Check if we're dealing with a simple or complex column.
        if (isset($colgroup['data'])) {
          $cols = $colgroup['data'];
          unset($colgroup['data']);
          $colgroup_attributes = $colgroup;
        }
        else {
          $cols = $colgroup;
          $colgroup_attributes = array();
        }
        $colgroup = array();
        $colgroup['attributes'] = new Attribute($colgroup_attributes);
        $colgroup['cols'] = array();

        // Build columns.
        if (is_array($cols) && !empty($cols)) {
          foreach ($cols as $col_key => $col) {
            $colgroup['cols'][$col_key]['attributes'] = new Attribute($col);
          }
        }
      }
    }

    // Build an associative array of responsive classes keyed by column.
    $responsive_classes = array();

    // Format the table header:
    $ts = array();
    $header_columns = 0;
    if (!empty($variables['header'])) {
      $ts = tablesort_init($variables['header']);

      // Use a separate index with responsive classes as headers
      // may be associative.
      $responsive_index = -1;
      foreach ($variables['header'] as $col_key => $cell) {
        // Increase the responsive index.
        $responsive_index++;

        if (!is_array($cell)) {
          $header_columns++;
          $cell_content = $cell;
          $cell_attributes = new Attribute();
          $is_header = TRUE;
        }
        else {
          if (isset($cell['colspan'])) {
            $header_columns += $cell['colspan'];
          }
          else {
            $header_columns++;
          }
          $cell_content = '';
          if (isset($cell['data'])) {
            $cell_content = $cell['data'];
            unset($cell['data']);
          }
          // Flag the cell as a header or not and remove the flag.
          $is_header = isset($cell['header']) ? $cell['header'] : TRUE;
          unset($cell['header']);

          // Track responsive classes for each column as needed. Only the header
          // cells for a column are marked up with the responsive classes by a
          // module developer or themer. The responsive classes on the header cells
          // must be transferred to the content cells.
          if (!empty($cell['class']) && is_array($cell['class'])) {
            if (in_array(RESPONSIVE_PRIORITY_MEDIUM, $cell['class'])) {
              $responsive_classes[$responsive_index] = RESPONSIVE_PRIORITY_MEDIUM;
            }
            elseif (in_array(RESPONSIVE_PRIORITY_LOW, $cell['class'])) {
              $responsive_classes[$responsive_index] = RESPONSIVE_PRIORITY_LOW;
            }
          }

          tablesort_header($cell_content, $cell, $variables['header'], $ts);

          // tablesort_header() removes the 'sort' and 'field' keys.
          $cell_attributes = new Attribute($cell);
        }
        $variables['header'][$col_key] = array();
        $variables['header'][$col_key]['tag'] = $is_header ? 'th' : 'td';
        $variables['header'][$col_key]['attributes'] = $cell_attributes;
        $variables['header'][$col_key]['content'] = $cell_content;
      }
    }
    $variables['header_columns'] = $header_columns;

    // Rows and footer have the same structure.
    $sections = array('rows', 'footer');
    foreach ($sections as $section) {
      if (!empty($variables[$section])) {
        foreach ($variables[$section] as $row_key => $row) {
          $cells = $row;
          $row_attributes = array();

          // Check if we're dealing with a simple or complex row.
          if (isset($row['data'])) {
            $cells = $row['data'];
            $variables['no_striping'] = isset($row['no_striping']) ? $row['no_striping'] : FALSE;

            // Set the attributes array and exclude 'data' and 'no_striping'.
            $row_attributes = $row;
            unset($row_attributes['data']);
            unset($row_attributes['no_striping']);
          }

          // Build row.
          $variables[$section][$row_key] = array();
          $variables[$section][$row_key]['attributes'] = new Attribute($row_attributes);
          $variables[$section][$row_key]['cells'] = array();
          if (!empty($cells)) {
            // Reset the responsive index.
            $responsive_index = -1;
            foreach ($cells as $col_key => $cell) {
              // Increase the responsive index.
              $responsive_index++;

              if (!is_array($cell)) {
                $cell_content = $cell;
                $cell_attributes = array();
                $is_header = FALSE;
              }
              else {
                $cell_content = '';
                if (isset($cell['data'])) {
                  $cell_content = $cell['data'];
                  unset($cell['data']);
                }

                // Flag the cell as a header or not and remove the flag.
                $is_header = !empty($cell['header']);
                unset($cell['header']);

                $cell_attributes = $cell;
              }
              // Active table sort information.
              if (isset($variables['header'][$col_key]['data']) && $variables['header'][$col_key]['data'] == $ts['name'] && !empty($variables['header'][$col_key]['field'])) {
                $variables[$section][$row_key]['cells'][$col_key]['active_table_sort'] = TRUE;
              }
              // Copy RESPONSIVE_PRIORITY_LOW/RESPONSIVE_PRIORITY_MEDIUM
              // class from header to cell as needed.
              if (isset($responsive_classes[$responsive_index])) {
                $cell_attributes['class'][] = $responsive_classes[$responsive_index];
              }
              $variables[$section][$row_key]['cells'][$col_key]['tag'] = $is_header ? 'th' : 'td';
              $variables[$section][$row_key]['cells'][$col_key]['attributes'] = new Attribute($cell_attributes);
              $variables[$section][$row_key]['cells'][$col_key]['content'] = $cell_content;
            }
          }
        }
      }
    }
    if (empty($variables['no_striping'])) {
      $variables['attributes']['data-striping'] = 1;
    }
  }

  /**
   * Display all non production lists.
   */
  static public function get_non_productions_list() {
    // $non_productions_lists = db_query("SELECT n.nid, n.title,
    //  ctn.field_non_production_state_value "
    //      . "FROM {node} n, {content_type_non_production_environment} ctn "
    //      . "WHERE n.nid = ctn.nid and type = '%s'", 'non_production_environment');
    // .
    $query = \Drupal::database()->select('node_field_data', 'nfd');
    $query->leftJoin('node__field_non_production_state', 'nfnpsv', 'nfd.nid = nfnpsv.entity_id');
    $query->fields('nfd', ['nid', 'title']);
    $query->fields('nfnpsv', ['field_non_production_state_value']);
    $query->condition('nfd.type', 'non_production_environment');
    $non_productions_lists = $query->execute()->fetchAll();

    $header = array(t('State'), t('Environment'), t('Operation'));
    foreach ($non_productions_lists as $row) {
      $query = \Drupal::database()->select('states', 's');
      $query->Fields('s', array('state'));
      $query->condition('s.id', $row->field_non_production_state_value);
      $state = $query->execute()->fetchField();

      $route_name = 'entity.node.edit_form';
      $url = Url::fromRoute($route_name, array(
            'node' => $row->nid,
              )
      );

      $edit = Link::fromTextAndUrl('Edit', $url);

      $elements = array(
        'state' => $state,
        'environment' => $row->title,
        'edit' => $edit,
      );
      $rows[] = $elements;
    }
    if (!isset($elements)) {
      $output[] = t('No Data to be displayed');
      return $output;
    }
    $output = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array(
        'id' => 'non-production-env',
        'class' => 'non-production-env',
      ),
      '#prefix' => '<div id="non_production_state_wrapper">',
      '#suffix' => '</div>',
    );

    return $output;
  }

}
