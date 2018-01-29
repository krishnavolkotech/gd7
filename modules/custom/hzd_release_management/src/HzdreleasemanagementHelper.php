<?php

namespace Drupal\hzd_release_management;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\group\Entity\Group;
use Drupal\node\Entity\Node;
use Drupal\cust_group\Controller\CustNodeController;
use Drupal\hzd_services\HzdservicesStorage;
use Drupal\hzd_services\HzdservicesHelper;
use Drupal\Core\Url;

// Use Drupal\Core\Render;.

/**
 *
 */
class HzdreleasemanagementHelper {
  /*
   * Returns TRUE if the service exists
   */
  /**
   * Function service_exist($service, $type) {
   * $services = HzdservicesStorage::get_related_services($type);
   * # return in_array(trim($service), $services);
   * return in_array(strtoupper(trim($service)), array_map('strtoupper',$services));
   * }.
   */

  /**
   * Title in header array  = release in the csv file.
   */
  static public function _csv_headers() {
    $released_path = \Drupal::config('hzd_release_management.settings')->get('import_path_csv_released');
    $rejected_path = \Drupal::config('hzd_release_management.settings')->get('import_path_csv_rejected');
    $locked_path = \Drupal::config('hzd_release_management.settings')->get('import_path_csv_locked');
    $progress_path = \Drupal::config('hzd_release_management.settings')->get('import_path_csv_progress');
    $ex_eoss_path = \Drupal::config('hzd_release_management.settings')->get('import_path_csv_ex_eoss');

    if ($released_path) {
      $path['released'] = DRUPAL_ROOT . '/' . $released_path;
      $header_values['released'] = array('title', 'status', 'service', 'datum', 'link', 'documentation_link');
    }
    if ($progress_path) {
      $path['progress'] = DRUPAL_ROOT . '/' . $progress_path;
      $header_values['progress'] = array('title', 'status', 'service', 'datum', 'link', 'documentation_link');
    }
    if ($ex_eoss_path) {
      $path['ex_eoss'] = DRUPAL_ROOT . '/' . $ex_eoss_path;
      $header_values['ex_eoss'] = array('title', 'status', 'service', 'datum', 'link', 'documentation_link');
    }

    /*  if ($rejected_path) {
      $path['rejected'] = DRUPAL_ROOT . '/' . $rejected_path;
      $header_values['rejected'] = array('title', 'status', 'service', 'date', 'comment');
      }
     */
    if ($locked_path) {
      $path['locked'] = DRUPAL_ROOT . '/' . $locked_path;
      $header_values['locked'] = array('title', 'status', 'service', 'datum', 'link', 'comment');
    }
    $path_header = array('path' => $path, 'headers' => $header_values);
    return $path_header;
  }

  /**
   * Validation for the release csv import.
   */
  static public function validate_releases_csv(&$values) {

    if (isset($values['datum']) && $values['datum']) {
      $replace = array('/' => '.', '-' => '.');
      $formatted_date = strtr($values['datum'], $replace);
      if ($formatted_date) {
        $date_time = explode(" ", $formatted_date);
        $date_format = explode(".", $date_time[0]);
        $time_format = explode(":", $date_time[1]);
        if ($time_format[0] && $time_format[1] && $date_format[1] && $date_format[0] && $date_format[2]) {
          $date = mktime((int) $time_format[0], (int) $time_format[1], (int) $time_format[2], (int) $date_format[1], (int) $date_format[0], (int) $date_format[2]);
        }
        $values['datum'] = $date;
      }
    }
    $type = 'releases';
    $service = $values['service'];
    $service = trim($service);
    if (HzdservicesHelper::service_exist($service, $type)) {
      $services = HzdservicesStorage::get_related_services($type);
      $service_id = array_keys($services, $service);
      $values['service'] = $service_id[0];
      return TRUE;
    } else {
      // Echo $service; echo $type; exit;.
      $mail = \Drupal::config('hzd_release_management.settings')->get('import_mail_releases');
      $subject = 'New service found while importing Releases';
      $body = t("New service found in import file: ") . $service . ' ' . t("Please add this service to the release database.");
      HzdservicesHelper::send_problems_notification('release_read_csv', $mail, $subject, $body);
      return FALSE;
    }
    return FALSE;
  }

