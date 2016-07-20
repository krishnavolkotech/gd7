<?php

namespace Drupal\downtimes\Form;

// use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hzd_services\HzdservicesStorage;

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
	  $options = HzdservicesStorage::get_related_services($type);
	  $form['#prefix'] = "<div class = 'downtimes_settings'>" . $this->t('Please specify the services of which you would like to display the downtimes in this group.');
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
    $gid = $_SESSION['Group_id'];
    $menu_name = 'menu-' . $gid;
    reset_menu_link($counter, 'Downtimes', 'downtimes', $menu_name, $gid);
    drupal_set_message($this->t('Downtime Settings Updated'));
  }
}
