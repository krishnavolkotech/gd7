<?php

namespace Drupal\hzd_release_management\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Class ReadimportreleasecsvController.
 *
 * @package Drupal\hzd_release_management\Controller
 */
class ReadimportreleasecsvController extends ControllerBase {

  /**
   * Callback for the read excel file
   * Use the function for the cron run.
   */
  function read_import_release_csv() {
    setlocale(LC_ALL, 'de_DE.UTF-8');
    $batch['title'] = t('Import intial release documents');
    $file_path = \Drupal::config('hzd_release_management.settings')->get('import_path_csv_initial_released');
    if (file_exists($file_path)) {
      if (fopen($file_path, "r")) {
        $handle = fopen($file_path, "r");
        $batch_limit = 25;
        $count = 1;

        for ($i = 1; $i < 5000; $i++) {
          $data = fgetcsv($handle, 5000, ",");
          if ($data) {
            if ($i == 1) {
              $batch['operations'][] = array('user_import_release_reading_csv', array($file_path, $handle, ftell($handle)));
              continue;
            }
            if ($i % $batch_limit == 0) {
              $batch['operations'][] = array('user_import_release_reading_csv', array($file_path, $handle, ftell($handle)));
            }
          }
          if (feof($handle)) {
            // End loop.
            break;
          }
        }
      }
    }
    batch_set($batch);
    batch_process('');
    return $batch;
  }

}
