<?php

namespace Drupal\downtimes\Form;

use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_services\HzdservicesStorage;

/**
 *
 */
class DowntimesettingsForm extends FormBase {

  /**
   * {@inheritDoc}.
   */
  public function getFormId() {
    return 'downtime_settings_form';
  }

  /**
   * {@inheritDoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
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
    /**
      $query = \Drupal::database()->query("select service_id from {group_downtimes_view} where group_id = %d", $_SESSION['Group_id']);

      while ($services = db_fetch_array($query)) {
      $default_services[$services['service_id']] = $services['service_id'];
      }
     */
    $default_services[$services['service_id']] = HzdservicesStorage::get_downtimes_default_services();
    $type = 'downtimes';

    //  $options = get_related_services($type);

    $form['#prefix'] = "<div class = 'downtimes_settings'>" . t('Please specify the services of which you would like to display the downtimes in this group.');
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
    return $form;
  }

  /*
   * submit handler for the downtimes_setting form
   * Inserts the selected services according to group in the table "group_downtimes_view"
   */

  function downtimes_setting_submit($form, &$form_state) {
    \Drupal::database()->query("delete from {group_downtimes_view} where group_id = %d ", $_SESSION['Group_id']);
    $selected_services = $form['services']['#post']['services'];
    $sql = 'insert into {group_downtimes_view} (group_id, service_id) values (%d, %d)';
    $counter = 0;
    if ($selected_services) {
      foreach ($selected_services as $service) {
        $counter++;
        \Drupal::database()->query($sql, $_SESSION['Group_id'], $service);
      }
    }
    $gid = $_SESSION['Group_id'];
    $menu_name = 'menu-' . $gid;
    reset_menu_link($counter, 'Downtimes', 'downtimes', $menu_name, $gid);
    \Drupal::messenger()->addMessage(t('Downtime Settings Updated'));
  }

}
