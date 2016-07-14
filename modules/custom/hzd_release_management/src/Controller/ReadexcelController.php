<?php

namespace Drupal\hzd_release_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\hzd_release_management\HzdreleasemanagementStorage;
use Drupal\hzd_services\HzdservicesHelper;
use Drupal\hzd_release_management\HzdreleasemanagementHelper;

/**
 * Class ReadexcelController.
 *
 * @package Drupal\hzd_release_management\Controller
 */
class ReadexcelController extends ControllerBase {

  /**
   * Callback for the read excel file
   * Use the function for the cron run
   * Header values given in the csv file must be equal to the header array set in the function "_csv_headers".
   * Releases are of 4 types released, in progress,locked and deployed
   * Types are differentiated status column in the csv file.
   *
   * Reads the csv file from paths which are set at the settings page.
   * validates the csv file format, and check for the existance of service in the database.
   * if the services does not exists sends out an email.
   * Saves the releases node after validation status is TRUE.
   * Sends out emails if the file reading contains any error or if the path does not exist.
   */
  function read_release_csv() {

    global $user;
    $response = '';

    $path_header = HzdreleasemanagementHelper::_csv_headers();
    if ($path_header['path']) {
      $path = $path_header['path'];
      foreach ($path as $type => $file_path) {
        if (file_exists($file_path)) {
          $header = $path_header['headers'][$type];
          if (fopen($file_path, "r")) {
            $handle = fopen($file_path, "r");
            if ($type == 'ex_eoss') {
              $type = 'released';
            }
            $sucess = HzdreleasemanagementStorage::release_reading_csv($handle, $header, $type, $file_path);
            if ($sucess) {
              $response .= t('files imported sucessfully') . "<br>";
            }
            else {
              // @sending mail to user when file need permissions or when file is corrupted
              $mail = \Drupal::config('hzd_release_management.settings')->get('import_mail_releases', ' ');
              $subject = 'Error while import';
              $body = t("There is an issue while reading the file" . $file_path . ".");
              HzdservicesHelper::send_problems_notification('release_read_csv', $mail, $subject, $body);
              $response = $type . t('ERROR WHILE READING');
            }
          }
          else {
            // @sending mail to user when file need permissions or when file is corrupted
            $mail = \Drupal::config('hzd_release_management.settings')->get('import_mail_releases', ' ');
            $subject = 'Error while import';
            $body = t("There is an issue while reading the file" . $file_path . ".");
            HzdservicesHelper::send_problems_notification('release_read_csv', $mail, $subject, $body);
            $response = $type . t('ERROR WHILE READING');
          }
        }
        else {
          // Sending mail to user when file does not exist.
          $mail = \Drupal::config('hzd_release_management.settings')->get('import_mail_releases');
          $subject = 'Error while import';
          $body = t("There is an issue while importing of the file" . $file_path . ". The filename does not exist or it could have been corrupted.");
          HzdservicesHelper::send_problems_notification('release_read_csv', $mail, $subject, $body);
          $status = t('Error');
          $msg = t('No import file found <br>');
          // insert_import_status($status, $msg);.
          $response .= $type . t('NO import file found<br>');
        }
      }

    }

    return $output = array('#markup' => $response);
  }

}
