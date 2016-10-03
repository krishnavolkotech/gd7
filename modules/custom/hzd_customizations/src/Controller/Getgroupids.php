<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\hzd_customizations\Controller;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Description of Getgroupids
 *
 * @author sureshk
 */
class Getgroupids  extends ControllerBase {
    /**
     * Menu callback; presents the node editing form, or redirects to delete confirmation.
     */
    function get_group_ids() {
     $query = \Drupal::entityQuery('group')->sort('id', 'ASC')->execute();
      // $query = [31=>31,32=>32];  
      $header = array(t('Group Name'), t('Group Id'));
      foreach ($query as $row) {
        $groups = \Drupal\group\Entity\Group::load($row);
        $elements = array(
          'group_name' => $groups->label(), 
          'group_id' => $groups->id(), 
            );
        $rows[] = $elements;
      }
      if (!isset($elements)) {
        $output[] = t('No Data to be displayed') . "<br/>";
      }
      else {
        $output[] = array(
          '#theme' => 'table',
          '#header' => $header,
          '#rows' => $rows,
        );
      }
      return $output;
    }
}
