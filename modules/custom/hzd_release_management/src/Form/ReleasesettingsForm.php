<?php

/**
 * @file
 * Contains \Drupal\problem_management\Form\ProblemsettingsForm.
 */

namespace Drupal\hzd_release_management\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hzd_services\HzdservicesStorage;
// use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\system\Entity\Menu;
use Drupal\hzd_release_management\HzdreleasemanagementStorage;
use Drupal\hzd_customizations\HzdcustomisationStorage;

//if(!defined('KONSONS'))
//  define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));
//if(!defined('RELEASE_MANAGEMENT'))
//  define('RELEASE_MANAGEMENT', 339);

// TODO
$_SESSION['Group_id'] = 339;


class ReleasesettingsForm extends FormBase {

 
 /**
  * {@inheritDoc}
  */
  public function getFormId() {
    return 'release_settings_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

	  global $base_url;
	  $breadcrumb = array();
//	  $breadcrumb[] = l(t('Home'), NULL);
	  if (isset($_SESSION['Group_name'])) {
//	    $breadcrumb[] = l(t($_SESSION['Group_name']), 'node/'. $_SESSION['Group_id']);
	  }
//	  $breadcrumb[] = drupal_get_title() ;
//	  drupal_set_breadcrumb($breadcrumb);      
	 

	  //Getting the default Services
	//  $query = db_query("select service_id from {group_releases_view} where group_id = %d", $_SESSION['Group_id']);
          $services = HzdreleasemanagementStorage::get_default_release_services_current_session(); 
          foreach ($services as $service) { 
	    $default_services[$service->service_id] = $service->service_id;
            // $services['service_id'];
	  }
	  //  $services_obj= db_query("SELECT title, n.nid FROM {node} n, {content_field_service_type} cfst WHERE n.nid = cfst.nid and field_service_type_value = %d ", RELEASE_SERVICES);
	  //while ($services = db_fetch_array($services_obj)) {
	  //$options[$services['nid']] = $services['title'];
	  //}
      //    echo '<pre>';  print_r($default_services);  exit;
	  $type = 'releases';
	  $options = HzdservicesStorage::get_related_services($type);

//	  $view_path = variable_get('import_alias_releases', 'releases ');
          $view_path = \Drupal::config('hzd_release_management.settings')->get('import_alias_releases');
//	  $path = $base_url . url('node/' . $_SESSION['Group_id'] . '/' . $view_path);
          //  $path = Url::fromInternalUri(array('node', $group_id ,$view_path)); 
          //  echo '<pre>';  print_r($path);  exit;
          // $path = URL::fromRoute('entity.node.canonical', array('node' => $group_id));




    /**
    global $base_url;
    $breadcrumb = array();
   // $breadcrumb[] = \Drupal::l(t('Home'), Url::setUnrouted());
   // $group_name =  \Drupal::service('user.private_tempstore')->get()->get('Group_name');
    $group_name = $_SESSION['Group_name'];
   // $group_id = \Drupal::service('user.private_tempstore')->get()->get('Group_id');
    $group_id = $_SESSION['Group_id'];

    if ($group_name) {
     // Url::fromUserInput('/node/' . $problems_node->nid->value
     // $breadcrumb[] = \Drupal::l(t($group_name),  Url::fromUserInput(array('/node/' . $group_id)));
    }

    $request = \Drupal::request();
    if ($route = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
      $breadcrumb[] = \Drupal::service('title_resolver')->getTitle($request, $route);
    }
    
    // Drupal::service('breadcrumb')->set($breadcrumb);
    $breadcrumb_manager = \Drupal::service('breadcrumb');
    $current_route_match = \Drupal::service('current_route_match');
    $breadcrumb = $breadcrumb_manager->build($current_route_match);

    $default_services[$services['service_id']] = HzdservicesStorage::get_default_services_current_session();

    $type = 'problems';
    $options = HzdservicesStorage::get_related_services($type);

    //  $services_obj= db_query("SELECT title, n.nid FROM {node} n, {content_field_service_type} cfst WHERE n.nid = cfst.nid and   field_service_type_value = %d ", 3);
    //while ($services = db_fetch_array($services_obj)) {
    //$options[$services['nid']] = $services['title'];
    //}
    $view_path = \Drupal::config('problem_management.settings')->get('import_alias');
   // \Drupal::url($route_name, $route_parameters = array(), $options = array(), $collect_bubbleable_metadata = FALSE)
   // Url::fromInternalUri('node/' . add)

 //  $path = Url::fromInternalUri(array('node', $group_id ,$view_path));
 //  echo '<pre>';  print_r($path);  exit;
   // $path = URL::fromRoute('entity.node.canonical', array('node' => $group_id));
    
    $prefix = '';
    $prefix .= "<div class = 'problem_settings'> ";
    $prefix .= t("The problems group view will be available at ");
    // $prefix .= Drupal::url($path, $path);
    // $prefix .= Drupal::url($path, $path);
    $prefix .= "<div> ";
    $prefix .= t("Please specify the services of which you would like to display the Problems in this group");
    $prefix .= "</div>";

    if ($options) {
      $form['#prefix'] = $prefix; 
      $form['#suffix'] = "</div>";
      
      $form['services'] = array(
        '#type' => 'checkboxes',
        '#options' => $options,
        '#default_value' => ($default_services?$default_services:array('')),
        '#weight' => -6
        );
      
      $form['downtimes_settings_submit'] = array(
        '#type' => 'submit',
        '#value' => t('Save'),
        '#weight' => 5
        );
    }
      */

	  $form['#prefix'] = "<div class = 'release_settings'> " . t("The Releases group view will be available at ") . "<p>" . // l($path, $path) .
	    "<p><div> " . t("Please specify the services of which you would like to display the Releases in this group") . "</div></p>";
	  $form['#suffix'] = "</div>";
	  $form['services'] = array(
	    '#type' => 'checkboxes',
	    '#options' => $options,
	    '#default_value' => ($default_services?$default_services:array('')),
	    '#weight' => -6
	  );
	  
	 // $terms = taxonomy_get_tree(variable_get('release_vocabulary_id', NULL));
        //  $terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree($vid, $parent, $max_depth, $load_entities); 
         $container = \Drupal::getContainer();
       //  $release_vocabulary_id = \Drupal::config('hzd_release_management.settings')->get('release_type');
         $terms = $container->get('entity.manager')->getStorage('taxonomy_term')->loadTree('release_type');
          // echo '<pre>';  print_r($terms);  exit;
	  foreach($terms as $key => $value) {
	    $release_type[$value->tid] =$value->name;
	  }
	  
	  // Getting the default Release type
	  $default_release_type =  HzdreleasemanagementStorage::get_release_type_current_session();
         //  echo '<pre>';  print_r($default_release_type);  exit; 
         //  echo '<pre>';  print_r($default_release_type);  
	  $form['default_release_type'] = array(
	    '#type' => 'radios',
	    '#title' => t('Default Release Type'),
	    '#options' => $release_type,
	    '#default_value' => $default_release_type ? $default_release_type: KONSONS,
	    '#weight' => -6,
	    '#required' => TRUE,
	  );

	  $form['releases_settings_submit'] = array(
	    '#type' => 'submit',
	    '#value' => t('Save'),
	    '#weight' => 5
	  );

    return $form;
  }

 /**
   * {@inheritDoc}
   */

  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritDoc}
   */

/*
 * submit handler for the release_setting form
 * Inserts services for the group into table "group_releases_view". 
*/

  public function submitForm(array &$form, FormStateInterface $form_state) {
  //   db_query("delete from {group_releases_view} where group_id = %d ", $_SESSION['Group_id']);
   HzdreleasemanagementStorage::delete_group_release_view();
   $default_release_type = $form_state->getValue('default_release_type');
   $selected_services = $form_state->getValue('services');
   // echo '<pre>';  print_r($selected_services); exit; 
   $counter = HzdreleasemanagementStorage::insert_group_release_view($default_release_type, $selected_services);
   $gid = $_SESSION['Group_id'];
   $menu_name = 'menu-' . $gid;
   $path = \Drupal::config('hzd_release_management.settings')->get('import_alias_releases');
   // $path = variable_get('import_alias_releases', 'releases');
   HzdcustomisationStorage::reset_menu_link($counter, t('Releases'), 'releases', $menu_name, $gid);
   drupal_set_message(t('Releases Settings Updated'), 'status');  
  }
}
