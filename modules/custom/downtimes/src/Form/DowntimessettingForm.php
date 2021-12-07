<?php

namespace Drupal\downtimes\Form;

// use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_services\HzdservicesStorage;
use Drupal\downtimes\HzdDowntimeStorage;
use Drupal\menu_link_content\Entity\MenuLinkContent;

// use Drupal\hzd_customizations\HzdDowntimeStorage;

/**
 *  downtime settings form 
 */
class DowntimessettingForm extends FormBase {

  /**
   * {@inheritDoc}.
   */
  public function getFormId() {
    return 'downtimes_setting_form';
  }

  /**
   * {@inheritDoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /**
      //Getting the default Services
      $breadcrumb = array();
      $breadcrumb[] = l(t('Home'), NULL);
      if (isset($_SESSION['Group_name'])) {
      $breadcrumb[] = l(t($_SESSION['Group_name']), 'node/' . $_SESSION['Group_id']);
      }
      $breadcrumb[] = drupal_get_title();
      drupal_set_breadcrumb($breadcrumb);
     */
    $type = 'downtimes';
    $group = \Drupal::routeMatch()->getParameter('group');
    $group_id = $group->id();
    $group_downtimes_view_service_query = \Drupal::database()->select('group_downtimes_view', 'gdv');
    $group_downtimes_view_service_query->Fields('gdv', array('service_id'));
    $group_downtimes_view_service_query->condition('group_id', $group_id, '=');
    $group_downtimes_view_service = $group_downtimes_view_service_query->execute()->fetchAll();

    foreach ($group_downtimes_view_service as $service) {
      $default_services[$service->service_id] = $service->service_id;
    }

    $options = HzdservicesStorage::get_related_services($type);

    $form['#prefix'] = "<div class = 'downtimes_settings'>" . $this->t('Please specify the services of which you would like to display the downtimes in this group.');
    $form['#suffix'] = "</div>";
    $form['services'] = array(
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => (isset($default_services) ? $default_services : array('')),
      '#weight' => -6
    );

    $form['downtimes_settings_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
      '#weight' => 5
    );
    return $form;
  }

  /**
   * {@inheritDoc}.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected_services = $form_state->getvalue('services');
    HzdDowntimeStorage::delete_group_downtimes_view();
    // $selected_services = $form['services']['#post']['services'];

    $counter = HzdDowntimeStorage::insert_group_downtimes_view($selected_services);
    $group = \Drupal::routeMatch()->getParameter('group');
    $group_id = $group->id();
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
      if ($menu->getUrlObject()->isRouted() && $menu->getUrlObject()->getRouteName() == 'downtimes.new_downtimes_controller_engDowntimes') {
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
                'title' => $this->t('Störungen und Blockzeiten'),
                'link' => ['uri' => 'internal:/group/' . $group->id() . '/downtimes'],
                'menu_name' => $menu_name,
                'expanded' => TRUE,
                'enabled' => 1,
      ]);
      $menu_link->save();
    }
    \Drupal::service("router.builder")->rebuild();
    \Drupal::messenger()->addMessage($this->t('Downtime Settings Updated'));
  }

}