  /**
   * Get list of services based on the release type.
   */
  public function get_release_type_services($string = NULL, $release_type = NULL) {
    $group = \Drupal::routeMatch()->getParameter('group');
    if (is_object($group)) {
      $group_id = $group->id();
    } else {
      $group_id = $group;
    }
    $group_id = (isset($group_id) ? $group_id : RELEASE_MANAGEMENT);
    $query = db_select('node_field_data', 'n');
    $query->join('group_releases_view', 'grv', 'n.nid = grv.service_id');
    $query->join('node__release_type', 'nrt', 'n.nid = nrt.entity_id');
    $query->fields('n', array('nid', 'title'))
            ->condition('n.type', 'services', '=')
            ->condition('grv.group_id', $group_id, '=')
            ->condition('nrt.release_type_target_id', $release_type, '=')
            ->orderBy('n.title', 'ASC');
    $result = $query->execute()->fetchAll();

    $default_services[] = '<' . t('Select service') . '>';
    foreach ($result as $vals) {
      $default_services[$vals->nid] = $vals->title;
    }
    return array('services' => $default_services);
  }

  /**
   * Returns the releases associated with the provided sevices.
   *
   * @service is service nide id for which releses has to be returned
   */
  static public function get_dependent_release($service = NULL) {
    // $tempstore = \Drupal::service('user.private_tempstore')->get('hzd_release_management');
    // $id = $tempstore->get('Group_id');
    $group = \Drupal::routeMatch()->getParameter('group');
    if (is_object($group)) {
      $group_id = $group->id();
    } else {
      $group_id = $group;
    }

    $group_id = (isset($group_id) ? $group_id : RELEASE_MANAGEMENT);
    $query = db_select('node_field_data', 'n');
    $query->join('node__field_relese_services', 'nfrs', 'n.nid = nfrs.entity_id');
    $query->join('group_releases_view', 'grv', 'nfrs.field_relese_services_target_id = grv.service_id');
    $query->fields('n', array('nid', 'title'))
            ->condition('grv.group_id', $group_id, '=')
            ->condition('n.type', 'release', '=')
            ->orderBy('n.title', 'ASC');
    if ($service) {
      $query->condition('nfrs.field_relese_services_target_id', $service);
    }
    $result = $query->execute()->fetchAll();
    $default_release[] = t("@release", ['@release' => '<Release>']);
    foreach ($result as $vals) {
      $default_release[$vals->nid] = $vals->title;
    }
    natcasesort($default_release);
    return array('releases' => $default_release);
  }

  /**
   *
   */
  public function get_release($string = NULL, $service = NULL) {
    $group = \Drupal::routeMatch()->getParameter('group');
    if (is_object($group)) {
      $group_id = $group->id();
    } else {
      $group_id = $group;
    }

    $group_id = (isset($group_id) ? $group_id : RELEASE_MANAGEMENT);
    $release_type = get_release_type($string);
    $query = db_select('node_field_data', 'n');
    $query->join('node__field_relese_services', 'nfrs', 'n.nid = nfrs.entity_id');
    $query->join('group_releases_view', 'grv', 'nfrs.field_relese_services_target_id = grv.service_id');
    $query->join('node__field_release_type', 'nfrt', 'n.nid = nfrt.entity_id');
    $query->fields('n', array('nid', 'title'))
            ->condition('grv.group_id', $group_id, '=')
            ->condition('nfrt.field_release_type_value', $release_type, '=')
            ->condition('n.type', 'release', '=')
            ->condition('nfrs.field_relese_services_target_id', $service)
            ->orderBy('n.title', 'ASC');

    $result = $query->execute()->fetchAll();
    $default_release[] = '<' . t("Release") . '>';
    foreach ($result as $vals) {
      $default_release[$vals->nid] = $vals->title;
    }
    return array('releases' => $default_release);
  }

