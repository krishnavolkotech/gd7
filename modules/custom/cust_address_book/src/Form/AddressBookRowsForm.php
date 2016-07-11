<?php

/**
 * @file
 * Contains \Drupal\cust_address_book\Form\AddressBookRowsForm
 */

namespace Drupal\cust_address_book\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBuilder;
define('DEFAULT_DISPLAY_ROWS', 20);
class AddressBookRowsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'address_book_rows_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = array(20 => 20, 50 => 50, 100 => 100, 'all' => $this->t('All'));
    $no_rows = \Drupal::request()->get('no_rows') ? \Drupal::request()->get('no_rows') : DEFAULT_DISPLAY_ROWS;
    $form['no_rows'] = array(
      '#type' => 'select',
      '#title' => $this->t('Rows per page'),
      '#default_value' => $no_rows,
      '#options' => $options,
      '#attributes' => array('id' => 'select-no-rows'),
      '#prefix' => "<div class = 'address_rows_select'>",
      '#suffix' => "<div style='clear:both'></div></div>",
    );

    // Hidden form values need to take care of the filter form
    $state = \Drupal::request()->get('state') ? $_REQUEST['state'] : 1;
    $form['state'] = array(
      '#type' => 'hidden',
      '#value' => $state,
    );
    $name_st = \Drupal::request()->get('name_st') ? \Drupal::request()->get('name_st') : null;
    $form['name_st'] = array(
      '#type' => 'hidden',
      '#value' => $name_st,
    );
    $add_search = \Drupal::request()->get('add_search') ? \Drupal::request()->get('add_search') : null;
    $form['add_search'] = array(
      '#type' => 'hidden',
      '#value' => $add_search,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
