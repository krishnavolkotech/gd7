<?php

namespace Drupal\problem_management\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\problem_management\Exception\ProblemImportException;
use Drupal\problem_management\HzdStorage;
use Drupal\hzd_services\HzdservicesHelper;
use Drupal\problem_management\HzdproblemmanagementHelper;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\problem_management\ProblemImport;

/**
 * Class ReadexcelController.
 *
 * @package Drupal\problem_management\Controller
 */
class ReadexcelController extends ControllerBase {

  /**
   * Callback for the read excel file
   * Use the function for the cron run.
   */
  public function read_problem_csv() {

    $path = DRUPAL_ROOT . '/' . \Drupal::config('problem_management.settings')
        ->get('import_path');
    $importer = new ProblemImport($path);
    $status = t('Success');
    try {
      $importer->processImport();
    } catch (ProblemImportException $e) {
      $mail = \Drupal::config('problem_management.settings')->get('import_mail');
      HzdservicesHelper::send_problems_notification('problem_management_read_csv', $mail, $e->getSubject($e->type), $e->getBody($e->type));
//      HzdStorage::insert_import_status($status, $msg);
      // watchdog('problem', $message, array(), 'error');.
      \Drupal::logger('problem')->error($e->getSubject($e->type));
      $status = $e->getMessage();
      // exit;


      // echo $e->getMessageRaw();
      // exit;
    } catch (\Exception $exception) {

    }
//pr($importer->ignored);exit;

    /*try {
      if (file_exists($path)) {
        $output = HzdproblemmanagementHelper::importing_problem_csv($path, $header_values);
      }
      else {
        throw new CustomException('No such File exists');
      }
    }*/

    /*if ($path) {
      if (file_exists($path)) {
        $output = HzdproblemmanagementHelper::importing_problem_csv($path, $header_values);
      }
      else {
        $mail = \Drupal::config('problem_management.settings')
          ->get('import_mail');
        $subject = $this->t('Error while import');
        $body = $this->t("There is an issue while importing of the file " . $path . ". The  import file not found or it could have been corrupted.");
        HzdservicesHelper::send_problems_notification('problem_management_read_csv', $mail, $subject, $body);
        $status = $this->t('Error');
        $msg = $this->t('Import File Not Found');
        HzdStorage::insert_import_status($status, $msg);
        $output = $this->t('Import File Not Found');
      }
    }
    else {
      $output = $this->t('File Path Not Specified');
      $mail = \Drupal::config('problem_management.settings')
        ->get('import_mail');
      $subject = 'Error while import';
      $body = $this->t("There is an issue while importing of the problems from file " . $path . "Check whether the format of problems is in proper CSV format or not.");

      HzdservicesHelper::send_problems_notification('problem_management_read_csv', $mail, $subject, $body);

      $url = Url::fromUserInput('/admin/settings/problem');
      // $link = Link::fromTextAndUrl($text, $url);.
      $text = 'Set the import path at';
      $path = Link::fromTextAndUrl($text, $url);

      $output .= t('Set the import path at') . $path;
    }*/
    $response = array(
      '#markup' => $status,
    );
    return $response;
  }

}
