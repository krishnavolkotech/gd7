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
    $config_path = \Drupal::config('arbeitsanleitungen.settings')
      ->get('import_path');
    $path = DRUPAL_ROOT . '/' . $config_path;
    $folders_of_al_edv = DRUPAL_ROOT . '/' . 'sites/default/files/al-edv/';
    $bak_al_edv = DRUPAL_ROOT . '/' . 'sites/default/files/bak-al-edv/';
    $result = [];
    try {
      ini_set('memory_limit', '3G');
      ini_set('max_execution_time', 0);
      if ($path && file_exists($path)) {
        $filename = end(explode($path));
        if (is_dir($folders_of_al_edv)) {
          shell_exec("rm -rf " . $folders_of_al_edv);
        }
        shell_exec("mkdir -p " . $folders_of_al_edv);
        shell_exec("unzip " . $path . " -d " . $folders_of_al_edv);
        if (is_dir($bak_al_edv)) {
          if (file_exists($bak_al_edv . $filename)) {
            shell_exec("rm " . $bak_al_edv . $filename);
          }
          shell_exec("mv " . $path . " " . $bak_al_edv);
        } else {
          shell_exec("mkdir -p " . $bak_al_edv);
          shell_exec("mv " . $path . " " . $bak_al_edv);
        }
        $mail = \Drupal::config('hzd_notifications.settings')->get('arbeitsanleitungen_not_import');
        $subject = "Successfully Extracted file in al-edv";
        $body = \Drupal::config('hzd_notifications.settings')->get('arb_success_download_text')['value'];
        HzdservicesHelper::send_arbeitsanleitungen_notification('read_arbeitsanleitungen_zipfile', $mail, $subject, $body);
        $result['#markup'] = t("Successfully Extracted file in al-edv");
      } else {
        $result['#markup'] = t("@file file not present.", ['@file' => $config_path]);
      }
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