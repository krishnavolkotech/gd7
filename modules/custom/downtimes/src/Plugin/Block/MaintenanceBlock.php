<?php

namespace Drupal\downtimes\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

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
    $maintenance_list = \Drupal::database()->query("select service_id, downtime_id, state_id,reason,startdate_planned,enddate_planned from {downtimes} d where d.service_id <> '' and d.scheduled_p = 0 and d.resolved = 0 and d.cancelled = 0 ", array())->fetchAll();
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
            $serviceids_list[$ids] = t($serviceids_list[$ids] . "<br><span class='state-item'>[$state_name] " . date("d.m.Y H:i", $vals->startdate_planned) . t("Uhr") . $vals->downtime_id. '</span>');
          }
          else {
            if (empty($state_name)) {
              continue;
            }
            $serviceids_list[$ids] = t("<span class='service-item'>$service_name</span><br><span class='state-item'>[$state_name] " . date("d.m.Y H:i", $vals->startdate_planned) . t("Uhr") . $vals->downtime_id .'</span>');
          }
        }
      }
    }
    /*$item_listnew = array();
    foreach ($serviceids_list as $value) {
      $item_listnew += $value;
    }*/
    $markup = [
      '#items' => $serviceids_list,
      '#theme' => 'item_list',
      '#type' => 'ul',
      '#weight' => 100,
    ];
    $build['maintenance_block_number_of_posts']['#markup'] = render($markup);
    //$build['maintenance_block_number_of_posts']['#markup'] = "ASDFDSF";
    return $build;
  }

}
