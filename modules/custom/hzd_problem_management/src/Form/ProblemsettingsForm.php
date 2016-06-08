<?php

/**
 * @file
 * Contains \Drupal\problem_management\Form\ProblemsettingsForm.
 */

namespace Drupal\problem_management\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hzd_services\HzdservicesStorage;
// use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\system\Entity\Menu;
use Drupal\problem_management\HzdStorage;

use Drupal\hzd_customizations\HzdcustomisationStorage;

class ProblemsettingsForm extends FormBase {
 /**
  * {@inheritDoc}
  */
  public function getFormId() {
    return 'problem_settings_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
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
   * submit handler for the problems settings page
   * selected services for the individual groups are stored in the table "group_problems_view"
   */

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // db_query("delete from {group_problems_view} where group_id = %d ", $_SESSION['Group_id']);
    
    HzdStorage::delete_group_problems_view();
  
    $selected_services = $form['services']['#post']['services'];
    $counter = HzdStorage::insert_group_problems_view($selected_services);
    
    // $tempstore = \Drupal::service('user.private_tempstore')->get('problem_management');
    // $gid = $tempstore->get('Group_id');
    $gid = $_SESSION['Group_id'];
    $menu_name = 'menu-' . $gid;
    $problem_path = \Drupal::config('problem_management.settings')->get('import_alias');
    
    // \Drupal::service('plugin.manager.menu.link')->createInstance($menu_link->getPluginId());
    HzdcustomisationStorage::reset_menu_link($counter, 'Problems', 'problems', $menu_name, $gid);    
    drupal_set_message(t('Problem Settings Updated'), 'status');
  }
}