  /**
   * Using service id and release id get release name, product name and service name.
   */
  static public function get_document_args($service_id, $release_id) {

    // Get service name and release name.
    $service_name = strtolower(db_query("SELECT title FROM {node_field_data} where nid = :nid", array(":nid" => $service_id))->fetchField());
    $release_name = db_query("SELECT title FROM {node_field_data} where nid = :nid", array(":nid" => $release_id))->fetchField();
    $release_product = explode("_", $release_name);
    $release_versions = explode("-", $release_product[1]);
    $releases_title = $release_product[0] . "_" . $release_versions[0];

    // Get the documentation folder path.
    $file_path = \Drupal::service('file_system')->realpath("private://");

    $get_product = $file_path . "/releases/" . strtolower($service_name) . "/" . strtolower($release_product[0]);
    $product = strtolower($release_product[0]);
    $upper_product = $release_product[0];
    $new_release = $get_product . "/" . strtolower($release_name);
    $dir = $new_release . "/dokumentation";
    $files = null;
    // Get the release versions.
    if (is_dir($dir)) {
      $files = scandir($dir);
    }

    $get_product_scan = scandir($get_product);
    $count = count($get_product_scan);
    if ($count >= 2) {
      foreach ($get_product_scan as $key => $values) {
        $get_release_values = explode("_", $values);
        $get_after_release_value = array_shift($get_release_values);
        $get_versions_value = join("_", $get_release_values);
        $arr[] = $get_versions_value;
      }

      // Get the documentation link zip file.
      $field_link_value = db_query("SELECT field_documentation_link_value FROM {node__field_documentation_link} WHERE entity_id= :eid", array(":eid" => $release_id))->fetchField();
      $field_link_value_split = explode("/", $field_link_value);
      $get_link = array_pop($field_link_value_split);
      $remove_link_zip = explode(".zi", $get_link);
      $get_releasess_version = array_shift($release_product);
      $releasess = strtolower(join("_", $release_product));
      $zip_link = strtolower($remove_link_zip[0]);

      $doc_values = array("get_product" => $get_product, "arr" => $arr, "dir" => $dir, "product" => $product, "service_name" => $service_name, "upper_product" => $upper_product, "files" => $files, "releasess" => $releasess, "zip_link" => $zip_link, "release_name" => $release_name);
      return $doc_values;
    } else {
      return FALSE;
    }
  }

  /**
   * Function to create zip file path.
   *
   * @param $args array of arr, dir, host_path, product, servie_name, product, zip_link.
   *
   * @return $zip string zip file path which created by compressing document folder.
   */
  public static function zip_file_path($doc_values) {
    $arrs = $doc_values['arr'];
    unset($arrs[0]);
    unset($arrs[1]);
    $version_count = self::get_release_version_count($doc_values['releasess'], $arrs);
    $count = $version_count['count'];
    $arr = $version_count['arr'];

    $dir = $doc_values['dir'];
    $product = $doc_values['product'];
    $service_name = $doc_values['service_name'];

    $dir = $doc_values['dir'];
    $product = $doc_values['product'];
    $service_name = $doc_values['service_name'];
    $host = \Drupal::request()->getHost();
    $host_path = "http://" . $host . "/files/releases/" . strtolower($service_name) . "/" . $product;
    $upper_product = $doc_values['upper_product'];
    $zip_link = $doc_values['zip_link'];
    $file_path = \Drupal::service('file_system')->realpath("private://");
    $release_dir = $file_path . "/releases/'" . $service_name . "'";
    $new_directory = $release_dir . "/" . $product . "/" . $product . "_" . max($arr) . "/dokumentation";
    $root_path = $file_path . "/releases/downloads";
    $release_name = $doc_values['release_name'];
    $latest_zip_version = $root_path . "/" . $release_name . "_doku.zip";

    // Check zip file exist or not.
    if (!file_exists($latest_zip_version)) {
      shell_exec("mkdir -p " . $root_path . "/" . $release_name . "_doku");
      $doc_files = scandir(str_replace("'", "", $new_directory));
      unset($doc_files[0]);
      unset($doc_files[1]);
      foreach ($doc_files as $values) {
        shell_exec("cp -r " . $new_directory . "/" . $values . " " . $root_path . "/" . $release_name . "_doku");
      }
      $array = array("betriebshandbuch", "afb", "releasenotes", "sonstige", "benutzerhandbuch", "zertifikat");
      $copy_doc_sub_folders = self::copy_doc_sub_folders($doc_values, $array, $root_path);
      // Creating a zip.
      $release_path = $file_path . "/releases/downloads/" . $release_name . "_doku";
      $files_path = $file_path . "/releases/downloads";
      $release_name_doc = $release_name . "_doku";
//            $sh_zip = "./core/misc/create_zip.sh";
//            shell_exec("sh $sh_zip $files_path $release_name_doc");
      $fileName = $release_name . '_doku.zip';
      shell_exec("cd $files_path && zip -r $fileName $release_name_doc/");
      shell_exec("rm -rf " . $release_path);
    }
    $host_path = "http://" . $host . "/files/releases/downloads";
    $zip = $release_name . "_doku.zip";
    return $zip;
  }

