<?php

namespace Drupal\hzd_release_management\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\node\Entity\Node;

/**
 * If(!defined('KONSONS'))
 * define('KONSONS', \Drupal::config('hzd_release_management.settings')->get('konsens_service_term_id'));
 * if(!defined('RELEASE_MANAGEMENT'))
 * define('RELEASE_MANAGEMENT', 339);.
 * TODO
 * $_SESSION['Group_id'] = 339;.
 */
class DeployedReleasesOverviewiew extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'deployed_released_view_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_state->setMethod('get');
    $default_type = \Drupal::request()->get('release_type') ?
            \Drupal::request()->get('release_type') : KONSONS;

    $container = \Drupal::getContainer();
    $terms = $container->get('entity.manager')
                    ->getStorage('taxonomy_term')->loadTree('release_type');
    foreach ($terms as $key => $value) {
      $release_type_list[$value->tid] = $value->name;
    }
    natcasesort($release_type_list);
    # $form['#title'] = $this->t('@type Releases (@overview)', ['@type' => 'Deployed','@overview'=>'Overview']);
    $form['#title'] = $this->t('Deployed Releases (Overview)');
    $form['release_type'] = array(
        '#type' => 'select',
        '#default_value' => $default_type,
        '#options' => $release_type_list,
        "#prefix" => "<div class = 'release_type_dropdown hzd-form-element input-group'>",
        '#suffix' => '</div><div style="clear:both"></div>',
        '#attributes' => array(
          'onchange' => 'this.form.submit()',
      ),
    );
  
    $form['#cache']['tags'] = ['hzd_release_management:deployed_releases'];
    $form['#attached']['library'] = array('hzd_release_management/hzd_release_management_sticky_header');
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
  }

}
