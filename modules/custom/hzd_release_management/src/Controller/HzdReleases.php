<?php
/**
 * @file
 * Contains \Drupal\hzd_release_management\Controller\ReadexcelController
 */

namespace Drupal\hzd_release_management\Controller;

use Drupal\Core\Database\Database;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\hzd_release_management\HzdreleasemanagementStorage;
use Drupal\hzd_release_management\HzdreleasemanagementHelper;

define('RELEASE_MANAGEMENT', 339);
define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));
$_SESSION['Group_id'] = 339;

/**
 * Class ReadexcelController
 * @package Drupal\hzd_release_management\Controller
 */
class HzdReleases extends ControllerBase {

  public function released() {
    $type = 'released';
    $output[] = HzdreleasemanagementStorage::release_info();
    $output[] = array('#markup' => '<div id = "released_results_wrapper">');

    $request = \Drupal::request();
    $page = $request->get('page');

    if (!isset($page)) {
      unset($_SESSION['filter_where']);
      unset($_SESSION['limit']);
      unset($_SESSION['release_type']);
      unset($_SESSION['service_release_type']);
    }
    $output[] =  \Drupal::formBuilder()->getForm('Drupal\hzd_release_management\Form\ReleaseFilterForm', $type);
    $output[] = array('#markup' => "<div class = 'reset_form'>"); 
    $output[] = HzdreleasemanagementHelper::releases_reset_element();
    $output[] = array('#markup' =>'</div><div style = "clear:both"></div>');
    $output[] = HzdreleasemanagementStorage::releases_display_table($type);
    $output[] = array('#markup' => '</div>');
    return $output;
  }

  public function documentation($service_id, $release_id) {
    $output[] = $this->documentation_page_link($service_id, $release_id);
    return $output;
  }

  public function getTitle($service_id, $release_id) {
    $release_name = db_query("SELECT title FROM {node_field_data} where nid= :nid", array(":nid" => $release_id))->fetchField();
    $release_product = explode("_", $release_name);
    $release_versions = explode("-", $release_product[1]);
    $releases_title = $release_product[0] . "_" . $release_versions[0];
    return "Documentation for " . $releases_title;
  }

  public function DownloadDocumentFiles($service_id, $release_id) {
    $doc_values = HzdreleasemanagementHelper::get_document_args($service_id, $release_id);
    $zip = HzdreleasemanagementHelper::zip_file_path($doc_values);
    $path = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
    $files_path = $path . "/releases/downloads";
    $zipfiles = $files_path . '/' . $zip;

    header("Content-type: application/zip");
    header("Content-Disposition: attachment; filename=$zip");
    header("Pragma: no-cache");
    header("Expires: 0");
    readfile("$zipfiles");
    exit;
  }

  public function inprogress() {
    $type = 'progress';
    $output[] = HzdreleasemanagementStorage::release_info();
    $output[] = array('#markup' => '<div id = "released_results_wrapper">');

    $request = \Drupal::request();
    $page = $request->get('page');
    if (!isset($page)) {
      unset($_SESSION['filter_where']);
      unset($_SESSION['limit']);
      unset($_SESSION['release_type']);
      unset($_SESSION['service_release_type']);
    }
    $output[] = \Drupal::formBuilder()->getForm('Drupal\hzd_release_management\Form\ReleaseFilterForm', $type);
    $output[] = array('#markup' => "<div class = 'reset_form'>"); 
    $output[] = HzdreleasemanagementHelper::releases_reset_element();
    $output[] = array('#markup' =>'</div><div style = "clear:both"></div>');
    $output[] = HzdreleasemanagementStorage::releases_display_table($type);
    $output[] = array('#markup' => '</div>');



    return $output;
  }

  public function locked() {
    $type = 'locked';
    $output[] = array('#markup' => '<div id = "released_results_wrapper">');
    $request = \Drupal::request();
    $page = $request->get('page');
    if (!isset($page)) {
      unset($_SESSION['filter_where']);
      unset($_SESSION['limit']);
      unset($_SESSION['release_type']);
      unset($_SESSION['service_release_type']);
    }
    $output[] =  \Drupal::formBuilder()->getForm('Drupal\hzd_release_management\Form\ReleaseFilterForm', $type);
    $output[] = array('#markup' => "<div class = 'reset_form'>"); 
    $output[] = HzdreleasemanagementHelper::releases_reset_element();
    $output[] = array('#markup' =>'</div><div style = "clear:both"></div>');
    $output[] = HzdreleasemanagementStorage::releases_display_table($type);
    $output[] = array('#markup' => '</div>');
    return $output;
  }

