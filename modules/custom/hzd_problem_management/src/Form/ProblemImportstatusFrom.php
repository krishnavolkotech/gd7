<?php

/**
 * @file
 * Contains \Drupal\problem_management\Form\ProblemImportstatusFrom
 */

namespace Drupal\problem_management\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\problem_management\HzdStorage;
// use Drupal\problem_management\Inactiveuserhelper;
// use Drupal\Core\Datetime\DateFormatter;
use Drupal\hzd_customizations\HzdcustomisationStorage;

/**
 * Configure inactive_user settings for this site.
 */
class ProblemImportstatusFrom extends FormBase {

 //  protected $dateFormatter;
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'import_status_filter_form';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'problem_management.settings',
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = \Drupal::request();
    $default_limit = array(
      20 => 20,
      50 => 50,
      100 => 100,
      'all' => t('All'),
    );
    if (isset($_SESSION['history_limit'])) {
      $default_page_limit = $_SESSION['history_limit']; 
    } else {
      $default_page_limit = $request->request->get('page_limit');
    }
    $form['page_limit'] = array(
      '#type' => 'select',
      '#options' => $default_limit,
      '#weight' => 8,
      '#default_value' => $default_page_limit,
      '#ajax' => array(
        'callback' => '::import_history_search_results',
        'wrapper' => 'import_search_results_wrapper',
        'method' => 'replace',
        'event' => 'change',
        'progress' => array(
          'type' => 'throbber',
         ),
      ),
      '#prefix' => "<div class = 'limit_search_dropdown hzd-form-element'>",
      '#suffix' => '</div>',
    );


    return $form;
  }
  /**
   * {@inheritDoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }
  
  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  public function import_history_search_results(array &$form, FormStateInterface $form_state) {

    $form_build_id = $_POST['form_build_id'];
    $request = \Drupal::request();
    $values['values']['limit'] = ($request->request->get('page_limit')?$request->request->get('page_limit'):$_POST['page_limit']);
    $limit = $form_state->getValue('page_limit')?$form_state->getValue('page_limit'): $values['values']['limit'];
    /**
    error_log("limit " . $limit);
    if($form_state['values']['page_limit']) {
      $limit = $form_state['values']['page_limit'];
    }
    */
    $_SESSION['history_limit'] = $limit;
    $result['page']['content']['import_search_results_wrapper']['#prefix'] = "<div id = 'import_search_results_wrapper' > "; 
    $result['page']['content']['import_search_results_table'] = HzdStorage::import_history_display_table($limit);
    // $result['page']['content']['#suffix'] = "</div>";
    
    return $result;
  }
}
