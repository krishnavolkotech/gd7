<?php
/**
 * Created by PhpStorm.
 * User: sandeep
 * Date: 28/3/17
 * Time: 1:10 PM
 */

namespace Drupal\cust_group\Controller;


use Drupal\Core\Controller\ControllerBase;

class Arbeitsanleitungen extends ControllerBase {

  public function read_arbeitsanleitungen_zip() {
    $path = DRUPAL_ROOT . '/' . \Drupal::config('arbeitsanleitungen.settings')
        ->get('import_path');

    $folders_of_al_edv = DRUPAL_ROOT . '/' . 'sites/default/files/al-edv/';
    $result = [];
    try {
      ini_set('memory_limit', '3G');
      ini_set('max_execution_time', 0);
      if ($path && file_exists($path)) {
        if (is_dir($folders_of_al_edv)) {
          shell_exec("rm -rf " . $folders_of_al_edv);
        }
        shell_exec("mkdir -p " . $folders_of_al_edv);
        shell_exec("unzip " . $path . " -d " . $folders_of_al_edv);
        shell_exec("rm " . $path);
      }
      $result['#markup'] = t("Successfully Extracted file in al-edv");
    } catch (Exception $e) {
      \Drupal::logger('arbeitsanleitungen_file_issue')->error($e->getMessage());
      $body = $e->getMessage();
      $result['#markup'] = $body;
//      HzdservicesHelper::send_problems_notification('release_read_csv', $mail, $subject, $body);
//      $response = t('Error occurred while Releases import. Please check db log for error details.');
    }
    //return $output = array('#markup' => $response);
    return $result;
  }
}