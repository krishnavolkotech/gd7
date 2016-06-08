<?php

namespace Drupal\hzd_release_management;

use Drupal\hzd_services\HzdservicesStorage; 
use Drupal\hzd_services\HzdservicesHelper;

define('RELEASE_MANAGEMENT', 339);

class HzdreleasemanagementHelper { 
 /*
  * Returns TRUE if the service exists
  */
 /**
 function service_exist($service, $type) {
   $services = HzdservicesStorage::get_related_services($type);
   # return in_array(trim($service), $services); 
   return in_array(strtoupper(trim($service)), array_map('strtoupper',$services));
 }
 */

 /*
  *Title in header array  = release in the csv file
  */
static function _csv_headers() {
  $released_path = \Drupal::config('hzd_release_management.settings')->get('import_path_csv_released');
  $rejected_path = \Drupal::config('hzd_release_management.settings')->get('import_path_csv_rejected');
  $locked_path = \Drupal::config('hzd_release_management.settings')->get('import_path_csv_locked');
  $progress_path = \Drupal::config('hzd_release_management.settings')->get('import_path_csv_progress');
  $ex_eoss_path = \Drupal::config('hzd_release_management.settings')->get('import_path_csv_ex_eoss');
  
  if ($released_path) {
    $path['released'] = $released_path;
    $header_values['released'] = array('title', 'status', 'service', 'date', 'link', 'documentation_link');
  }
  if ($progress_path) {
    $path['progress'] = $progress_path;
    $header_values['progress'] = array('title', 'status', 'service', 'date', 'link', 'documentation_link');
  }
  if ($ex_eoss_path) {
    $path['ex_eoss'] = $ex_eoss_path;
    $header_values['ex_eoss'] = array('title', 'status', 'service', 'date', 'link', 'documentation_link');
  }


/*  if ($rejected_path) {
    $path['rejected'] = $rejected_path;
    $header_values['rejected'] = array('title', 'status', 'service', 'date', 'comment');
  }
*/  
  if ($locked_path) {
    $path['locked'] = $locked_path;
    $header_values['locked'] = array('title', 'status', 'service', 'date', 'comment');
  }
  $path_header = array('path' => $path, 'headers' => $header_values);
  return $path_header;
 }


/*
 * validation for the release csv import
 */ 
function validate_releases_csv(&$values) {
  if ($values['date']) {
    $replace = array('/' => '.', '-' => '.');
    $formatted_date = strtr($values['date'], $replace);
    if ($formatted_date) {
      $date_time = explode(" ", $formatted_date);
      $date_format = explode(".", $date_time[0]);
      $time_format = explode(":", $date_time[1]);
      if ($time_format[0] && $time_format[1] && $date_format[1] && $date_format[0] &&$date_format[2]) {
        $date = mktime((int)$time_format[0], (int)$time_format[1], (int)$time_format[2], (int)$date_format[1], (int)$date_format[0], (int)$date_format[2]);
      }
      $values['date'] = $date;
    }
  }
  $type = 'releases';
  $service = $values['service'];
  $service = trim($service);
  
  if (HzdservicesHelper::service_exist($service, $type)) {
    // echo 'haiiiii8';  exit;
    $services = HzdservicesStorage::get_related_services($type);
    $service_id = array_keys($services, $service);
    $values['service'] = $service_id[0];
    return TRUE;
  }
  else {
    // echo 'hahsjdhfjsdhf';  exit;
    $mail = \Drupal::config('hzd_release_management.settings')->get('import_mail_releases');
    $subject = 'New service found while importing Releases';
    $body = t("New service found in import file: ") . $service . ' ' . t("Please add this service to the release database.");
    HzdservicesHelper::send_problems_notification($mail, $subject, $body);
    return FALSE;
  }
  return FALSE;
 }

  // get list of services based on the release type
  public function get_release_type_services($string = NULL, $release_type = NULL) {
    $group_id = ($_SESSION['Group_id'] ? $_SESSION['Group_id'] : RELEASE_MANAGEMENT);
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
    foreach($result as $vals) {
      $default_services[$vals->nid] = $vals->title;
    }
    return array('services' => $default_services);
  }

/**
 * Returns the releases associated with the provided sevices
 * @service is service nide id for which releses has to be returned
 *
 */
static function get_dependent_release($service = NULL) {
  // $tempstore = \Drupal::service('user.private_tempstore')->get('hzd_release_management');
  // $id = $tempstore->get('Group_id');
  $id = $_SESSION['Group_id'];
  $group_id = ($id ? $id : RELEASE_MANAGEMENT);
  $query = db_select('node_field_data', 'n');
  $query->join('node__field_relese_services', 'nfrs', 'n.nid = nfrs.entity_id');
  $query->join('group_releases_view', 'grv', 'nfrs.field_relese_services_target_id = grv.service_id');
  $query->fields('n', array('nid', 'title'))
  ->condition('grv.group_id', $group_id, '=')
  ->condition('n.type', 'release', '=')
  ->orderBy('n.title', 'ASC');
  if($service) {
    $query->condition('nfrs.field_relese_services_target_id', $service);
  }
  $result = $query->execute()->fetchAll();
  $default_release[] = "Release";
  foreach($result as $vals) {
    $default_release[$vals->nid] = $vals->title;
  }
  return array('releases' => $default_release);
 }

