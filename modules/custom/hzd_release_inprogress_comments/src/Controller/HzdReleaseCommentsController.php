<?php

namespace Drupal\hzd_release_inprogress_comments\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\hzd_release_inprogress_comments\HzdReleaseCommentsStorage;

/**
 * Class HzdReleaseCommentsController.
 */
class HzdReleaseCommentsController extends ControllerBase {

  /**
   * @return mixed
   */
  public function view_release_comments() {

    $output['content']['#attached']['library'] = array(
//      'hzd_customizations/hzd_customizations',
//      'downtimes/downtimes',
      'hzd_earlywarnings/hzd_earlywarnings',
    );

    //$output['content']['pretext'] = HzdearlywarningsStorage::early_warning_text();
    $output['content']['#prefix'] = '<div id = "earlywarnings_results_wrapper">';
    $output['content']['earlywarnings_filter_form'] = \Drupal::formBuilder()
      ->getForm('Drupal\hzd_release_inprogress_comments\Form\ReleaseCommentsFilterForm');
    $output['content']['earlywarnings_filter_table'] = HzdReleaseCommentsStorage::view_releasecomments_display_table();
    $output['content']['#suffix'] = '</div>';
    return $output;
  }

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
