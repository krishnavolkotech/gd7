<?php

namespace Drupal\hzd_release_management\Controller;

use Drupal\node\Entity\Node;
use Drupal\cust_group\Controller\CustNodeController;
use Drupal\Core\Link;
use Drupal\group\Entity\Group;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\hzd_release_management\HzdreleasemanagementHelper;
use Drupal\hzd_release_management\HzdreleasemanagementStorage;

// if(!defined('KONSONS'))
//  define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));
// if(!defined('RELEASE_MANAGEMENT'))
//  define('RELEASE_MANAGEMENT', 339);
// $_SESSION['Group_id'] = 339;.
define('zrml', Zentrale_Release_Manager_Lander);
define('DEPLOYED_RELESES_HEADING', \Drupal::config('hzd_release_management.settings')->get('deployed_releses'));

if (!defined('DISPLAY_LIMIT')) {
    define('DISPLAY_LIMIT', 20);
}

/**
 * Class ReadexcelController.
 *
 * @package Drupal\hzd_release_management\Controller
 */
class HzdReleases extends ControllerBase
{
    
    /**
     *
     */
    public function released() {
        global $base_url;
        $type = 'released';
        # $output['#title'] = $this->t('@type Releases', ['@type' => 'Released']);
	$output['#title'] = $this->t('Available Releases');
    $hzdReleaseManageStorage = new HzdreleasemanagementStorage();
    $output[] = $hzdReleaseManageStorage->release_info();
    $output[] = array('#markup' => '<div id = "released_results_wrapper">');

    $output[]['#attached']['library'] = array(
        'locale.libraries/translations',
        'locale.libraries/drupal.locale.datepicker',
        'hzd_release_management/hzd_release_management',
        //  'hzd_customizations/hzd_customizations',
        // 'hzd_release_management/hzd_release_management_sort',
//        'downtimes/downtimes',
    );
    // Add Some extra "settings" to use in JS.
    $output[]['#attached']['drupalSettings']['release_management'] = array(
        'type' => $type,
        'base_path' => $base_url,
    );
    $output[] = \Drupal::formBuilder()
        ->getForm('Drupal\hzd_release_management\Form\ReleaseFilterForm', $type); 

    $output[] = HzdreleasemanagementStorage::releases_display_table($type, NULL, DISPLAY_LIMIT);
//    $output[] = array('#markup' => '</div>');
        $output['#cache'] = ['tags' => ['node_list']];
    return $output;
  }
    
    /**
     *
     */
    public function documentation($service_id, $release_id) {
        $output[] = $this->documentation_page_link($service_id, $release_id);
        return $output;
    }
    
    /**
     *
     */
    public function getTitle($service_id, $release_id) {
        $release_name = db_query("SELECT title FROM {node_field_data} "
            . "where nid= :nid", array(":nid" => $release_id))->fetchField();
        $release_product = explode("_", $release_name);
        $release_versions = explode("-", $release_product[1]);
        $releases_title = $release_product[0] . "_" . $release_versions[0];
        return $this->t("Documentation for @title",['@title'=>$releases_title]);
    }
    
    /**
     *
     */
    public function DownloadDocumentFiles($service_id, $release_id) {
        $doc_values = HzdreleasemanagementHelper::get_document_args($service_id, $release_id);
        $zip = HzdreleasemanagementHelper::zip_file_path($doc_values);
        $path = \Drupal::service('file_system')->realpath("private://");
        $files_path = $path . "/releases/downloads";
        $zipfiles = $files_path . '/' . $zip;
        
        header("Content-type: application/zip");
        header("Content-Disposition: attachment; filename=$zip");
        header("Pragma: no-cache");
        header("Expires: 0");
        readfile("$zipfiles");
        exit;
    }
    
    /**
     *
     */
    public function inprogress() {
        global $base_url;
        $type = 'progress';
        # $output['#title'] = $this->t('@type Releases', ['@type' => 'In progress']);
	$output['#title'] = $this->t('In progress Releases');
        $output[] = HzdreleasemanagementStorage::release_info();
        $output[] = array('#markup' => '<div id = "released_results_wrapper">');
        $output[]['#attached']['library'] = array('locale.libraries/translations',
            'locale.libraries/drupal.locale.datepicker',
            'hzd_release_management/hzd_release_management',
            // 'hzd_customizations/hzd_customizations',.
//            'downtimes/downtimes',
        );
        // Add Some extra "settings" to use in JS.
        $output[]['#attached']['drupalSettings']['release_management'] = array(
            'type' => $type,
            'base_path' => $base_url,
        );
        
        // Echo '<pre>';  print_r($_SESSION['limit']);
        // echo '<pre>';  print_r($_SESSION['release_type']);   exit;
        // echo '<pre>';  print_r($_SESSION['service_release_type']);
        // echo '<pre>';  print_r($_SESSION['filter_where']); exit;.
        $output[] = \Drupal::formBuilder()->getForm('Drupal\hzd_release_management\Form\ReleaseFilterForm', $type);
//    $output[] = array('#markup' => "<div class = 'reset_form'>");
//    $output[] = HzdreleasemanagementHelper::releases_reset_element();
//    $output[] = array('#markup' => '</div><div style = "clear:both"></div>');
        $output[] = HzdreleasemanagementStorage::releases_display_table($type, NULL, DISPLAY_LIMIT);
//    $output[] = array('#markup' => '</div>');
        $output['#cache'] = ['tags' => ['node_list']];
        return $output;
    }
    
