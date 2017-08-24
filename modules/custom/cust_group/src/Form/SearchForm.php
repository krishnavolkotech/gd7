<?php

namespace Drupal\cust_group\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SearchForm.
 *
 * @package Drupal\cust_group\Form
 */
class SearchForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'search_site';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $query = \Drupal::request()->query;
    $form['search'] = ['#type'=>'html_tag','#tag' => 'span','#value'=>'','#attributes'=>['class' => ['search-icon','icon glyphicon glyphicon-search'], 'onclick'=>'jQuery(".search-expanded").toggleClass("hide");return false;']];
    $form['expanded'] = ['#type'=>'container','#attributes'=>['class'=>['search-expanded','hide']]];
    $form['expanded']['close'] = ['#type'=>'html_tag','#tag'=>'a','#attributes'=>['class'=>['close'],'onclick'=>'jQuery(".search-expanded").toggleClass("hide");return false;'],'#value'=>'Close'];
    $form['expanded']['fulltext'] = [
      '#type'=>'textfield',
// as a requirement search input is emptied after form submit
//      '#default_value'=>$query->has('fulltext')?$query->get('fulltext'):null,
      '#size'=>15,
      '#placeholder'=>$this->t('Search site'),
    ];
    $form['#action'] = '/suche';
    $form['#method'] = 'GET';
    
    $form['expanded']['submit'] = [
      '#type' => 'submit',
      '#name' => '',
      '#value' => t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    print_r($form_state['values']); die();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      drupal_set_message($key . ': ' . $value);
    }
  }

}