  /**
   *
   */
  public static function copy_doc_sub_folders($doc_values, $array, $root_path) {
    $arrs = $doc_values['arr'];
    unset($arrs[0]);
    unset($arrs[1]);
    $version_count = self::get_release_version_count($doc_values['releasess'], $arrs);
    $count = $version_count['count'];
    $arr = $version_count['arr'];
    $dir = $doc_values['dir'];
    $product = $doc_values['product'];
    $service_name = $doc_values['service_name'];
    $host = \Drupal::request()->getHost();
    $host_path = "http://" . $host . "/files/releases/" . strtolower($service_name) . "/" . $product;
    $upper_product = $doc_values['upper_product'];
    $zip_link = $doc_values['zip_link'];
    $release_name = $doc_values['release_name'];
    foreach ($array as $values) {
      $arr_copy = $arr;
      $root_path_release = $root_path . "/" . $release_name . "_doku";
      $new_dir_path_scandir = scandir($root_path_release);
      $search = array_search($values, $new_dir_path_scandir);
      if ($search) {
        $true = TRUE;
      } else {
        if ($values == 'betriebshandbuch') {
          $i = 1;
          // Copy documentation sub folders to previous version.
          while ($i <= $count) {
            $a = FALSE;
            if (count($arr_copy) > 0) {
              $version = max($arr_copy);
              $search_version = array_search($version, $arr_copy);
              unset($arr_copy[$search_version]);
              $file_path = \Drupal::service('file_system')->realpath("private://");
              $release_dir = $file_path . "/releases/" . $service_name;
              if (count($arr_copy) > 0) {
                $title = $upper_product . "_" . max($arr_copy);
                $new_directory = $release_dir . "/" . $product . "/" . $product . "_" . max($arr_copy) . "/dokumentation";
                if (is_dir($new_directory)) {
                  $zip_file_path_scandir = scandir($new_directory);
                  $search = array_search($values, $zip_file_path_scandir);
                  if ($search) {
                    $release_dir = $file_path . "/releases/'" . $service_name . "'";
                    $new_directory = $release_dir . "/" . $product . "/" . $product . "_" . max($arr_copy) . "/dokumentation";
                    shell_exec("cp -r " . $new_directory . "/" . $values . " " . $root_path_release);
                    $a = TRUE;
                  }
                }
              }
            } else {
              $i = $i + 1;
            }

            if ($a) {
              break;
            }
          }
        }
      }
    }
  }

  /**
   *
   * @return count and release versions
   */
  public static function get_release_version_count($releasess, $arr) {

    // Get the count and upto present release versions.
    $count_search = array_search($releasess, $arr);
    $key_arrays = array_keys($arr);
    $max_key_arrays = max($key_arrays);
    $count_search = $count_search + 1;
    for ($count_search; $count_search <= $max_key_arrays; $count_search++) {
      unset($arr[$count_search]);
    }
    // $count = count($arr);
    $get_major_release = explode(".", $releasess);
    foreach ($arr as $key => $valu) {
      $arr_versions = explode(".", $valu);
      if ($get_major_release[0] != $arr_versions[0]) {
        unset($arr[$key]);
      }
    }
    $count = count($arr);

    $version_count = array("count" => $count, "arr" => $arr);
    return $version_count;
  }

