<?php

namespace Drupal\hzd_customizations;

use Drupal\Component\Datetime\DateTimePlus;
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
use Drupal\node\Entity\Node;

/* if (!defined('MAINTENANCE_GROUP_ID')) {
  define('MAINTENANCE_GROUP_ID', \Drupal::config('downtimes.settings')->get('maintenance_group_id'));
  } */

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
          \Drupal::database()
            ->update('menu_link_content_data')
            ->fields(array('enabled' => 1))
            ->condition('id', $group_link, '=')
            ->execute();
          
          // Need to clear the menu cache to get the new menu item affected.
          menu_cache_clear_all();
        }
      }
    }
    else {
      // All links were unset. So we need to make the menu item hidden.
      if ($group_link) {
        // db_query("UPDATE {menu_links} set hidden = %d WHERE mlid = %d", 1, $group_link);.
        \Drupal::database()
          ->update('menu_link_content_data')
          ->fields(array('enabled' => 0))
          ->condition('id', $group_link, '=')
          ->execute();
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
      $query = \Drupal::database()
        ->select('node__field_dependent_service', 'nfds');
      $query->addField('nfds', 'entity_id');
      $query->condition('nfds.field_dependent_service_target_id', $service->nid);
      $query->range(0, 1);
      $id = $query->execute()->fetchField();
      $current_uri = \Drupal::request()->getRequestUri();
      if ($id) {
        $query = \Drupal::database()->select('url_alias', 'ua');
        $query->addField('ua', 'alias');
        $query->condition('ua.source', '/node/' . $id);
        $query->range(0, 1);
        $path_alias = $query->execute()->fetchField();
        $text = $service->service;
        // $url = Url::fromUserInput($path_alias . '/edit');.
        
        $url = Url::fromUserInput('/node/' . $id . '/edit?destination=' . $current_uri);
        $data[] = \Drupal::l($text, $url);
      }
      else {
        $text = $service->service;
        $url = Url::fromUserInput('/node/' . MAINTENANCE_GROUP_ID . '/add/service_profile?service=' . $service->nid . '&destination=' . $current_uri);
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
      $query = \Drupal::database()
        ->select('node__field_dependent_service', 'nfds');
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
      $query = \Drupal::database()
        ->select('node__field_dependent_service', 'nfds');
      $query->addField('nfds', 'entity_id');
      $query->condition('nfds.field_dependent_service_target_id', $service_nid);
      $query->range(0, 1);
      $id = $query->execute()->fetchField();
      $sdata = self::get_service_data($service_nid, $service);
      if ($id && !empty($sdata) && $option_type != 'select') {
        $c_data = trim($service) . "|<span class='downtimes-service-tooltip' id = '" . $service_nid . "'><img height=10 src = '/" . $img . "'></span><div class='downtimes-service-profile-data service-profile-data-" . $service_nid . "' style='display:none'><div class='wrapper'><div class='service-profile-close' style='' id='close-" . $service_nid . "'><a id='service-profile-close'>Close</a></div>" . $sdata . "</div></div>";
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
    
    $services = \Drupal::entityQuery('node')
      ->condition('field_dependent_downtimeservices', $serviceId)
      ->execute();
    $service = \Drupal\node\Entity\Node::loadMultiple($services);
//    $dependantServicesList = $service->get('field_dependent_services')->getValue();
    $dependantServices = [];
    foreach ($service as $val) {
      $dependantServices[] = $val->get('field_dependent_service')
        ->referencedEntities()[0]->id();
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
    
    $query = \Drupal::database()
      ->select('node__field_dependent_service', 'nfd');
    $query->fields('nfd', ['entity_id']);
    $query->fields('nfi', ['field_impact_value']);
    $query->fields('nfmat', ['field_maintenance_advance_time_value']);
    $query->leftJoin('node__field_impact', 'nfi', 'nfi.entity_id = nfd.entity_id');
    $query->leftJoin('node__field_maintenance_advance_time', 'nfmat', 'nfmat.entity_id = nfd.entity_id');
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
    $query = \Drupal::database()
      ->select('node__field_service_recipient', 'nfd');
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
    $query = \Drupal::database()
      ->select('node__field_dependent_downtimeservices', 'nfd');
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
    $query = \Drupal::database()
      ->select('service_profile_maintenance_service_time', 'nfd');
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
        if ($flag == 1) {
          $service_vals .= "<tr>" . $service_vals_data . "</tr>";
        }
      }
      $data['service_time'] = $service_vals;
    }
    
    // Maintenance windows time.
    $query = \Drupal::database()
      ->select('service_profile_maintenance_windows', 'nfd');
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
    $group = \Drupal\group\Entity\Group::load(INCIDENT_MANAGEMENT);
    $group_id = $group->id();
    if (!$group->getMember($user)) {
      return FALSE;
    }
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
//    array_push($content_state_id, $owner_state_id);
    if (in_array(SITE_ADMIN_ROLE, $user->getRoles())) {
      return TRUE;
    }
    elseif ($is_group_admin) {
      return TRUE;
    }
    elseif (in_array($current_user_state_id, $content_state_id)) {
      return TRUE;
    }
    elseif ($owner_id == $user->id()) {
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
  static public function current_incidents($type = 'incident', $string = '') {
    //Make sure to print only necessary data for print formats
    $filterData = \Drupal::request()->query;
    $isPrintFormat = $filterData->has('print');
    $group = \Drupal::routeMatch()->getParameter('group');
    $current_group_id = (int) $group->id();
    $group_id = INCIDENT_MANAGEMENT;
    ///// optimizd the code for filters things
    $db = \Drupal::database();
    $downtimesQuery = $db->select('downtimes', 'd');
    $downtimesQuery->addJoin('INNER', 'node_field_data', 'nfd', 'nfd.nid = d.downtime_id');
    $downtimesQuery->addJoin('INNER', 'group_content_field_data', 'gcfd', 'gcfd.entity_id = d.downtime_id');
    if ($type == 'archived') {
      $downtimesQuery->addJoin('LEFT', 'resolve_cancel_incident', 'rci', 'rci.downtime_id = d.downtime_id');
    }
    $downtimesQuery = $downtimesQuery->condition('gcfd.type', '%group_node%', 'LIKE');
    $downtimesQuery = $downtimesQuery->condition('gcfd.gid', $group_id);
    
    $exposedFilterData = $filterData->all();
    unset($exposedFilterData['form_build_id']);
    unset($exposedFilterData['form_id']);
    if ($type != 'archived') {
      $types = ['incident' => 0, 'maintenance' => 1];
      $downtimesQuery = $downtimesQuery->condition('d.scheduled_p', $types[$type]);
    }
    if ($filterData->has('type') && $filterData->get('type') != 'select') {
      $downtimesQuery = $downtimesQuery->condition('d.scheduled_p', $filterData->get('type'));
    }
    if ($filterData->has('string') && $filterData->get('string') != '') {
      $downtimesQuery = $downtimesQuery->condition('d.description', "%{$filterData->get('string')}%", 'LIKE');
    }
    $startDate = $endDate = NULL;
    if ($filterData->has('filter_startdate') && $filterData->get('filter_startdate') != '') {
      $startDate = DateTimePlus::createFromFormat('d.m.Y|', $filterData->get('filter_startdate'), NULL, ['validate_format' => FALSE])
        ->getTimestamp();
    }
    if ($filterData->has('filter_enddate') && $filterData->get('filter_enddate') != '') {
      $endDate = DateTimePlus::createFromFormat('d.m.Y|', $filterData->get('filter_enddate'), NULL, ['validate_format' => FALSE])
          ->getTimestamp() + 86399;
    }
    
    if ($filterData->has('time_period') && $filterData->get('time_period') != 0) {
      $time_period = $filterData->get('time_period');
      switch ($time_period) {
        case '1':
          //last week
          $from_date = mktime(0, 0, 0, date('m', time()), date('d', time()) - 7, date('Y'));
          break;
        case '2':
          //last month
          $from_date = mktime(0, 0, 0, date('m', time()), date('d', time()) - 30, date('Y'));
          break;
        case '3':
          //last 3 months
          $from_date = mktime(0, 0, 0, date('m', time()), date('d', time()) - 90, date('Y'));
          break;
        case '4':
          //last 6 months
          $last_day = \Drupal\downtimes\Form\DowntimesFilter::lastDayOfMonth(date('m', strtotime('last month')), date('y', strtotime('last month')));
          $from_date = mktime(0, 0, 0, date('m', $last_day) - 5, 01, date('y', $last_day));
          //$to_date = $last_day;
          break;
        case '5':
          //last 12 months
          $last_day = \Drupal\downtimes\Form\DowntimesFilter::lastDayOfMonth(date('m', strtotime('last month')), date('y', strtotime('last month')));
          $from_date = mktime(0, 0, 0, date('m', $last_day) - 11, 01, date('y', $last_day));
          //$to_date = $last_day;
          break;
      }
      if ($from_date > $startDate) {
        $startDate = $from_date;
      }
//            $fromDate = DateTimePlus::createFromTimestamp($from_date)->getTimestamp();
//            $filterData->set('filter_startdate', $startDate->format('d.m.Y'));
//            $downtimesQuery = $downtimesQuery->condition('d.scheduled_p',$filterData->get('type'));
    }
    if ($type == 'archived') {
      $archiveCheckGroup = $downtimesQuery->orConditionGroup()
        ->condition('d.resolved', 1)
        ->condition('d.cancelled', 1);
      $downtimesQuery = $downtimesQuery->condition($archiveCheckGroup);
    }
    else {
      $archiveCheckGroup = $downtimesQuery->andConditionGroup()
        ->condition('d.resolved', 0)
        ->condition('d.cancelled', 0);
      $downtimesQuery = $downtimesQuery->condition($archiveCheckGroup);
    }
    
    if ($startDate && $endDate) {
      if ($startDate > $endDate) {
        $andDateGrp = $downtimesQuery->andConditionGroup()
          ->condition('d.startdate_planned', $startDate, '>')
          ->condition('d.enddate_planned', $endDate, '<');
        $downtimesQuery = $downtimesQuery->condition($andDateGrp);
      }
      if ($type == 'archived') {
        $andDateGrp = $downtimesQuery->andConditionGroup()
          ->condition('d.startdate_planned', $startDate, '<')
          ->condition('rci.end_date', $endDate, '>');
        $orDateGroup = $downtimesQuery->orConditionGroup()
          ->condition('d.startdate_planned', [$startDate, $endDate], 'BETWEEN')
          ->condition('rci.end_date', [$startDate, $endDate], 'BETWEEN')
          ->condition($andDateGrp);
        $downtimesQuery = $downtimesQuery->condition($orDateGroup);
      }
      else {
        $andDateGrp = $downtimesQuery->andConditionGroup()
          ->condition('d.startdate_planned', $startDate, '<')
          ->condition('d.enddate_planned', $endDate, '>');
        $orDateGroup = $downtimesQuery->orConditionGroup()
          ->condition('d.startdate_planned', [$startDate, $endDate], 'BETWEEN')
          ->condition('d.enddate_planned', [$startDate, $endDate], 'BETWEEN')
          ->condition($andDateGrp);
        $downtimesQuery = $downtimesQuery->condition($orDateGroup);
      }
    }
    else {
      if ($startDate) {
//                $startDate = DateTimePlus::createFromFormat('d.m.Y', $filterData->get('filter_startdate'))->getTimestamp();
        $downtimesQuery = $downtimesQuery->condition('d.startdate_planned', $startDate, '>');
      }
      elseif ($endDate) {
//                $endDate = DateTimePlus::createFromFormat('d.m.Y', $filterData->get('filter_enddate'))->getTimestamp();
//                $endDate += 86399;
        if ($type == 'archived') {
          $downtimesQuery = $downtimesQuery->condition('rci.end_date', $endDate, '<');
        }
        else {
          $downtimesQuery = $downtimesQuery->condition('d.enddate_planned', $endDate, '<');
          $downtimesQuery = $downtimesQuery->condition('d.enddate_planned', '', '<>');
        }
      }
    }
//        pr($downtimesQuery->__toString());exit;
    if ($filterData->has('states') && $filterData->get('states') != 1) {
      $orStateGroup = $downtimesQuery->orConditionGroup()
        ->condition('d.state_id', "{$filterData->get('states')},%", 'LIKE')
        ->condition('d.state_id', "%,{$filterData->get('states')},%", 'LIKE')
        ->condition('d.state_id', "%,{$filterData->get('states')}", 'LIKE')
        ->condition('d.state_id', "{$filterData->get('states')}");
      $downtimesQuery = $downtimesQuery->condition($orStateGroup);
    }
    if ($filterData->has('services_effected') && $filterData->get('services_effected') != 0) {
      $orServiceGroup = $downtimesQuery->orConditionGroup()
        ->condition('d.service_id', "{$filterData->get('services_effected')},%", 'LIKE')
        ->condition('d.service_id', "%,{$filterData->get('services_effected')},%", 'LIKE')
        ->condition('d.service_id', "%,{$filterData->get('services_effected')}", 'LIKE')
        ->condition('d.service_id', "{$filterData->get('services_effected')}");
      $downtimesQuery = $downtimesQuery->condition($orServiceGroup);
//            $state = " ( ds.state_id LIKE '" . $state_id . ",%' or ds.state_id LIKE '%," . $state_id . ",%' or  ds.state_id LIKE '%," . $state_id . "' ) ";
    }
    else {
      $defaultServicesList = [];
      $group_downtimes_view_service_query = \Drupal::database()
        ->select('group_downtimes_view', 'gdv');
      $group_downtimes_view_service_query->Fields('gdv', array('service_id'));
      $group_downtimes_view_service_query->condition('group_id', $current_group_id, '=');
      $group_downtimes_view_service = $group_downtimes_view_service_query->execute()
        ->fetchAll();
      if (empty($group_downtimes_view_service)) {
        $downtimesQuery->condition('d.service_id', [-1], 'IN');
      }
      else {
        foreach ($group_downtimes_view_service as $service) {
          $defaultServicesList[$service->service_id] = $service->service_id;
        }
        $orAllServiceGroup = $downtimesQuery->orConditionGroup();
        foreach ($defaultServicesList as $item) {
          $orServiceGroup = $downtimesQuery->orConditionGroup()
            ->condition('d.service_id', "{$item},%", 'LIKE')
            ->condition('d.service_id', "%,{$item},%", 'LIKE')
            ->condition('d.service_id', "%,{$item}", 'LIKE')
            ->condition('d.service_id', "{$item}");
          $orAllServiceGroup->condition($orServiceGroup);
        }
        $downtimesQuery->condition($orAllServiceGroup);
      }
    }
//        kint($downtimesQuery->__toString());
    if ($type == 'archived') {
    $count_query = clone $downtimesQuery;
    $count_query->addExpression('Count(d.id)');
    $pager = $downtimesQuery->extend('Drupal\Core\Database\Query\PagerSelectExtender');
    $pager->setCountQuery($count_query);
    $pager->limit(PAGE_LIMIT);
    $pager->fields('d');
    $pager->distinct();
    }
    if ($type == 'archived') {
      $pager->addExpression('case when (rci.end_date = 0 OR rci.end_date is null) then d.enddate_planned else rci.end_date end','sorted_end');
      $pager->orderby('sorted_end', 'desc');
    } elseif ($type == 'incident') {
        $downtimesQuery->fields('d');
        $downtimesQuery->orderby('d.startdate_planned', 'desc');
    } elseif ($type == 'maintenance') {
        $downtimesQuery->fields('d');
        $downtimesQuery->orderby('d.startdate_planned', 'asc');
    } 


//        kint($pager->__toString());
    if ($type == 'archived') {
        $result = $pager->execute()->fetchAll();
    } else {
        $result = $downtimesQuery->execute()->fetchAll();
    }
    $renderer = \Drupal::service('renderer');
    if ($type == 'archived') {
      $enddate_label = t('Actual End Date');
    }
    else {
      $enddate_label = t('Expected End Date');
    }
    $headersNew = $rows = [];
    if ($type == 'archived') {
      $headersNew = array_merge($headersNew, ['type' => t('Type')]);
    }
    $headersNew = array_merge($headersNew, [
      'description' => t('Beschreibung'),
      'service' => t('Verfahren'),
      'state' => t('Land')
    ]);
    $headersNew = array_merge($headersNew, [
      'start_date' => t('Beginn'),
      'end_date' => $enddate_label
    ]);
    if ($type == 'archived') {
      $headersNew = array_merge($headersNew, ['status' => t('Status')]);
    }
    foreach ($result as $client) {
//            kint($client);
      $services = self::downtime_services_names($client->service_id);
      // $user_state = display_update($states[$client->state_id]);.
      $user_state_list = \Drupal::database()->select("states", 's')
        ->fields('s', ['abbr'])
        ->distinct()
        ->condition('id', explode(',', $client->state_id), 'IN')
        ->execute()
        ->fetchCol();
//            pr($user_state_list);exit;
//            $user_state = null;
      $user_states = [0 => NULL];
      $i = 1;
      $j = 0;
      //// preparing an array with 3 states on each row for a clean display purpose.
      foreach ($user_state_list as $stateAbbr) {
        if (!isset($user_states[$j])) {
          $user_states[$j] = NULL;
        }
        $user_states[$j] .= ' ' . $stateAbbr . ',';
        if ($i % 3 == 0) {
          $j++;
        }
        $i += 1;
      }
      $stateCount = count($user_states);
      if ($stateCount != 0) {
        $user_states[$stateCount - 1] = trim($user_states[$stateCount - 1], ',');
      }
//            $user_state = trim($user_state, ',');
      //$user_state = \Drupal::database()->select("SELECT Group_concat(DISTINCT abbr SEPARATOR', ') FROM {states} WHERE id IN (" . $client->state_id . ")")->fetchField();
      $startdate = ($client->startdate_planned ? date('d.m.Y H:i', $client->startdate_planned) . ' Uhr' : "unbekannt");
      if ($type == 'archived') {
        if (isset($client->cancelled) && $client->cancelled == 1) {
                    $enddate_cancelled = db_query("select end_date,date_reported from {resolve_cancel_incident} where downtime_id = ?", array($client->downtime_id))->fetchObject();
                    if (!empty($enddate_cancelled)) {
                        if (!empty($enddate_cancelled->end_date)) {
                            $enddate = date("d.m.Y H:i", $enddate_cancelled->end_date) . ' Uhr';
                        } else {
                            $enddate = date("d.m.Y H:i", $enddate_cancelled->date_reported) . ' Uhr';
                        }
                    } else {
                        $enddate = ($client->enddate_planned ? date("d.m.Y H:i", $client->enddate_planned) . ' Uhr' : "");
                    }
        }
        else {
          $enddate_resolved = db_query("select end_date,date_reported from {resolve_cancel_incident} where downtime_id = ?", array($client->downtime_id))->fetchObject();
          if (!empty($enddate_resolved)) {
            if (!empty($enddate_resolved->end_date)) {
              $enddate = date("d.m.Y H:i", $enddate_resolved->end_date) . ' Uhr';
            }
            else {
              $enddate = date("d.m.Y H:i", $enddate_resolved->date_reported) . ' Uhr';
            }
          }
          else {
            $enddate = ($client->enddate_planned ? date("d.m.Y H:i", $client->enddate_planned) . ' Uhr' : "");
          }
        }
      }
      else {
        $enddate = ($client->enddate_planned ? date("d.m.Y H:i", $client->enddate_planned) . ' Uhr' : "");
      }
      $reporter_uid = db_query("SELECT uid FROM {node_field_data} WHERE nid = $client->downtime_id")->fetchField();
//      $name = db_query("select concat(firstname,' ',lastname) as name from {cust_profile} where uid = $reporter_uid")->fetchField();
//      $user_url = Url::fromUserInput('/user/' . $reporter_uid);
//      $user_name = ($user->id() ? \Drupal::l($name, $user_url) : $name);
      
      $downtime_state_ids = array();
      $downtime_state_ids = explode(',', $client->state_id);
      $show_resolve = self::resolve_link_display($downtime_state_ids, $reporter_uid);
      $maintenance_group = \Drupal\group\Entity\Group::load(GEPLANTE_BLOCKZEITEN);
      // $maintenance_edit = saved_quickinfo_og_is_member(MAINTENANCE_GROUP_ID);
      $currentUser = \Drupal::currentUser();
      $groupMember = $maintenance_group->getMember($currentUser);
      $incidentManagement = \Drupal\group\Entity\Group::load(INCIDENT_MANAGEMENT);
      $incidentManagementGroupMember = $incidentManagement->getMember($currentUser);
      if (($groupMember && $groupMember->getGroupContent()
            ->get('request_status')->value == 1 && $incidentManagementGroupMember) || array_intersect($currentUser->getRoles(), [
          'site_administrator',
          'administrator'
        ])
      ) {
        $maintenance_edit = TRUE;
      }
      else {
        $maintenance_edit = FALSE;
      }
//            if ($string == 'archived' && isset($client->cancelled) && $client->cancelled == 1) {
//                $flag = "<span class='cancelled-downtime'>" . t('Cancelled') . "</span>";
//            } else {
//                $flag = "";
//            }
      $elements = [];
      $downtimeTypes = [0 => t('Störung'), 1 => t('Blockzeit')];
      if ($type == 'archived') {
        $elements = array_merge($elements, ['type' => $downtimeTypes[$client->scheduled_p]]);
      }
      $user_states = [
        '#items' => $user_states,
        '#theme' => 'item_list',
        '#type' => 'ul'
      ];
      $serviceList = [
        '#items' => $services,
        '#theme' => 'item_list',
        '#type' => 'ul'
      ];
      $elements = array_merge($elements, array(
        //// truncating description accordin to the display of services i.e. 60 char for 1 service and 120 char for 2 services
        'description' => Markup::create(Unicode::truncate(strip_tags($client->description), count($services) * 60, TRUE, TRUE, 1)),
        'service' => $renderer->render($serviceList),
        'state' => $renderer->render($user_states)
      ));
//                'state' => $user_state));
      $elements = array_merge($elements, array(
        'start_date' => $startdate,
        'end_date' => $enddate,
//        'name' => $user_name,
      ));
      if ($type == 'archived') {
        
        $status = NULL;
        if ($client->cancelled) {
          $status = t('Storniert');
        }
        if ($client->resolved) {
          $status = t('Behoben');
        }
        $elements = array_merge($elements, ['status' => $status]);
      }
      
      
      $groupContent = \Drupal\cust_group\CustGroupHelper::getGroupNodeFromNodeId($client->downtime_id);
      
      $links = [];
      $query = \Drupal::request()->query;
      if ($groupContent && !$query->has('print')) {
        $links['action']['popup'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['popup-wrapper']]
        ];
        $links['action']['popup']['view'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['details-wrapper']]
        ];
        $links['action']['popup']['view']['details'] = [
          '#title' => t('Details'),
          '#type' => 'link',
          '#url' => Url::fromRoute('entity.node.canonical', ['node' => $client->downtime_id], [
            'attributes' => ['class' => ['downtimes_details_link']],
            'query' => $exposedFilterData
          ])
        ];
      }

//            $query_seralized = serialize($query_params)
      $downtime_type = db_query("SELECT scheduled_p FROM {downtimes} WHERE downtime_id = $client->downtime_id")->fetchField();
      if ($downtime_type == 1) {
        if ($maintenance_edit && (INCIDENT_MANAGEMENT == $group_id) && $type != 'archived') {
          $links['action']['edit'] = [
            '#title' => t('Update'),
            '#type' => 'link',
            '#url' => Url::fromRoute('entity.node.edit_form', ['node' => $client->downtime_id], ['attributes' => ['class' => ['downtimes_update_link']]])
          ];
          if ($client->startdate_planned > REQUEST_TIME) {
            $links['action']['cancel'] = [
              '#title' => t('Cancel Maintenance'),
              '#type' => 'link',
              '#url' => Url::fromRoute('downtimes.cancel', [
                'group' => $group_id,
                'node' => $client->downtime_id
              ], ['attributes' => ['class' => ['downtimes_cancel_link']]])
            ];
          }
          else {
            $links['action']['resolve'] = [
              '#title' => t('Resolve'),
              '#type' => 'link',
              '#url' => Url::fromRoute('downtimes.resolve', ['node' => $client->downtime_id], ['attributes' => ['class' => ['downtimes_resolve_link']]])
            ];
          }
        }
      }
      else {
        if ($show_resolve && (INCIDENT_MANAGEMENT == $group_id)) {
          if ($type != 'archived') {
            $links['action']['edit'] = [
              '#title' => t('Update'),
              '#type' => 'link',
              '#url' => Url::fromRoute('entity.node.edit_form', ['node' => $client->downtime_id], ['attributes' => ['class' => ['downtimes_update_link']]])
            ];
            $links['action']['resolve'] = [
              '#title' => t('Resolve'),
              '#type' => 'link',
              '#url' => Url::fromRoute('downtimes.resolve', ['node' => $client->downtime_id], ['attributes' => ['class' => ['downtimes_resolve_link']]])
            ];
          }
        }
      }
      if (!$isPrintFormat) {
        $headersNew = array_merge($headersNew, ['action' => t('Action')]);
        $entity = Node::load($client->downtime_id);
        $view_builder = \Drupal::entityManager()->getViewBuilder('node');
        $links['action']['popup']['node'] = ['#type'=>'container','#attributes'=>['class'=>['downtime-popover-wrapper']]];
        $links['action']['popup']['node'][] = $view_builder->view($entity, 'popup', 'de');
        $elements['action'] = $renderer->render($links);
      }
//            pr(count($links));
//      $elements['table_type'] = $string;
      $rowClass = '';
      if ($type != 'archived' && ($client->startdate_planned < REQUEST_TIME || $client->scheduled_p == 0)) {
        $rowClass = 'text-danger';
      }
      $rows[] = ['data' => $elements, 'class' => $rowClass];
    }
    $title = [
      'incident' => Markup::create('<h2 class="text-danger">Aktuelle Störungen</h2>'),
      'maintenance' => Markup::create('<h2>Blockzeiten</h2>'),
      'archived' => Markup::create('<h2>Störungen und Blockzeiten</h2>')
    ];
    $noDataText = [
      'incident' => t('No incidents available.'),
      'maintenance' => t('No maintenances available.'),
      'archived' => t('No downtimes available.')
    ];
    $variables = array(
      'header' => $headersNew,
      'rows' => $rows,
      'footer' => NULL,
      'attributes' => array('class' => [$type]),
      'caption' => NULL,
      'colgroups' => array(),
      'sticky' => TRUE,
      'responsive' => TRUE,
      'empty' => $noDataText[$type]
    );
//    self::downtimes_display_table($variables);
    $build = [];
    $build['downtime_data'] = array(
      '#header' => $variables['header'],
      '#rows' => $variables['rows'],
      '#attributes' => $variables['attributes'],
      '#empty' => $variables['empty'],
//      '#header_columns' => $variables['header_columns'],
      '#type' => 'table',
      '#caption' => $title[$type],
      '#prefix' => Markup::create('<div style="clear:both"></div>'),
      // adding max-age 0 because caching downtimes depends on various factors
      // @todo to be changed to appropiate context and tags
      '#cache' => ['max-age' => 0],
    );
    if ($type == 'archived') {
        $build['pager'] = array(
          '#type' => 'pager',
          '#prefix' => '<div id="pagination">',
          '#suffix' => '</div>',
          '#exclude_from_print' => 1,
        );
    }
    
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
  
  static public function get_downtimes_filters() {
    $parameters = array();
    $request = \Drupal::request()->query;
    $parameters['downtime_type'] = $request->get('downtime_type');
    $parameters['time_period'] = $request->get('time_period');
    $parameters['states'] = $request->get('states');
    $parameters['services_effected'] = $request->get('services_effected');
    $parameters['filter_startdate'] = $request->get('filter_startdate');
    $parameters['search_string'] = $request->get('search_string');
    $parameters['filter_enddate'] = $request->get('filter_enddate');
    $parameters['string'] = $request->get('string');
    $parameters['type'] = $request->get('type');
    $parameters['limit'] = $request->get('limit');
    return $parameters;
  }
  
}