  public function get_release($string = NULL, $service = NULL) {
    $id = $_SESSION['Group_id'];
    $group_id = ($_SESSION['Group_id'] ? $_SESSION['Group_id'] : RELEASE_MANAGEMENT);
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
    $default_release = array('Release');
    foreach($result as $vals) {
      $default_release[$vals->nid] = $vals->title;
    }
    return array('releases' => $default_release);
  }

  /**
   * Using service id and release id get release name, product name and service name.
   */
  static function get_document_args($service_id, $release_id) {
    
  // get service name and release name.

  $service_name = strtolower(db_query("SELECT title FROM {node_field_data} where nid = :nid", array(":nid" => $service_id))->fetchField());
  $release_name = db_query("SELECT title FROM {node_field_data} where nid = :nid", array(":nid" => $release_id))->fetchField();
  $release_product = explode("_", $release_name);
  $release_versions = explode("-", $release_product[1]);
  $releases_title = $release_product[0] . "_" . $release_versions[0];
  
  // get the documentation folder path.
  $file_path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");

  $get_product = $file_path . "/releases/" . strtolower($service_name) . "/" . strtolower($release_product[0]);
  $product = strtolower($release_product[0]);
  $upper_product = $release_product[0];
  $new_release = $get_product . "/" . strtolower($release_name);
  $dir =  $new_release . "/dokumentation";
  
  // get the release versions.
  if (is_dir($dir)) {
    $files = scandir($dir);
  }

  $get_product_scan = scandir($get_product);
  $count = count($get_product_scan);
  if ($count >= 2) {
    foreach ($get_product_scan as $key => $values) {
      $get_release_values = explode("_", $values);
      $get_after_release_value = array_shift($get_release_values);
      $get_versions_value = join("_",$get_release_values);
      $arr[] = $get_versions_value;
    }
    
    // get the documentation link zip file
    $field_link_value = db_query("SELECT field_documentation_link_value FROM {node__field_documentation_link} WHERE entity_id= :eid", array(":eid" => $release_id))->fetchField();
    $field_link_value_split = explode("/",$field_link_value);
    $get_link = array_pop($field_link_value_split);
    $remove_link_zip = explode(".zi",$get_link);
    $get_releasess_version = array_shift($release_product);
    $releasess = strtolower(join("_",$release_product));
    $zip_link = strtolower($remove_link_zip[0]);

    $doc_values = array("get_product" => $get_product, "arr" => $arr, "dir" => $dir,  "product" => $product, "service_name" => $service_name, "upper_product" => $upper_product, "files" => $files, "releasess" => $releasess, "zip_link" => $zip_link, "release_name" => $release_name);
    return $doc_values;
  }
  else {
    return false;
  }
  }
  
  /**
   * function to create zip file path
   * @param $args array of arr, dir, host_path, product, servie_name, product, zip_link.
   * @return $zip string zip file path which created by compressing document folder.
   */
  public function zip_file_path($doc_values) {
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
    $file_path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    $release_dir = $file_path . "/releases/'" . $service_name . "'";
    $new_directory = $release_dir . "/" . $product . "/" . $product . "_" . max($arr) . "/dokumentation";
    $root_path = $file_path . "/releases/downloads";
    $release_name = $doc_values['release_name'];
    $latest_zip_version = $root_path . "/" . $release_name . "_doku.zip";

    // Check zip file exist or not
    if (!file_exists($latest_zip_version)) {
    shell_exec("mkdir -p " . $root_path . "/" . $release_name . "_doku");
    $doc_files = scandir(str_replace("'","",$new_directory));
    unset($doc_files[0]);
    unset($doc_files[1]);
    foreach ($doc_files as $values) {
      shell_exec("cp -r " . $new_directory . "/" . $values . " " . $root_path . "/" . $release_name . "_doku");
    }
    $array = array("betriebshandbuch", "afb", "releasenotes", "sonstige", "benutzerhandbuch", "zertifikat");
    $copy_doc_sub_folders = self::copy_doc_sub_folders($doc_values, $array, $root_path);
    // creating a zip
    $release_path = $file_path . "/releases/downloads/" . $release_name . "_doku";
    $files_path = $file_path . "/releases/downloads";
    $release_name_doc = $release_name . "_doku";
    $sh_zip = "./core/misc/create_zip.sh";
    shell_exec("sh $sh_zip $files_path $release_name_doc");
    shell_exec("rm -rf " . $release_path);
  }
  $host_path = "http://" . $host . "/files/releases/downloads";
  $zip = $release_name . "_doku.zip";
  return $zip;
  }