  /**
   * Display documentation files in release documentation table.
   *
   * @param $args array of arr, dir, host_path, get_product, product, count.
   * @param $values array of document sub folders name
   *
   * @return nothing
   */
  public static function display_doc_folders($args, $values) {
    $get_product = $args['get_product'];
    $count = $args['count'];
    $arr = $args['arr'];
    $dir = $args['dir'];
    $host_path = $args['host_path'];
    $product = $args['product'];
    $major_file = scandir($dir);
    $i = 1;
    $output = null;
    while ($i <= $count) {
      $true = FALSE;
      $search_folder = array_search($values, $major_file);
      if ($search_folder) {
        $folder_name = self::display_folder_name($values);
        $output .= "<tr><td>" . $folder_name . "</td><td>";
        $afb_scan = scandir($dir . "/" . $values);
        unset($afb_scan[0]);
        unset($afb_scan[1]);
        $link_path = $host_path . "/" . $product . "_" . max($arr) . "/dokumentation/" . $values;
        foreach ($afb_scan as $value) {
          $new_path = $link_path . "/" . $value;
          $output .= "<div><a target = '_blank' href = '$new_path'>" . $value . "</a></div>";
        }
        return $output .= "</td></tr>";
        $true = TRUE;
      } else {
        if ($values == 'betriebshandbuch') {
          // Get previous version release documentation.
          $version = max($arr);
          $search_version = array_search($version, $arr);
          unset($arr[$search_version]);
          if (!empty($arr)) {
            $paths = $get_product . "/" . $product . "_" . max($arr) . "/dokumentation";
            if (is_dir($paths)) {
              $major_file = scandir($paths);
              $dir = $paths;
            }
          }
          $i++;
        } else {
          $true = TRUE;
        }
      }
      if ($true) {
        break;
      }
    }
  }

  /**
   * Function to display folder names in release documentation table.
   */
  public static function display_folder_name($values) {
    switch ($values) {
      case "afb":
        return "Abnahme- und Freigabeberichte";

        break;
      case "benutzerhandbuch":
        return "Benutzerhandbuch";

        break;
      case "betriebshandbuch";
        return "Betriebshandbuch";

        break;
      case "releasenotes":
        return "Release Notes";

        break;
      case "sonstige":
        return "Sonstiges";

        break;
      case "zertifikat":
        return "Zertifikat";

        break;
    }
  }

  /**
   *
   */
  static public function releases_reset_element() {
    return $form['reset'] = array(
        '#type' => 'button',
        '#value' => t('Reset'),
        '#attributes' => array('onclick' => "reset_form_elements()"),
        '#prefix' => "<div class = 'reset_form'>",
        '#suffix' => "</div>",
    );
  }

  /**
   * Returns the released releases for the deployment.
   */
  static public function released_deployed_releases($service = NULL) {
    //Retriving the data using entity query // PLease use the below code to optimize performance @sandeep
//        $services = \Drupal::database()->select('group_releases_view','grv')
//            ->fields('grv',['service_id'])
//            ->condition('grv.group_id', RELEASE_MANAGEMENT, '=')
//            ->execute()->fetchCol();
    $services = [-1];
    if ($service) {
      $services = [$service];
    }
    $deployedReleases = \Drupal::entityQuery('node')
            ->condition('field_release_service', $services, 'IN')
            ->condition('type', 'deployed_releases')
            ->execute();
    $deployedReleasesEntities = Node::loadMultiple($deployedReleases);
    $deployed = [-1];
    if ($deployedReleasesEntities) {
      foreach ($deployedReleasesEntities as $node) {
        $deployed[] = $node->get('field_earlywarning_release')->value;
      }
    }
    $data = \Drupal::entityQuery('node')
            ->condition('field_relese_services', $services, 'IN')
            ->condition('field_release_type', [1, 2], 'IN')
            ->condition('nid', $deployed, 'NOT IN')
            ->condition('type', 'release')
            ->execute();
    $nodes = Node::loadMultiple($data);
    $releases = [];
    foreach ($nodes as $node) {
      $releases[$node->id()] = $node->label();
    }

    $deployed_services[] = '<'.t('Service')->render().'>';

    $query = \Drupal::database()->select('node_field_data', 'nfd');
    $query->join('node__field_release_name', 'nfrn', 'nfd.nid = nfrn.entity_id');
    $query->isNotNull('nfrn.field_release_name_value');
    $query->fields('nfd', array('nid', 'title'));
    if ($service) {
      $query->condition('nfd.nid', $services, 'IN');
    }
    $query->condition('type', 'services');
    $query->orderBy('title');
    $services_infos = $query->execute()->fetchAll();

    foreach ($services_infos as $services_info) {
      $serviceEntity = Node::load($services_info->nid);
      $deployed_services[$services_info->nid] = $serviceEntity->get('title')->value;
    }

    return $service_releases = array(
        'services' => $deployed_services,
        'releases' => $releases,
    );
  }

