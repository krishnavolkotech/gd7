<?php

namespace Drupal\hzd_release_management\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_services\HzdservicesStorage;
use Drupal\hzd_release_management\HzdreleasemanagementStorage;
use Drupal\Core\Url;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * If(!defined('KONSONS'))
 * define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));
 * if(!defined('RELEASE_MANAGEMENT'))
 * define('RELEASE_MANAGEMENT', 339);.
 * TODO
 * $_SESSION['Group_id'] = 339;.
 */
class ReleasesettingsForm extends FormBase {

  /**
   * {@inheritDoc}.
   */
  public function getFormId() {
    return 'release_settings_form';
  }

  /**
   * {@inheritDoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $group = \Drupal::routeMatch()->getParameter('group');
    if (is_object($group)) {
      $group_id = $group->id();
    } else {
      $group_id = $group;
    }
    
    $default_services = array();
    global $base_url;
    $breadcrumb = array();
    // $breadcrumb[] = l(t('Home'), NULL);.
    if (isset($_SESSION['Group_name'])) {
      // $breadcrumb[] = l(t($_SESSION['Group_name']), 'node/'. $_SESSION['Group_id']);.
    }
    // $breadcrumb[] = drupal_get_title() ;
    // drupal_set_breadcrumb($breadcrumb);
    // Getting the default Services
    //  $query =  \Drupal::database()->query("select service_id from {group_releases_view} where group_id = %d", $_SESSION['Group_id']);.
    $services = HzdreleasemanagementStorage::get_default_release_services_current_session();
    foreach ($services as $service) {
      $default_services[$service->service_id] = $service->service_id;
    }
    $type = 'releases';
    $options = HzdservicesStorage::get_related_services($type);
    $view_path = \Drupal::config('hzd_release_management.settings')->get('import_alias_releases');

    // l($path, $path) .
    $form['#prefix'] = "<div class = 'release_settings'> " . " <div> " . t("Please specify the services of which you would like to display the Releases in this group.") . "</div></p>";
    $form['#suffix'] = "</div>";
    $form['services'] = array(
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => ($default_services ? $default_services : array('')),
      '#weight' => -6,
    );

    // $terms = taxonomy_get_tree(variable_get('release_vocabulary_id', NULL));
    //  $terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree($vid, $parent, $max_depth, $load_entities);.
    $container = \Drupal::getContainer();
    // $release_vocabulary_id = \Drupal::config('hzd_release_management.settings')->get('release_type');.
    $terms = $container->get('entity_type.manager')->getStorage('taxonomy_term')->loadTree('release_type');
    // Echo '<pre>';  print_r($terms);  exit;.
    foreach ($terms as $key => $value) {
      $release_type[$value->tid] = $value->name;
    }
    natcasesort($release_type);

    // Getting the default Release type.
    $default_release_type = HzdreleasemanagementStorage::get_release_type_current_session();
    // Echo '<pre>';  print_r($default_release_type);  exit;
    //  echo '<pre>';  print_r($default_release_type);
    $form['default_release_type'] = array(
      '#type' => 'radios',
      '#title' => t('Default Release Type'),
      '#options' => $release_type,
      '#default_value' => $default_release_type ? $default_release_type : KONSONS,
      '#weight' => -6,
      '#required' => TRUE,
    );

    $form['releases_settings_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#weight' => 5,
    );

    return $form;
  }

  /**
   * {@inheritDoc}.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    
  }

  /**
   * {@inheritDoc}.
   */

  /**
   * Submit handler for the release_setting form
   * Inserts services for the group into table "group_releases_view".
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $group = \Drupal::routeMatch()->getParameter('group');
    if (is_object($group)) {
      $group_id = $group->id();
    } else {
      $group_id = $group;
    }
    HzdreleasemanagementStorage::delete_group_release_view();
    $default_release_type = $form_state->getValue('default_release_type');
    $selected_services = $form_state->getValue('services');
    $counter = HzdreleasemanagementStorage::insert_group_release_view($default_release_type, $selected_services);

    $menu_name = 'menu-' . $group->get('field_old_reference')->value;
//    $problem_path = \Drupal::config('problem_management.settings')->get('import_alias');
//    // \Drupal::service('plugin.manager.menu.link')->createInstance($menu_link->getPluginId());
//    HzdcustomisationStorage::reset_menu_link($counter, 'Problems', 'problems', $menu_name, $group->id());
    $menuItemIds = \Drupal::entityQuery('menu_link_content')
            ->condition('menu_name', $menu_name)
            ->execute();
    $menuItems = MenuLinkContent::loadMultiple($menuItemIds);
    $noLinkAvailable = true;
    foreach ($menuItems as $menu) {
      if ($menu->getUrlObject()->isRouted() && $menu->getUrlObject()->getRouteName() == 'hzd_release_management.released') {
        $noLinkAvailable = false;
        if ($counter == 0) {
          $menu->set('enabled', 0);
        } else {
          $menu->set('enabled', 1);
        }
        $menu->save();
        break;
      }
    }
    if ($noLinkAvailable && $counter != 0) {
      $menu_link = MenuLinkContent::create([
                'title' => $this->t('Releases'),
                'link' => ['uri' => 'internal:/group/' . $group->id() . '/releases'],
                'menu_name' => $menu_name,
                'expanded' => TRUE,
                'enabled' => 1,
      ]);
      $menu_link->save();
    }
    \Drupal::service("router.builder")->rebuild();
    \Drupal::messenger()->addMessage(t('Releases Settings Updated'), 'status');
  }

}
