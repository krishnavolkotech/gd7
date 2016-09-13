<?php

namespace Drupal\hzd_release_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

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
            $query = \Drupal::database()->update('node__field_archived_release');
            $query->fields([
              'field_archived_release_value' => 1
            ]);
            $query->condition('entity_id', $nid);
            $query->execute();
            $output = 'true';
        }
        $result['#attached']['drupalSettings']['deploy_release'] = array(
          'data' => $output,
          'status' => TRUE
        );
        
        return $result;
    }

}