  public function deployed() {
    $type = 'deployed';
    $output[] = HzdreleasemanagementStorage::deployed_releases_text();
    $output[] = array('#markup' => '<div id = "released_results_wrapper">');
    $request = \Drupal::request();
    $page = $request->get('page');
    if (!isset($page)) {
      unset($_SESSION['deploy_filter_options']);
      unset($_SESSION['limit']);
      unset($_SESSION['release_type']);
    }
    $output[] =  \Drupal::formBuilder()->getForm('Drupal\hzd_release_management\Form\ReleaseFilterForm', $type);
    $output[] = array('#markup' => "<div class = 'reset_form'>"); 
    $output[] = HzdreleasemanagementHelper::releases_reset_element();
    $output[] = array('#markup' =>'</div><div style = "clear:both"></div>');
    $output[] = HzdreleasemanagementStorage::deployed_releases_displaytable();
    $output[] = array('#markup' => '</div>');
    return $output;
  }


  public function documentation_page_link($service_id, $release_id) {
    $query = db_query("SELECT field_documentation_link_value FROM {node__field_documentation_link} where entity_id = :eid and field_documentation_link_value <> 'NULL'", array(":eid" => $release_id))->fetchField();
    $query_explode = explode('/', $query);
    $query_explode_search = array_search('secure-downloads', $query_explode);

    // Check secure-downloads string in documentaion link
    if ($query_explode_search) {
      $output = \Drupal::config('hzd_release_management.settings')->get('secure_download_text')['value'];
      $output .= "<h4><a target = '_blank' href ='$query'>" . t("Please click this secure download link to download the documentation as a ZIP file directly from the DSL (authentication required)") . "</a></h4>";
      return $output;
    }
    else {
    $doc_values = HzdreleasemanagementHelper::get_document_args($service_id, $release_id);
    $arr = $doc_values['arr'];
    $files = $doc_values['files'];

    $major_directory = $release_product . "_" . max($arr);
    unset($files[0]);
    unset($files[1]);
    // Check the documentation link download or not. if not failed download link will display.
    if (!empty($files)) {

      $host = \Drupal::request()->getHost();
      $host_path = "http://" . $host . "/sites/default/files/releases/" . strtolower($doc_values['service_name']) . "/" . $doc_values['product'];
      unset($arr[0]);
      unset($arr[1]);

      // get the count and release versions.
      $version_count = HzdreleasemanagementHelper::get_release_version_count($doc_values['releasess'], $arr);

      // display zip file path for specific release
      $args = array("get_product" => $doc_values['get_product'], "count" => $version_count['count'], "arr" => $version_count['arr'], "dir" => $doc_values['dir'], "host_path" => $host_path , "product" => $doc_values['product'], "service_name" => $doc_values['service_name'], "upper_product" => $doc_values['upper_product'], "zip_link" => $doc_values['zip_link']);
      $cache = \Drupal::cache()->get('release_doc_import_' . $release_id);
      
      $doc_options['attributes'] = array('class' => 'document-link');
      $doc_url = Url::fromUserInput('/documentation_link_zip/' . $service_id . '/' . $release_id, $doc_options);
      $output = \Drupal::l(t("Please click here to download all documents for this release as a ZIP file."), $doc_url);
      if(!$cache) {
        // display documentation table for specific release.
        $output .= "<table border='1'><tr><th>" . t('Folder') . "</th><th>" . t('Documents') . "</th></tr>";
        $sub_doc_folders = array("afb","benutzerhandbuch","betriebshandbuch","releasenotes","sonstige","zertifikat");
        foreach ($sub_doc_folders as $values) {
          $output .= HzdreleasemanagementHelper::display_doc_folders($args, $values);
        }
        $output .= "</table>";
        \Drupal::cache('render')->set('release_doc_import_' . $release_id, $output, \Drupal\Core\Cache\CacheBackendInterface::CACHE_PERMANENT);
      }
      else {
        $output = $cache->data;
      }
      $build['#markup'] = $output;
      return $build;
    }

    // display failed download text

    else {
      //$output = variable_get('failed_download_text', NULL);
      $output = \Drupal::config('hzd_release_management.settings')->get('failed_download_text')['value'];
      $string = t('Please click here to download the documentation as a ZIP file directly from the DSL (authentication required)');
      $output .= "<h4><a target = '_blank' href='$query'>" . t("Please click here to download the documentation as a ZIP file directly from the DSL (authentication required)") . "</a></h4>";
      $build['#markup'] = $output;
      return $build;
    }
  }
  }
  
}
