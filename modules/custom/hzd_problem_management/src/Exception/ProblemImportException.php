<?php

/**
 * @file
 * Contains ${NAMESPACE}\CustomException.
 */

namespace Drupal\problem_management\Exception;

use Exception;

/**
 * Class CustomException.
 *
 * @package CustomException\Core\Exception
 */
class ProblemImportException extends Exception {
  protected $replacement = [];
  protected $message_raw = 'Unknown exception';


  protected $mailSubject = [
    'file_not_found' => 'File not found. Error while importing problems',
    'empty_file' => 'No Data Found in imported csv',
    'invalid_data' => 'Invalid data',
    'new_service_found' => 'New service found while importing problems',
    'file_unable_to_read' => 'Unable to read the source file',
  ];

  protected $mailBody = [
    'file_not_found' => 'File %path not found. Error while importing problems',
    'empty_file' => 'There is an issue while importing of the file %path. No Data Found in imported csv',
    'invalid_data' => 'Invalid data in file %path at Sdcallid - %sid',
    'new_service_found' => 'We have found a new service %service which does not match the service in our database.',
    'file_unable_to_read' => 'There is an issue while importing of the file %path. Error with file either permissions denied or file corrupted.',
  ];
  public $type = NULL;

  /**
   * Construct the exception. Note: The message is NOT binary safe.
   *
   * @link http://php.net/manual/en/exception.construct.php
   * @param string $message
   *   [optional] The Exception message to throw.
   * @param array $replacement
   *   [optional] Untranslatable values to replace into the string.
   * @param int $code
   *   [optional] The Exception code.
   */
  public function __construct($type = NULL, $code = 0, $options = []) {
    $this->options = $options;
    $this->type = $type;
    parent::__construct($this->getSubject($type), $code);
  }


  public function getSubject($type) {
    return isset($this->mailSubject[$type]) ? $this->mailSubject[$type] : t('Unknown Exception Occured');
  }

  public function getBody($type) {
    return isset($this->mailBody[$type]) ? strtr($this->mailBody[$type], $this->options) : t('Unknown Exception Occured');
  }


}