    /**
     *
     */
    public function locked() {
        $type = 'locked';
        # $output['#title'] = $this->t('@type Releases', ['@type' => 'Locked']);
	$output['#title'] = $this->t('Locked Releases');
        global $base_url;
        //   $output[] = array('#markup' => '<div id = "released_results_wrapper">');
        $output[]['#attached']['library'] = array('locale.libraries/translations',
            'locale.libraries/drupal.locale.datepicker',
            'hzd_release_management/hzd_release_management',
            // 'hzd_customizations/hzd_customizations',.
//            'downtimes/downtimes',
        );
        // Add Some extra "settings" to use in JS.
        $output[]['#attached']['drupalSettings']['release_management'] = array(
            'type' => $type,
            'base_path' => $base_url,
        );
        $output[] = \Drupal::formBuilder()->getForm('Drupal\hzd_release_management\Form\ReleaseFilterForm', $type);
//    $output[] = array('#markup' => "<div class = 'reset_form'>");
//    $output[] = HzdreleasemanagementHelper::releases_reset_element();
//    $output[] = array('#markup' => '</div><div style = "clear:both"></div>');
        $output[] = HzdreleasemanagementStorage::releases_display_table($type, NULL, DISPLAY_LIMIT);
//    $output[] = array('#markup' => '</div>');
        $output['#cache'] = ['tags' => ['node_list']];
        return $output;
    }
    
    /**
     *
     */
    public function deployed() {
        global $base_url;
        $type = 'deployed';
        $output['#title'] = $this->t('Deployed Releases');
        $output[] = HzdreleasemanagementStorage::deployed_releases_text();
        
        $output[]['#attached']['drupalSettings']['release_management'] = array(
            'type' => $type,
            'base_path' => $base_url,
        );
        $output[] = \Drupal::formBuilder()->getForm('Drupal\hzd_release_management\Form\ReleaseFilterForm', $type);
//    $output[] = array('#markup' => "<div class = 'reset_form'>");
//    $output[] = HzdreleasemanagementHelper::releases_reset_element();
//    $output[] = array('#markup' => '</div><div style = "clear:both"></div>');
        //  dpm($_SESSION);
        $output[] = HzdreleasemanagementStorage::deployed_releases_displaytable();
//    $output[] = array('#markup' => '</div>');
        $output['#cache'] = ['tags' => ['node_list']];
        return $output;
    }
    
    /**
     *
     */
    public function documentation_page_link($service_id, $release_id) {
        $query = db_query("SELECT field_documentation_link_value FROM {node__field_documentation_link} where entity_id = :eid and field_documentation_link_value <> 'NULL'", array(":eid" => $release_id))->fetchField();
        $query_explode = explode('/', $query);
        $query_explode_search = array_search('secure-downloads', $query_explode);
        
        // Check secure-downloads string in documentaion link.
        if ($query_explode_search) {
            $output = \Drupal::config('hzd_release_management.settings')->get('secure_download_text')['value'];
            $output .= "<h4><a target = '_blank' href ='$query'>" . t("Please click this secure download link to download the documentation as a ZIP file directly from the DSL (authentication required)") . "</a></h4>";
            return $output;
        } else {
            $doc_values = HzdreleasemanagementHelper::get_document_args($service_id, $release_id);
            $arr = $doc_values['arr'];
            $files = $doc_values['files'];
            
//            $major_directory = $release_product . "_" . max($arr);
            unset($files[0]);
            unset($files[1]);
            // Check the documentation link download or not. if not failed download link will display.
            if (!empty($files)) {
                
                $host = \Drupal::request()->getHost();
                $host_path = "http://" . $host . "/system/files/releases/" . strtolower($doc_values['service_name']) . "/" . $doc_values['product'];
                unset($arr[0]);
                unset($arr[1]);
                
                // Get the count and release versions.
                $version_count = HzdreleasemanagementHelper::get_release_version_count($doc_values['releasess'], $arr);
                
                // Display zip file path for specific release.
                $args = array("get_product" => $doc_values['get_product'], "count" => $version_count['count'], "arr" => $version_count['arr'], "dir" => $doc_values['dir'], "host_path" => $host_path, "product" => $doc_values['product'], "service_name" => $doc_values['service_name'], "upper_product" => $doc_values['upper_product'], "zip_link" => $doc_values['zip_link']);
                $cache = \Drupal::cache()->get('release_doc_import_' . $release_id);
                
                $doc_options['attributes'] = array('class' => 'document-link');
                $doc_url = Url::fromUserInput('/documentation_link_zip/' . $service_id . '/' . $release_id, $doc_options);
                $output = \Drupal::l(t("Please click here to download all documents for this release as a ZIP file."), $doc_url);
                if (!$cache) {
                    // Display documentation table for specific release.
                    $output .= "<table border='1'><tr><th>" . t('Folder') . "</th><th>" . t('Documents') . "</th></tr>";
                    $sub_doc_folders = array("afb", "benutzerhandbuch", "betriebshandbuch", "releasenotes", "sonstige", "zertifikat");
                    foreach ($sub_doc_folders as $values) {
                        $output .= HzdreleasemanagementHelper::display_doc_folders($args, $values);
                    }
                    $output .= "</table>";
                    \Drupal::cache('render')->set('release_doc_import_' . $release_id, $output, CacheBackendInterface::CACHE_PERMANENT);
                } else {
                    $output = $cache->data;
                }
                $build['#markup'] = $output;
                return $build;
            } // Display failed download text.
            else {
                // $output = variable_get('failed_download_text', NULL);.
                $output = \Drupal::config('hzd_release_management.settings')->get('failed_download_text')['value'];
                $string = t('Please click here to download the documentation as a ZIP file directly from the DSL (authentication required)');
                $output .= "<h4><a target = '_blank' href='$query'>" . t("Please click here to download the documentation as a ZIP file directly from the DSL (authentication required)") . "</a></h4>";
                $build['#markup'] = $output;
                $build['#cache'] = ['tags' => ['node_list']];
                return $build;
            }
        }
    }
    
