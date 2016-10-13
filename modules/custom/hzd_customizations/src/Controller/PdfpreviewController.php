<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\hzd_customizations\Controller;

use Drupal\Component\Utility\Html;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Drupal\Core\Controller\ControllerBase;

/**
 * Description of Customnodepageedit.
 *
 * @author sureshk
 */
class Customnodepageedit extends ControllerBase {

  /**
   * Menu callback; presents the node editing form, or redirects to delete confirmation.
   */
  public function custom_node_page_edit($node) {
    $output = array();

    $request = \Drupal::request();
    if ($route = $request->attributes->get(RouteObjectInterface::ROUTE_OBJECT)) {
      $route->setDefault('_title', Html::escape($node->title));
    }

    if ($node->type == 'quickinfo' && $node->status == 1) {
      $output['#markup'] = $this->t("This content was already published. You cannot edit it anymore.");
    }
    else {
      dpm($node);
      $output['content']['problems_filter_element'] = \Drupal::formBuilder()->getForm($node);
      return $output;
      // Return drupal_get_form($node->type . '_node_form', $node);.
    }
  }

}
