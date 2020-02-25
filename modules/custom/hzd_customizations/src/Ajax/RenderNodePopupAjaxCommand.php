<?php

namespace Drupal\hzd_customizations\Ajax;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

/**
 * Class RenderNodePopupAjaxCommand.
 */
class RenderNodePopupAjaxCommand extends ControllerBase {

  public function ajaxNodeDetails($node_id) {
      $selector = 'div#' . $node_id . '';
    if ($node_id) {
      $response = new AjaxResponse();
      $entity = Node::load($node_id);
      $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
      $content = [$view_builder->view($entity, 'popup', 'de'),];
      $response->addCommand(new HtmlCommand($selector, $content));
      return $response;
    }

  }

  public function ajaxDeployedDetails($node_id) {
      $selector = 'div#' . $node_id . '';
      if ($node_id) {
          $response = new AjaxResponse();
          $deployed_releases = views_embed_view('deployed_popup_content', 'page_1', $node_id);
          $variables = drupal_render($deployed_releases);
          $response->addCommand(new HtmlCommand($selector, $variables));
          return $response;
      }
  }
}
