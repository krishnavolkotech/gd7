<?php

namespace Drupal\hzd_release_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\hzd_release_management\HzdreleasemanagementStorage;
use Drupal\hzd_services\HzdservicesHelper;
use Drupal\hzd_release_management\HzdreleasemanagementHelper;
use Drupal\node\Entity\Node;

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
  public function read_release_csv() {
    try {
      ini_set('memory_limit', '3G');
      ini_set('max_execution_time', 0);
      $mail = \Drupal::config('hzd_release_management.settings')->get('import_mail_releases', ' ');
      $subject = "Error while release csv's import";
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
              } else {
                // @sending mail to user when file need permissions or when file is corrupted
                $body = t("There is an issue while reading the file @file.", ['@file' => $file_path]);
                HzdservicesHelper::send_problems_notification('release_read_csv', $mail, $subject, $body);
                $response .= $type . t(" error while reading.") . "<br>";
              }
            } else {
              // @sending mail to user when file need permissions or when file is corrupted
              $body = t("There is an issue while reading the file @file.", ['@file' => $file_path]);
              HzdservicesHelper::send_problems_notification('release_read_csv', $mail, $subject, $body);
              $response .= $type . t(" error while reading.") . "<br>";
            }
          } else {
            // Sending mail to user when file does not exist.
            $body = t("There is an issue while importing of the file @file. The filename does not exist or it could have been corrupted.", ['@file' => $file_path]);
            HzdservicesHelper::send_problems_notification('release_read_csv', $mail, $subject, $body);
            $status = t('Error');
            $msg = t(' Import file not found <br>');
            // insert_import_status($status, $msg);.
            $response .= $type . t(' Import file not found<br>') . "<br>";
          }
        }
      }
      //Attempt downloding the previously failed documentations.
      self::download_failed_documentations();
    }
    catch (Exception $e) {
      \Drupal::logger('hzd_release_management')->error($e->getMessage());
      $body = $e->getMessage();
      HzdservicesHelper::send_problems_notification('release_read_csv', $mail, $subject, $body);
      $response = t('Error occurred while Releases import. Please check db log for error details.');
    }
    return $output = array('#markup' => $response);
  }

  public function download_failed_documentations(){
    //Get the list of all the previously failed releases.
    $query = \Drupal::database()->select('release_doc_failed_download_info', 'rdfdi');
    $query->Fields('rdfdi', array('nid'));
    $query->addExpression('count(nid)', 'cnt');
    $query->groupBy('nid');
    $query->having("count(nid) < 3");
    $query = $query->execute()
    ->fetchAll();
    foreach ($query as $key => $value) {
      $node = Node::load($value->nid);
      //continueing the loop if node is somehow deleted from the system.
      if(!$node){
        continue;
      }
      $service = strtolower($node->get('field_relese_services')->first()->entity->label());
      //attempt the download with the prepared data now.
      HzdreleasemanagementStorage::do_download_documentation($node->id(), $node->label(), $node->get('field_documentation_link')->value, $service);
    }
  }

}
