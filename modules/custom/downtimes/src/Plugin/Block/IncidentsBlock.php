<?php

namespace Drupal\downtimes\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'IncidentsBlock' block.
 *
 * @Block(
 *  id = "incidents_block",
 *  admin_label = @Translation("Incident Block"),
 * )
 */
class IncidentsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'number_of_posts' => $this->t(''),
        ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['number_of_posts'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of posts'),
      '#description' => $this->t(''),
      '#default_value' => $this->configuration['number_of_posts'],
      '#maxlength' => 64,
      '#size' => 64,
      '#weight' => '0',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['number_of_posts'] = $form_state->getValue('number_of_posts');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $maintenance_list = \Drupal::database()->query("select service_id, downtime_id, state_id,reason,startdate_planned,enddate_planned from {downtimes} d where d.service_id <> '' and d.scheduled_p = 1 and d.resolved = 0 and d.cancelled = 0 ", array())->fetchAll();
    $result = $serviceids_list = array();

    // Get the service id's list and get respective details from service id.
    foreach ($maintenance_list as $key => $vals) {
      //$item = '<div '
      $serviceid = explode(',', $vals->service_id);
      $stateids = explode(',', $vals->state_id);
      /* dsm($key);
        dsm($serviceid); */
      foreach ($serviceid as $ids) {
        // Loops for all services
        $service_name = \Drupal::database()->query('SELECT title FROM {node_field_data} WHERE nid=:sid', array(':sid' => $ids))->fetchField();

        $statesArray = \Drupal::database()->select('states', 's')->distinct()
            ->fields('s', ['abbr'])
            ->condition('s.id', $stateids, 'IN')            
            ->execute()
            ->fetchCol();
        
        $statesArray = implode('][ ',$statesArray);
        $serviceids_list[$ids] = t("<span class='service-item'>$service_name</span><span class='state-item'>[$statesArray]</span>");
       
       /* foreach ($stateids as $sids) {
          // Loops for all states
          $state_name = \Drupal::database()->query('SELECT abbr FROM {states} WHERE id=:sid', array(':sid' => $sids))->fetchField();

          $states_array[$state_name] = $state_name;
          if (!empty($serviceids_list[$ids])) {
            $serviceids_list[$ids] = t($serviceids_list[$ids] . "<br><span class='state-item'>[$state_name]</span>");
            // $serviceids_list[$ids] = t($serviceids_list[$ids] . "<br><span class='state-item'>[$state_name] " . date("d.m.Y H:i", $vals->startdate_planned) . t("Uhr") . '</span>');              
          }
          else {
            if (empty($state_name)) {
              continue;
            }
            $serviceids_list[$ids] = t("<span class='service-item'>$service_name</span><br><span class='state-item'>[$state_name]</span>");
            //$serviceids_list[$ids] = t("<span class='service-item'>$service_name</span><br><span class='state-item'>[$state_name] " . date("d.m.Y H:i", $vals->startdate_planned) . t("Uhr") . '</span>');
          }
        }*/
      }
    }
    /* $item_listnew = array();
      foreach ($serviceids_list as $value) {
      $item_listnew[] = t(implode('', $value));
      } */
    $markup = [
      '#items' => $serviceids_list,
      '#theme' => 'item_list',
      '#type' => 'ul',
      '#weight' => 100,
    ];
    $build['incidents_block_number_of_posts']['#markup'] = render($markup);

    return $build;
  }

}
