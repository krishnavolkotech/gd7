<?php

namespace Drupal\downtimes\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Datetime\DateTimePlus;
use Drupal\Core\Link;
use Drupal\Component\Utility\Unicode;

/**
 * Provides a 'MaintenanceBlock' block.
 *
 * @Block(
 *  id = "maintenance_block",
 *  admin_label = @Translation("Maintenance"),
 * )
 */
class MaintenanceBlock extends BlockBase {

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
    $maintenance_list = \Drupal::database()->query("select service_id,description, downtime_id, state_id,reason,startdate_planned,enddate_planned,scheduled_p from {downtimes} d where d.service_id <> '' and d.scheduled_p = 1 and d.resolved = 0 and d.cancelled = 0 and startdate_planned > :current_date ", array(':current_date' => REQUEST_TIME))->fetchAll();
    $result = $serviceids_list = array();
    foreach ($maintenance_list as $vals) {
      //$item = '<div '
      $serviceid = explode(',', $vals->service_id);
      $stateids = explode(',', $vals->state_id);
      foreach ($serviceid as $ids) {
        $service_name = \Drupal::database()->query('SELECT title FROM {node_field_data} WHERE nid=:sid', array(':sid' => $ids))->fetchField();
        foreach ($stateids as $sids) {
          $state_name = \Drupal::database()->query('SELECT abbr FROM {states} WHERE id=:sid', array(':sid' => $sids))->fetchField();
          if (!empty($serviceids_list[$ids])) {
            $serviceids_list[$ids] = t($serviceids_list[$ids] . "<br><span class='downtime-hover-wrapper'><a class='state-link' href='node/$vals->downtime_id'><span class='state-item'>[$state_name] " . date("d.m.Y H:i", $vals->startdate_planned) . t("Uhr") . $vals->downtime_id . '</span></a>');

            $serviceids_list[$ids] = t($serviceids_list[$ids] . $this->get_hover_markup($vals->startdate_planned, $vals->enddate_planned, $vals->description, $vals->scheduled_p));
            $serviceids_list[$ids] = t($serviceids_list[$ids] . '</span>');
          }
          else {
            if (empty($state_name)) {
              continue;
            }
            $serviceids_list[$ids] = "<span class='service-item'>$service_name</span><br><span class='downtime-hover-wrapper'><a class='state-link' href='node/$vals->downtime_id'><span class='state-item'>[$state_name] " . date("d.m.Y H:i", $vals->startdate_planned) . t("Uhr") . $vals->downtime_id . '</span></a>';

            $serviceids_list[$ids] = t($serviceids_list[$ids] . $this->get_hover_markup($vals->startdate_planned, $vals->enddate_planned, $vals->description, $vals->scheduled_p));
            $serviceids_list[$ids] = t($serviceids_list[$ids] . '</span>');
          }
        }
      }
    }
    /* $item_listnew = array();
      foreach ($serviceids_list as $value) {
      $item_listnew += $value;
      } */

    $link_options = array(
      'attributes' => array(
        'class' => array(
          'front-page-link',
        ),
      ),
    );
    
    $all_link = Link::createFromRoute($this->t('Störungen und Blockzeiten'), 'downtimes.new_downtimes_controller_newDowntimes', ['group' => INCEDENT_MANAGEMENT], $link_options);

    $markup['maintenance_list'] = [
      '#items' => $serviceids_list,
      '#theme' => 'item_list',
      '#type' => 'ul',
      '#weight' => 100,
    ];

    $markup['all_link'] = $all_link->toString();
    $build['maintenance_block_number_of_posts']['#markup'] = render($markup['maintenance_list']) . render($markup['all_link']);

    $build['#cache'] = array(
      'max-age' => 0,
    );

    //$build['maintenance_block_number_of_posts']['#markup'] = "ASDFDSF";
    return $build;
  }

  /**
   * Return the hover markup to be shown on front page blocks for downtimes.
   * @param unix time $start_date_planned
   * @param unix time $end_date_planned
   * @param string $description
   * @param boolean $scheduled_p
   * @return markup
   */
  public static function get_hover_markup($start_date_planned, $end_date_planned, $description, $scheduled_p) {

    $html = "<ul class='downtime-hover' style='display:none;'>";
    // Getting the below start date. end date and description for hover.
    if (!empty($start_date_planned)) {
      $start_date_planned = DateTimePlus::createFromTimestamp((integer) $start_date_planned)->format('d.m.Y');
      $html .= "<li>" . t('Start:') . $start_date_planned . "</li>";
    }

    // If end date is not empty and if it is maintenance(ie., scheduled_p =1), then only display end date in hover.
    if (!empty($end_date_planned) && $scheduled_p) {
      $end_date_planned = DateTimePlus::createFromTimestamp((integer) $end_date_planned)->format('d.m.Y');
      $html .= "<li>" . t('End:') . $end_date_planned . "</li>";
    }

    if (!empty($description)) {
      $description = strip_tags($description);
      $description = Unicode::Truncate($description, 100, TRUE, TRUE);
      $html .= "<li>$description</li>";
    }

    $html .= "</ul>";

    return $html;
  }

}
