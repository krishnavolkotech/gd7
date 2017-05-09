<?php

namespace Drupal\downtimes\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class CurrentProblemsController
 * @package Drupal\problem_management\Controller
 */
class DowntimesNotesDisplayController extends ControllerBase {

    /*
     * Callback function for the downtimes display
     */

    public function downtime_notes_message_display() {
//        echo 321321;exit;
        $downtimes = \Drupal::config('downtimes.settings')->get('current_downtimes');
    return array(
      '#markup' => $downtimes,
    );
    }

}
