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
    'empty_file' => 'File is empty',
    'invalid_data' => 'Invalid data',
    'new_service_found' => 'New service detected',
    'file_unable_to_read' => 'Unable to read the source file',
  ];

  protected $mailBody = [
    'file_not_found' => 'File not found. Error while importing problems',
    'empty_file' => 'File is empty',
    'invalid_data' => 'Invalid data',
    'new_service_found' => 'New service detected',
    'file_unable_to_read' => 'Unable to read the source file',
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
    return isset($this->mailBody[$type]) ? $this->mailBody[$type] : t('Unknown Exception Occured');
  }


}