  /**
   *
   * @returns: table display of deployed and archived releases
   * Deployed releases are edited by groupor site admin
   * Onclicking the archive link deployed release status is changed to archived sing ajax
   */
  static public function deployed_releases_table($type = 'current') {
    $page_limit = 100;
    $group = \Drupal::routeMatch()->getParameter('group');
    if (is_object($group)) {
      $group_id = $group->id();
    } else {
      $group_id = $group;
    }

    $user = \Drupal::currentUser();
    $query = \Drupal::database()->select('cust_profile', 'cp');
    $query->addField('cp', 'state_id');
    $query->condition('cp.uid', $user->id(), '=');
    $user_state = $query->execute()->fetchCol();

    $user_role = $user->getRoles(TRUE);
    $output['#attachment']['library'] = array(
        'hzd_release_management/hzd_release_management',
        'hzd_release_management/deployed_releases',
    );
//    $output['current_deploy_table']['#markup'] = '<div class = "currently_deployed_relesaes" ><div class = "deployed_release_title" ><strong>' . t("Currently Deployed Releases:") . '</strong></div>';

    $header = array(t('State'), t('Environment'), t('Service'), t('Release'), t('Date Deployed'), t('Action'));
    /**
     *  TO do check group admin
     */
    $entityQuery = \Drupal::entityQuery('node')->condition('type', 'deployed_releases')->sort('field_date_deployed', 'DESC');
    $request = \Drupal::request();
    if (CustNodeController::isGroupAdmin(zrml) || array_intersect($user_role, array('site_administrator', 'administrator'))) {
      $userState = $request->get('state', 1);
    } else {
      $userState = $user_state;
    }
    if ($userState != 1) {
      $entityQuery->condition('field_user_state', $userState);
    }
    if ($request->get('environment', 0)) {
      $entityQuery->condition('field_environment', $request->get('environment'));
    }
    if ($request->get('service', 0)) {
      $entityQuery->condition('field_release_service', $request->get('service'));
    }
    if ($request->get('release', 0)) {
      $entityQuery->condition('field_earlywarning_release', $request->get('release'));
    }
    if ($request->get('startdate', '') != '' && preg_match ("/^([0-9]{2}).([0-9]{2}).([0-9]{4})$/", $request->get('startdate'))) {
      $startDate = \Drupal\Component\Datetime\DateTimePlus::createFromFormat('d.m.Y|', $request->get('startdate', ''), null, ['validate_format' => FALSE])->format('Y-m-d');
      $entityQuery->condition('field_date_deployed', $startDate, '>=');
    }
    if ($request->get('enddate', '') != '' && preg_match ("/^([0-9]{2}).([0-9]{2}).([0-9]{4})$/", $request->get('enddate'))) {
      $date = $request->get('enddate', '') . ' 23:59:59';
      $enddate = \Drupal\Component\Datetime\DateTimePlus::createFromFormat('d.m.Y H:i:s', $date, null, ['validate_format' => FALSE])->format('Y-m-d H:i:s');
      $entityQuery->condition('field_date_deployed', $enddate, '<=');
    }

    if ($type == 'archived') {
      $entityQuery->condition('field_archived_release', 1);
    } else {
      $entityQuery->condition('field_archived_release', 1, '<>');
    }
    $default_limit = DISPLAY_LIMIT;
    if(!empty($request->get('limit')) && $request->get('limit') != 'all') {
        $limit = $request->get('limit');
        $result = $entityQuery->pager($limit)->execute();
    } elseif($request->get('limit') == 'all') {
        $result = $entityQuery->execute();
    } else {
        $result = $entityQuery->pager($default_limit)->execute();
    }
//      $entityQuery->sort('field_date_deployed','DESC');
//      $entityQuery->addTag('debug');
//    $result = $entityQuery->pager($limit)->execute();
//       pr($result);exit;
    $data = [];
    $states = get_all_user_state();
    foreach ($result as $deployed_release_id) {
      $deployedRelease = Node::load($deployed_release_id);
      $serviceEntity = Node::load($deployedRelease->get('field_release_service')->value);
      //If $serviceEntity has no node attached skip the record
      if(!$serviceEntity){
        continue;
      }
      if (CustNodeController::isGroupAdmin(zrml) || in_array($user_role, array('site_admin','administrator'))) {
        $buildRedirUrl = Url::fromRoute('hzd_release_management.deployed_releases', ['group' => $group_id])->toString();
        $edit_url = Url::fromRoute('entity.node.edit_form', ['node' => $deployedRelease->id()], array(
                    'query' => array(
                        'ser' => $deployedRelease->get('field_release_service')->value,
                        'rel' => $deployedRelease->get('field_earlywarning_release')->value,
                        'env' => $deployedRelease->get('field_environment')->value,
                        'destination' => $buildRedirUrl,
                    ),
                        )
        );
      }

      $archive_url = Url::fromUserInput('/archive/');
      $query = \Drupal::database()->select('node_field_data', 'nfd');
      $query->fields('nfd', array('title'));
      $query->condition('nfd.nid', $deployedRelease->get('field_earlywarning_release')->value, '=');
      $release = $query->execute()->fetchField();

      if ($deployedRelease->get('field_environment')->value == 1) {
        $environment = t('Produktion');
      } else if($deployedRelease->get('field_environment')->value == 2) {
        $environment = t('Pilot');
      } else {
        $query = \Drupal::database()->select('node_field_data', 'nfd');
        $query->fields('nfd', array('title'));
        $query->condition('nfd.nid', $deployedRelease->get('field_environment')->value, '=');
        $environment = $query->execute()->fetchField();
      }

      if ($deployedRelease->get('field_archived_release')->value != 1) {
        if (!empty($edit_url)) {
          $action = t('<a href="@edit_url">'.t('Edit')->render().'</a> | <a href="@archive_url" class = "archive_deployedRelease" nid = "@nid" 
                  >'.t('Archive')->render().'</a>  <span class = "loader"></span>', array(
              '@edit_url' => $edit_url->toString(),
              '@archive_url' => $archive_url->toString(),
              '@nid' => $deployedRelease->id(),
                  )
          );
        } else {
          $action = t('<a href="@archive_url" class = "archive_deployedRelease" nid = "@nid"  >'.t('Archive')->render().'</a> <span class = "loader"></span>', array(
              '@archive_url' => $archive_url->toString(),
              '@nid' => $deployedRelease->id(),
                  )
          );
        }
      } else {
        if (!empty($edit_url)) {
          $action = t('<a href="@edit_url">Edit</a>', array(
              '@edit_url' => $edit_url->toString(),
                  )
          );
        } else {
          $action = '';
        }
      }
      $state = isset($deployedRelease->get('field_user_state')->value) ? $states[$deployedRelease->get('field_user_state')->value] : '';
      $data[] = array(
          $state,
          $environment,
          $serviceEntity->get('title')->value,
          $release,
          date("d.m.Y", strtotime($deployedRelease->get('field_date_deployed')->value)),
          $action,
      );
    }