    /*     * ******************** DEPLOYED RELEASES ************************************* */
    
    /**
     * Callback of the deployed releases.
     */
    public function deployed_releases() {
        global $base_url;
        
        $output['#attached']['drupalSettings']['deploy_release'] = array(
            'type' => 'deployed_releases',
            'base_path' => $base_url,
            'basePath' => $base_url,
        );
        
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
        $user_state = $query->execute()->fetchField();
        
        $user_role = $user->getRoles(TRUE);
        $group = Group::load(zrml);
  
      $groupMember = $group->getMember(\Drupal::currentUser());
        if (($groupMember && $groupMember->getGroupContent()->get('request_status')->value == 1) || in_array($user_role, array('site_administrator'))) {
            $breadcrumb = array();
            $url = Url::fromRoute('/');
            $link = Link::fromTextAndUrl(t('Home'), $url);
            
            if ($group->label) {
//                $url = Url::fromUserInput('/group/' . $group_id);
//                $link = \Drupal::l($group->label, $url);
//                $breadcrumb[] = $link;
            }
            $breadcrumb[] = t('Deployed Releases');
            $output['#breadcrumb'] = $breadcrumb;
            $output['#attachment']['library'] = array(
                'hzd_release_management/hzd_release_management',
            );
            $query = \Drupal::database()->select('states', 's');
            $query->Fields('s', array('state'));
            $query->condition('id', $user_state, '=');
            $state = $query->execute()->fetchField();
            
            // $user_state = db_result(db_query("SELECT state FROM {states} where id = %d", $user->user_state));.
            if ((CustNodeController::isGroupAdmin(zrml) == TRUE) || in_array($user_role, array('site_administrator'))) {
//                $output['#title'] = $this->t("Deployed Releases");
            } else {
                $output['#title'] = t("Deployed Releases") . " in " . $state;
            }
            
            // DEPLOYED_RELESES_HEADING = if (DEPLOYED_RELESES_HEADING) ? DEPLOYED_RELESES_HEADING : 11220;.
            $node = Node::load(\Drupal::config('hzd_release_management.settings')
                ->get('deployed_releses') ? DEPLOYED_RELESES_HEADING : 11220);
            
            $output['node_body']['#markup'] = $node->body->value;
            $output['newdeployrelease']['#prefix'] = "<div class = 'deployedReleases'><strong>";
            $output['newdeployrelease']['#markup'] = t("Enter a new deployed release:");
            $output['newdeployrelease']['#suffix'] = "</strong></div>";
            $output['deploy_release_form']['#prefix'] = "<div id = 'deployedreleases_posting'>";
            $output['deploy_release_form']['form']['#prefix'] = '<div id = "deployed_release_form_warapper">';
            $output['deploy_release_form']['form']['render'] = \Drupal::formBuilder()->getForm('\Drupal\hzd_release_management\Form\Deployedreleasecreateform');
            // $output['deploy_release_form']['form']['#suffix'] = '</div>';
            //  $output['deploy_release_form']['reset']['#prefix'] = "<div class = 'reset_form'>" .
            $output['deploy_release_form']['reset'] = HzdreleasemanagementHelper::releases_reset_element();
            $output['deploy_release_form']['reset']['#suffix'] = "</div>";
            $output['deploy_release_form']['clear_div']['#markup'] = "<div style = 'clear:both'></div>";
            $output['deploy_release_form']['#suffix'] = '</div>';
            $output['deployment_table'] = HzdreleasemanagementHelper::deployed_releases_table();
        } else {
            $output['#markup'] = t('You are not authorized to access this page.');
        }
        $output['#cache'] = ['tags' => ['node_list']];
        return $output;
    }
    
}
