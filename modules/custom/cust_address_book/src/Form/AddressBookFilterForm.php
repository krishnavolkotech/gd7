<?php

/**
 * @file
 * Contains \Drupal\cust_address_book\Form\AddressBookFilterForm
 */

namespace Drupal\cust_address_book\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBuilder;
use Drupal\cust_address_book\AddressBookHelper;
define('ADDRESS_BOOK_SEARCH_DEFAULT', 'Search Name or Email');
class AddressBookFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'address_book_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $limit = 20) {
    // Alphabets
    $sql = "SELECT DIStINCT UCASE(LEFT(cp.lastname,1)) as alphabet FROM {users} u, {cust_profile} cp WHERE u.uid = cp.uid ORDER BY alphabet";
    $path = 'addressbook_filter';
    if($_SESSION['Group_name']) {
      $path = 'node/'. $_SESSION['Group_id'] .'/'. $path;
    }

    $alphabets = AddressBookHelper::alphabetic_list_users($path, $sql);
    $form['alphabets'] = array(
      '#markup' => $alphabets,
      '#prefix' => '<div id = "address_book_alphabets">',
      '#suffix' => '</div>',
    );

    $states = get_all_user_state(1);
    $state = \Drupal::request()->get('state') ? \Drupal::request()->get('state') : 1;
    $form['state'] = array(
      '#type' => 'select', 
      '#title' => '',
      '#options' => $states,
      '#default_value' => $state,
      /*'#ahah' => array(
        'path' => $path,
        'wrapper' => 'members_list',
        'method' => 'replace',
        'event' => 'change',
        'progress' => array('type' => 'throbber'),
		  ),*/
    );

    // Search name or Email
    $search_default = t(ADDRESS_BOOK_SEARCH_DEFAULT);
    //drupal_add_js(array('address_book_search_default_value' => $search_default), 'setting');
    $form['add_search'] = array(
      '#type' => 'textfield',
      '#title' => '',
      '#size' => 27,
      '#value' => $search_default,
      '#attributes' => array('onfocus' => 'address_book_search_focus(this)', 'onblur' => 'address_book_search_blur(this)'),
      '#prefix' => "<div class = 'address_book_string_search'>",
      '#suffix' => '</div>',
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#attributes' => array('class' => 'address_search_submit'),
      '#prefix' => '<div class = "search_string_submit">',
      '#suffix' => '</div><div style="clear:both;"></div>',
    );
    $form['#action'] = '/'. $path;
    $form['no_rows'] = array(
      '#type' => 'hidden',
      '#value' => $limit
    );
    $form['name_st'] = array(
      '#type' => 'hidden',
      '#value' => null,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }
}
