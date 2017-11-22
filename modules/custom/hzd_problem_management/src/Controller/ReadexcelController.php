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
   * Reads csv file and saves as nodes
   * Check the file separator,presently we are usng ';' as separator
   * validates the csv file format, and check for the existance of service in the database in the function "validate_csv"
   * function saving_problem_node saves the data as problems node , only if the validation returns true
   * status of the import is stored in the "insert_import_status"
   */
  public function read_problem_csv() {

    $path = DRUPAL_ROOT . '/' . \Drupal::config('problem_management.settings')
        ->get('import_path');
    $importer = new ProblemImport($path);
    $status = t('Success');
    $mail = \Drupal::config('problem_management.settings')->get('import_mail');
    try {
      $import = $importer->processImport();
      $importstat = t('OK');
      HzdStorage::insert_import_status($importstat, 'File imported sucessfully.');
      if($import) {
        $subject = 'New service found while importing problems';
        $import = array_unique($import);
        $body = t(" We have found a new service " . implode(",", $import) . " which does not match the service in our database.");
        HzdservicesHelper::send_problems_notification('problem_management_read_csv', $mail, $subject, $body);
        $status = t('Import success but new services found.');
      }
    } catch (ProblemImportException $e) {
      $importstat = t('Error');
      HzdservicesHelper::send_problems_notification('problem_management_read_csv', $mail, $e->getSubject($e->type), $e->getBody($e->type));
      HzdStorage::insert_import_status($importstat, $e->getMessage());
      \Drupal::logger('problem')->error($e->getSubject($e->type));
      $status = $e->getMessage();
    }
    $response = array(
      '#markup' => $status,
    );
    return $response;
  }

}