    $output['current_deploy_table']['table'] = array(
        '#theme' => 'table',
        '#header' => $header,
        '#rows' => $data,
        '#empty' => t('No Data Created Yet'),
        '#attributes' => array(
            'id' => 'current_deploysortable',
            'class' => 'tablesorter releases deployed_overview',
        ),
    );

    $output['current_deploy_table']['pager'] = array(
        '#type' => 'pager',
        '#prefix' => '<div id="pagination">',
        '#suffix' => '</div>',
        '#exclude_from_print' => 1,
    );

//    $output['archived_deployed_relesaes_table']['#prefix'] = '</div><div class = "archived_deployed_relesaes" >'
//            . '<div class = "deployed_release_title" ><strong>' . t("Archived deployed releases:") . '</strong></div>';
//    $header = array(t('Environment'), t('Service'), t('Release'), t('Date Deployed'), t('Action'));
//
//    $output['archived_deployed_relesaes_table']['table'] = array(
//        '#theme' => 'table',
//        '#header' => $header,
//        '#rows' => $archived,
//        '#attributes' => array(
//            'id' => 'archived_deploysortable',
//            'class' => 'tablesorter',
//        ),
//    );
//
//    $output['archived_deployed_relesaes_table']['pager2'] = array(
//        '#type' => 'pager',
//        '#prefix' => '<div id="pagination">',
//        '#suffix' => '</div>',
//    );
//
//    $output['archived_deployed_relesaes_table']['#suffix'] = "</div>";

