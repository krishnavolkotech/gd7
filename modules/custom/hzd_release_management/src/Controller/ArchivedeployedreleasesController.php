<?php

namespace Drupal\hzd_release_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

/**
 *
 */
class ArchivedeployedreleasesController extends ControllerBase {

  /**
   *
   */
  public function archive_deployedreleases() {
    $nid = $_POST['nid'];
    if (is_numeric($nid)) {
      $node = Node::load($nid);
      if($node instanceof Node){
        $node->set('field_archived_release',1)->save();

        if ($node->field_environment->value == 1) {
            // If environment is Production, delete cache for deployed releases overview table
            // $cids = ['deployedReleasesOverview459', 'deployedReleasesOverview460'];
            // \Drupal::cache()->deleteMultiple($cids);
            \Drupal\Core\Cache\Cache::invalidateTags(array('deployedReleasesOverview'));
        }
      }
      $output = 'true';
    }
    $result['#attached']['drupalSettings']['deploy_release'] = array(
      'data' => $output,
      'status' => TRUE,
    );

    return $result;
  }

}
