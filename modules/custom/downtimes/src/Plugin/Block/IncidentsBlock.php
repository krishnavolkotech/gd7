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
    $maintenance_list = \Drupal::database()->query("select service_id,description, downtime_id, state_id,reason,startdate_planned,enddate_planned from {downtimes} d where d.service_id <> '' and d.scheduled_p = 1 and d.resolved = 0 and d.cancelled = 0 and startdate_planned <= :current_date", array(':current_date' => REQUEST_TIME))->fetchAll();
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
                   
        // Uncomment this to get hover details for incident block
        // $serviceids_list[$ids] = t($serviceids_list[$ids].$this->get_hover_markup($vals->startdate_planned,$vals->description));
       
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
    
    $link = Link::createFromRoute($this->t('Störungen und Blockzeiten'), 'downtimes.new_downtimes_controller_newDowntimes', ['group' => INCEDENT_MANAGEMENT]);
                     
    $markup['incident_list'] = [
      '#items' => $serviceids_list,
      '#theme' => 'item_list',
      '#type' => 'ul',
      '#weight' => 100,
    ];
    $markup['report_link'] = $link->toString();
    $build['incidents_block_number_of_posts']['#markup'] = render($markup['incident_list']).render($markup['report_link']);
   
    return $build;
  }
  
  public function get_hover_markup($start_date_planned,$description) {

    $html = "<ul class='downtime-hover' style='display:none;'>";
    // Getting the below start date. end date and description for hover.
    if (!empty($start_date_planned)) {
      $start_date_planned = DateTimePlus::createFromTimestamp((integer) $start_date_planned)->format('d.m.Y');
      $html .= "<li>$start_date_planned</li>";
    }

    if (!empty($description)) {
      $description = strip_tags($description);
      $html .= "<li>$description</li>";
    }

    $html .= "</ul>";
    
    return $html;
  }

}
