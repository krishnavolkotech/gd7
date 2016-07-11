<?php
/**
 * @file
 * Contains \Drupal\problem_management\Controller\ReadexcelController.
 */
namespace Drupal\problem_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\problem_management\HzdStorage;
use Drupal\hzd_services\HzdservicesHelper;
use Drupal\problem_management\HzdproblemmanagementHelper;

use Drupal\Core\Url;

/**
 * Class ReadexcelController
 * @package Drupal\problem_management\Controller
 */
class ReadexcelController extends ControllerBase {
/*
 * Callback for the read excel file
 * Use the function for the cron run
 */
  function read_problem_csv() {
   $header_values = array(
		  'sno', 'status', 'service', 'function', 'release', 'title' ,
		  'problem_text', 'diagnose', 'solution', 'workaround', 'version', 'priority', 
		  'taskforce', 'comment', 'processing', 'attachment', 'eroffnet', 
                  'closed', 'problem_status', 'ticketstore_count', 'ticketstore_link'
		  );
   
   $path = \Drupal::config('problem_management.settings')->get('import_path');
   if ($path) {
     if(file_exists($path)) {
      $output = HzdproblemmanagementHelper::importing_problem_csv($path, $header_values);
     }
     else {
       $mail = \Drupal::config('problem_management.settings')->get('import_mail');
       $subject = 'Error while import';
       $body = t("There is an issue while importing of the file" . $path . ". The filename does not exist or it could have been corrupted.");
       HzdservicesHelper::send_problems_notification('problem_management_read_csv', $mail, $subject, $body);
       $status = t('Error');
       $msg = t('No import file found');
       HzdStorage::insert_import_status($status, $msg);
       $output = 'File Does Not EXIST';
     }
   }
   else {
     $mail = \Drupal::config('problem_management.settings')->get('import_mail');
     $subject = 'Error while import';
     $body = t("There is an issue while importing of the problems from file" . $path . "Check whether the format of problems is in proper CSV format or not.");

     HzdservicesHelper::send_problems_notification('problem_management_read_csv', $mail, $subject, $body);
     // $output =  t('Set the import path at') . \Drupal::l('set import path', Url::fromRoute('admin/config/problem'));  
    }
    $response = array(
      '#markup' => $output
     );
    return $response;
  }
}
