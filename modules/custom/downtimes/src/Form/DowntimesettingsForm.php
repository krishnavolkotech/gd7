<?php

/**
 * @file
 * Contains \Drupal\downtimes\Form\DowntimeSettingsForm.
 */

namespace Drupal\downtimes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hzd_services\HzdservicesStorage;
use Drupal\system\Entity\Menu;
use Drupal\problem_management\HzdStorage;

use Drupal\hzd_customizations\HzdcustomisationStorage;

class DowntimesettingsForm extends FormBase {
 /**
  * {@inheritDoc}
  */
  public function getFormId() {
    return 'downtime_settings_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
    global $base_url;
    $breadcrumb = array();
   // $breadcrumb[] = \Drupal::l(t('Home'), Url::setUnrouted());
    $group_name =  \Drupal::service('user.private_tempstore')->get()->get('Group_name');
    $group_id = \Drupal::service('user.private_tempstore')->get()->get('Group_id');

    if ($group_name) {
      $breadcrumb[] = l(t($group_name), 'node/' . $group_id);
    }

    $request = \Drupal::request();
    if ($route = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
      $breadcrumb[] = \Drupal::service('title_resolver')->getTitle($request, $route);
    }
    
    // Drupal::service('breadcrumb')->set($breadcrumb);
    $breadcrumb_manager = \Drupal::service('breadcrumb');
    $current_route_match = \Drupal::service('current_route_match');
    $breadcrumb = $breadcrumb_manager->build($current_route_match);

    $default_services[$services['service_id']] = HzdservicesStorage::get_downtimes_default_services();

    $type = 'downtimes';
    $options = HzdservicesStorage::get_related_services($type);
    $view_path = \Drupal::config('problem_management.settings')->get('import_alias');
    
    $prefix = '';
    $prefix .= "<div class = 'downtimes_settings'> ";
    $prefix .= t("The downtimes group view will be available at ");
    $prefix .= "<div> ";
    $prefix .= t("Please specify the services of which you would like to display the downtimes in this group");
    $prefix .= "</div>";

    if ($options) {
      $form['#prefix'] = $prefix; 
      $form['#suffix'] = "</div>";
      
      $form['services'] = array(
        '#type' => 'checkboxes',
        '#options' => $options,
        '#default_value' => ($default_services ? $default_services : array('')),
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
    
    /*HzdDowntimeStorage::delete_group_downtimes_view();
  
    $selected_services = $form['services']['#post']['services'];
    $counter = HzdDowntimeStorage::insert_group_downtimes_view($selected_services);
    
    $tempstore = \Drupal::service('user.private_tempstore')->get('downtimes');
    $gid = $tempstore->get('Group_id');

    $menu_name = 'menu-' . $gid;
    //$problem_path = \Drupal::config('problem_management.settings')->get('import_alias');
    
    // \Drupal::service('plugin.manager.menu.link')->createInstance($menu_link->getPluginId());
    HzdcustomisationStorage::reset_menu_link($counter, 'Downtimes', 'downtimes', $menu_name, $gid);  */  
    drupal_set_message(t('Downtimes Settings Updated'), 'status');
  }
}