  public function copy_doc_sub_folders($doc_values, $array, $root_path) {
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
      $search = array_search($values,$new_dir_path_scandir);
      if ($search) {
        $true = true;
      }
      else {
        if ($values == 'betriebshandbuch') {
	      $i = 1;
	      // Copy documentation sub folders to previous version.
        while($i <= $count) {
	        $a = false;
	        if(count($arr_copy) > 0) {
	        $version = max($arr_copy);
	        $search_version = array_search($version,$arr_copy);
	        unset($arr_copy[$search_version]);
	        $file_path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
	        $release_dir = $file_path . "/releases/" . $service_name;
	        if (count($arr_copy) > 0) {
	          $title = $upper_product . "_" . max($arr_copy);
	          $new_directory = $release_dir . "/" . $product . "/" . $product . "_" . max($arr_copy) . "/dokumentation"; 
	          if(is_dir($new_directory)) {
		          $zip_file_path_scandir = scandir($new_directory);
		          $search = array_search($values,$zip_file_path_scandir);
		          if($search) {
		            $release_dir = $file_path . "/releases/'" . $service_name . "'";
		            $new_directory = $release_dir . "/" . $product . "/" . $product . "_" . max($arr_copy) . "/dokumentation";
		            shell_exec("cp -r " . $new_directory . "/" . $values . " " . $root_path_release);
		            $a = true;
		          }
	          }
	        }
	      }
	      else {
	        $i = $i+1;
	      }

	      if($a) {
	        break;
	      }
	      }
      } 
    }
  }
}

  /*
  * @return count and release versions
  */
  public function get_release_version_count($releasess, $arr) {
  
  // get the count and upto present release versions.
  $count_search = array_search($releasess,$arr);
  $key_arrays = array_keys($arr);
  $max_key_arrays = max($key_arrays);
  $count_search = $count_search+1;
  for ($count_search; $count_search <= $max_key_arrays;$count_search++) {
    unset($arr[$count_search]);
  }
  // $count = count($arr);

  $get_major_release = explode(".", $releasess);
  foreach ($arr as $key => $valu) {
    $arr_versions = explode(".", $valu);
    if ($get_major_release[0] !=  $arr_versions[0]) {
      unset($arr[$key]);
    }
  }
  $count = count($arr);

  $version_count = array("count" => $count, "arr" => $arr);
  return $version_count;
  }
  
  /*
  * Display documentation files in release documentation table.
  * @param $args array of arr, dir, host_path, get_product, product, count.
  * @param $values array of document sub folders name
  * @return nothing
  */
  public function display_doc_folders($args, $values) {
  $get_product = $args['get_product'];
  $count = $args['count'];
  $arr = $args['arr'];
  $dir = $args['dir'];
  $host_path = $args['host_path'];
  $product = $args['product'];
  $major_file = scandir($dir);
  $i = 1;

  while($i <= $count) {
    $true = false;
    $search_folder = array_search($values, $major_file);
    if ($search_folder) {
      $folder_name = self::display_folder_name($values);
      $output .= "<tr><td>" . $folder_name .  "</td><td>";
      $afb_scan = scandir($dir . "/" . $values);
      unset($afb_scan[0]);
      unset($afb_scan[1]);
      $link_path = $host_path . "/" . $product . "_" . max($arr) . "/dokumentation/" . $values;
      foreach ($afb_scan as $value) {
       	$new_path = $link_path . "/" . $value;
	      $output .= "<div><a target = '_blank' href = '$new_path'>" . $value . "</a></div>";
      }
      return $output .= "</td></tr>";
      $true = true;
    }
    else {
      if ($values == 'betriebshandbuch') {
	    // get previous version release documentation.
	    $version = max($arr);
	    $search_version = array_search($version,$arr);
	    unset($arr[$search_version]);
	    if (!empty($arr)) {
	      $paths = $get_product . "/" . $product . "_" . max($arr) . "/dokumentation";
	      if (is_dir($paths)) {
	        $major_file = scandir($paths);
	        $dir = $paths;
	      }
	    }   
	    $i++;
      }
      else {
	    $true = true;
      }
    }
    if ($true) {
      break;
    }
  }
  }

  /*
  * function to display folder names in release documentation table.
  */
  public function display_folder_name($values) {
  switch($values) {
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

  static function releases_reset_element() {
    return  $form['reset'] = array(
      '#type' => 'button', 
      '#value' => t('Reset'),
      '#attributes' => array('onclick' => "reset_form_elements()"),
      '#prefix' => "<div class = 'reset_all'>",
      '#suffix' => "</div>",
    );
  }
}
