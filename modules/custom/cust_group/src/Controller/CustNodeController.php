<?php

namespace Drupal\cust_group\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeTypeInterface;

/**
 * Returns responses for Node routes.
 */
class CustNodeController extends ControllerBase {

  /**
   * Provides the node submission form.
   *
   * @param \Drupal\node\NodeTypeInterface $node_type
   *   The node type entity for the node.
   *
   * @return array
   *   A node submission form.
   */
  public function add($group_id, NodeTypeInterface $node_type) {
    $maintainance_id = \Drupal::config('downtimes.settings')->get('maintenance_group_id');
    $quickinfo_id = \Drupal::config('quickinfo.settings')->get('quickinfo_group_id');

    $node = $this->entityManager()->getStorage('node')->create(array(
      'type' => $node_type->id(),
    ));

    $form = $this->entityFormBuilder()->getForm($node);

    return $form;
  }

}
