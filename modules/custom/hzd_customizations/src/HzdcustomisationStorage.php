<?php

namespace Drupal\hzd_customizations;

// Use Drupal\node\Entity\Node;
// use Drupal\user\PrivateTempStoreFactory;.
use Drupal\Core\Path\Path;
use Drupal\Core\Url;

define('MAINTENANCE_GROUP_ID', \Drupal::config('downtimes.settings')->get('maintenance_group_id'));
/**
 *
 */
class HzdcustomisationStorage {

  /**
   * Change url alias when the path to problems or releases is changed.
   */
  static function change_url_alias($dst_path = NULL, $src_path = NULL) {
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
  function documentation_link_download($params, $values) {

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
      if ((!$title)
      || ($field_release_value == 2 && $field_release_type_value == 1)
      || (($count_nid < 3) && ($count_nid > 0))
      || (($field_date_value != $values_date) && ($field_release_type_value == 1))) {

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
          if (($values_date != $field_date_value)
          || ($field_release_value == 2 && $field_release_type_value == 1)) {

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
  static function reset_menu_link($counter = 0, $link_title = NULL, $link_path = NULL, $menu_name = NULL, $gid = NULL) {
    // $group_link = db_result(db_query("SELECT mlid from {menu_links} ml where ml.link_title = '%s' and ml.menu_name = '%s'", $link_title, $menu_name));
    // droy: Replaced unique identifier link_title by link_path because of issues with German special characters in link_title which result in no sql query results found.
    $group_link = \Drupal::database()->select('menu_link_content_data', 'mlcd')
                ->Fields('mlcd', array('id'))
                ->condition('link__uri', '%' . $link_path, 'LIKE')
                ->condition('menu_name', $menu_name, 'LIKE')
                ->execute()->fetchAssoc();

    if ($counter > 0) {
      if (empty($group_link)) {
        $flink['link_title'] = t($link_title);
        $flink['link_path'] = 'node/' . $gid . '/' . $link_path;
        $flink['router_path'] = "node/%" . '/' . "$link_path";
        $flink['plid'] = 0;
        $flink['menu_name'] = $menu_name;
        menu_link_save($flink);
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
        $query->Fields('mlcd', array('enabled'));
        $query->condition('id', $group_link, '=');
        $hidden_value = $query->execute()->fetchAssoc();

        if ($hidden_value['enabled'] == 1) {
          // droy: When hiding a menu entry, we only set hidden = 1. Why the need to update many more fields when unhiding?
          // db_query("UPDATE {menu_links} set hidden = %d, link_path= '%s', router_path = '%s' WHERE mlid = %d", 0, "node/$gid/$link_path", "node/%/$link_path", $group_link);
          // db_query("UPDATE {menu_links} set hidden = %d WHERE mlid = %d", 0, $group_link);.
          db_update('menu_link_content_data')->fields(array('enabled' => 0))->condition('id', $group_link, '=')->execute();

          // Need to clear the menu cache to get the new menu item affected.
          menu_cache_clear_all();
        }
      }
    }
    else {
      // All links were unset. So we need to make the menu item hidden.
      if ($group_link) {
        // db_query("UPDATE {menu_links} set hidden = %d WHERE mlid = %d", 1, $group_link);.
        db_update('menu_link_content_data')->fields(array('enabled' => 1))->condition('id', $group_link, '=')->execute();
        // Need to clear the menu cache to get the new menu item affected.
        menu_cache_clear_all();
      }
    }
  }

  /**
   * Display published services.
   */
  function service_profiles() {
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
        $data[]   = \Drupal::l($text, $url);
      }
      else {
        $text = $service->service;
        $url = Url::fromUserInput('/node/' . MAINTENANCE_GROUP_ID . '/add/service_profile/', array('service' => $service->nid));
        $data[] = \Drupal::l($text, $url);
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

}
