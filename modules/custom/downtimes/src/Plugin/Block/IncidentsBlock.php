<?php

namespace Drupal\downtimes\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Link;

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
    $maintenance_list = \Drupal::database()->query("SELECT service_id,description, downtime_id, state_id,reason,startdate_planned,enddate_planned,scheduled_p FROM downtimes d WHERE d.service_id <> '' AND d.cancelled = 0  AND d.resolved = 0 AND (d.scheduled_p = 0 OR (d.scheduled_p = 1 AND startdate_planned <= :current_date))", array(':current_date' => REQUEST_TIME))->fetchAll();
    $result = $serviceids_list = array();

    // Get the service id's list and get respective details from service id.
    foreach ($maintenance_list as $key => $vals) {
      
      $serviceid = explode(',', $vals->service_id);
      $stateids = explode(',', $vals->state_id);

      foreach ($serviceid as $ids) {
        // Loops for all services
        $service_name = \Drupal::database()->query('SELECT title FROM {node_field_data} WHERE nid=:sid', array(':sid' => $ids))->fetchField();
      
       foreach ($stateids as $sids) {
          // Loops for all states
          $state_name = \Drupal::database()->query('SELECT abbr FROM {states} WHERE id=:sid', array(':sid' => $sids))->fetchField();

          $states_array[$state_name] = $state_name;
          if (!empty($serviceids_list[$ids])) {
            $serviceids_list[$ids] = t($serviceids_list[$ids] . "<span class='state-item'>[$state_name] " . '</span>');
           
            $hover_markup  = MaintenanceBlock::get_hover_markup($vals->startdate_planned,$vals->enddate_planned,$vals->description,$vals->scheduled_p);
            $serviceids_list[$ids] = t($serviceids_list[$ids].$hover_markup);              
          }
          else {
            if (empty($state_name)) {
              continue;
            }
            $serviceids_list[$ids] = t("<span class='service-item'>$service_name</span><br><span class='state-item'>[$state_name] " . '</span>');
            $hover_markup  = MaintenanceBlock::get_hover_markup($vals->startdate_planned,$vals->enddate_planned,$vals->description,$vals->scheduled_p);
            $serviceids_list[$ids] = t($serviceids_list[$ids].$hover_markup);
          }
        }
      }
    }
    /* $item_listnew = array();
      foreach ($serviceids_list as $value) {
      $item_listnew[] = t(implode('', $value));
      } */
    
    $all_link = Link::createFromRoute($this->t('Störungen und Blockzeiten'), 'downtimes.new_downtimes_controller_newDowntimes', ['group' => INCEDENT_MANAGEMENT]);
    $report_link = Link::createFromRoute($this->t('Report Downtime'), 'downtimes.create_downtimes', ['group' => INCEDENT_MANAGEMENT]);
                         
    $markup['incident_list'] = [
      '#items' => $serviceids_list,
      '#theme' => 'item_list',
      '#type' => 'ul',
      '#weight' => 100,
    ];
        
    $markup['all_link'] = $all_link->toString();
    $markup['report_link'] = $report_link->toString();
    $build['incidents_block_number_of_posts']['#markup'] = render($markup['incident_list']).render($markup['all_link']).render($markup['report_link']);
   
    return $build;
  }
  
}