    return $output;
  }

  /**
   *
   */
  static public function quickinfo_display_table($limit = NULL) {
    $content_type = 'quickinfo';
    $limit = 20;
    $sql = \Drupal::database()->select('node_field_data', 'nfd');
    $sql->Fields('nfd', array('nid', 'title', 'changed', 'uid'));
    $sql->addField('s', 'state');
    $sql->addField('nfui', 'field_unique_id_value');
    $sql->addField('nfrtn', 'field_related_transfer_number_value');
    $sql->leftJoin('node__field_unique_id', 'nfui', 'nfui.entity_id = nfd.nid');
    $sql->leftJoin('node__field_related_transfer_number', 'nfrtn', 'nfui.entity_id  = nfrtn.entity_id');
    $sql->leftJoin('cust_profile', 'cp', 'cp.uid  = nfd.uid');
    $sql->leftJoin('states', 's', 's.id  = cp.state_id');
    $sql->condition('nfd.type', $content_type, '=');
    $sql->condition('nfd.status', 1, '=');
    // $sql->condition('nfui.revision_id', 'nfd.vid', '=');
    //  $sql->condition('nfrtn.revision_id', 'nfd.vid', '=');.
    $sql->orderBy('nfui.field_unique_id_value', 'DESC');
    $pager = $sql->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit($limit);
    $result = $pager->execute()->fetchAll();

    $quickinfo_id = array('data' => t('Quickinfo Id'), 'class' => 'quickinfo-hdr');
    $state_name = array('data' => t('State'), 'class' => 'state-hdr');
    $title_name = array('data' => t('Title'), 'class' => 'title-hdr');
    $service_id = array('data' => t('Service ID'), 'class' => 'service-id-hdr');
    $transfer_num = array('data' => t('SW Transfer No.'), 'class' => 'related-transfer-num');
    $published_date = array('data' => t('Published on'), 'class' => 'published-on-hdr');
    $details_name = array('data' => t('Details'), 'class' => 'details-hdr');

    $header = array($quickinfo_id, $state_name, $title_name, $service_id, $transfer_num, $published_date, $details_name);

    $rows = array();
    foreach ($result as $node) {
      $id = $node->field_unique_id_value;
      $state_name = $node->state;
      $title = $node->title;

      $output1 = Node::load($node->nid);
      $other_services = '';
      // $other_services = array();
      $content_field = FieldStorageConfig::loadByName('node', 'field_other_services');

      $allowed_values = options_allowed_values($content_field);

      foreach ($output1->field_other_services as $service_ids) {
        // $other_services .= "<div>" . $allowed_values[$service_ids->value] . "</div>";.
        $other_services .= "<div>" . $allowed_values[$service_ids->value] . "</div>";
      }

      // $other_services['#markup'] = (string) $other_service;.
      $related_sw_transfer_num = $node->field_related_transfer_number_value;
      $published = date('d.m.Y', $node->changed);

      $absolute_path = Url::fromUserInput('/group/' . RELEASE_MANAGEMENT . '/rz-schnellinfos/' . $node->nid);

      $split_title = str_split($node->title, 50);
      // $quickinfo_title = strtolower(str_replace(" ","-", $split_title[0]));
      // $alias_path = 'release-management/rz-schnellinfos/'. $quickinfo_title;
      // $alias_path = 'release-management/rz-schnellinfos/'. $id;
      // 20140224 droy
      // This belongs to the publish function where the db queries are only executed once.
      // $source = db_result(db_query("SELECT src FROM {url_alias} WHERE src = '%s'",$absolute_path));
      // $destination = db_result(db_query("SELECT dst FROM {url_alias} WHERE dst = '%s'",$alias_path));
      // if (!$source && !$destination) {
      //    db_query("INSERT INTO {url_alias} (src,dst) VALUES ('%s', '%s')", $absolute_path, $alias_path);
      // }.
      $details = \Drupal::service('link_generator')->generate('Details', $absolute_path);
      // $data = \Drupal\Component\Utility\Html::load($other_services);
      $rows[] = array($id, $state_name, $title, t($other_services), $related_sw_transfer_num, $published, $details);
    }
    if ($rows) {
      $build['quickinfo_table'] = array(
          '#theme' => 'table',
          '#header' => $header,
          '#rows' => $rows,
          '#empty' => t('No Data Created Yet'),
          '#attributes' => ['id' => "quickinfo-sortable", 'class' => "tablesorter"],
      );

      $build['pager'] = array(
          '#type' => 'pager',
          '#prefix' => '<div id="pagination">',
          '#suffix' => '</div>',
      );
      return $build;
    }
    return $build = array(
        '#prefix' => '<div id="no-result">',
        '#markup' => t("No Data Created Yet"),
        '#suffix' => '</div>',
    );
  }

}
