<?php
/**
 * Created by PhpStorm.
 * User: sandeep
 * Date: 28/3/17
 * Time: 1:10 PM
 */

namespace Drupal\cust_group\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\hzd_services\HzdservicesHelper;

class Arbeitsanleitungen extends ControllerBase {

public static function read_arbeitsanleitungen_zip() {
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
      $mail = \Drupal::config('hzd_notifications.settings')->get('arbeitsanleitungen_not_import');
      $subject = "Error while importing al-edv zip file.";
      $body = \Drupal::config('hzd_notifications.settings')->get('arb_failed_download_text')['value'];
      \Drupal::logger('arbeitsanleitungen_file_issue')->error($e->getMessage());
      $result['#markup'] = $e->getMessage();
      HzdservicesHelper::send_arbeitsanleitungen_notification('read_arbeitsanleitungen_zipfile', $mail, $subject, $body);
    }
    return $result;
  }
}