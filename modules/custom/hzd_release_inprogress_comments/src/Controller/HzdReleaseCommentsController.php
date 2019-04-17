<?php

namespace Drupal\hzd_release_inprogress_comments\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class HzdReleaseCommentsController.
 */
class HzdReleaseCommentsController extends ControllerBase {

  /**
   * Add_release_comment.
   *
   * @return string
   *   Return Hello string.
   */
  public function add_release_comment($group) {
    $type = node_type_load("release_comments");
    $samplenode = $this->entityManager()->getStorage('node')->create(array(
      'type' => $type->id(),
    ));
    $node_create_form = $this->entityFormBuilder()->getForm($samplenode);
    return $node_create_form;
  }

}
