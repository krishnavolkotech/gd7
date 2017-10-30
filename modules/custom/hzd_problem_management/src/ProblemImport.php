<?php
/**
 * Created by PhpStorm.
 * User: sandeep
 * Date: 27/10/17
 * Time: 3:23 PM
 */

namespace Drupal\problem_management;


use Drupal\Component\Utility\Html;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\node\Entity\Node;
use Drupal\problem_management\Exception\CustomException;
use Drupal\problem_management\Exception\ProblemImportException;

class ProblemImport {

  protected $headers = [
    'sno',
    'status',
    'service',
    'function',
    'release',
    'title',
    'body',
    'diagnose',
    'solution',
    'workaround',
    'version',
    'priority',
    'taskforce',
    'comment',
    'processing',
    'attachment',
    'eroffnet',
    'ticketstore_link',
    'closed',
    'timezone',
  ];

  protected $importPath = NULL;

  protected $fileHandle = NULL;

  public $ignored = [];
  public $failed = [];


  public function __construct($path) {
    $this->importPath = $path;
  }


  public function processImport() {
    if (!file_exists($this->importPath)) {
      throw new ProblemImportException("file_not_found");
    }
    setlocale(LC_ALL, 'de_DE.UTF-8');
    $this->fileHandle = fopen($this->importPath, "r");
    if (!$this->fileHandle) {
      throw new ProblemImportException("file_unable_to_read");
    }
    $count = 1;
    $data = fgetcsv($this->fileHandle, 5000, ";");
    if (!$data) {
      throw new ProblemImportException("empty_file");
    }
    $this->failedNodes = [];
    while (($data = fgetcsv($this->fileHandle, 5000, ";")) !== FALSE) {
      foreach ($data as $key => $value) {
        $values[$this->headers[$key]] = $data[$key];
      }
      if (count($values) == 1) {
        throw new ProblemImportException("invalid_data");
      }
//      pr($values);exit;
      if ($this->validateCsv($values)) {
        $this->saveProblemNode($values);
      }
      else {
        throw new ProblemImportException('new_service_found');
      }
      $count++;
    }

    if (count($this->failedNodes) === 0 && $count > 1) {
    }
    else {
    }
    return TRUE;
  }

  protected function validateCsv(&$values) {
    $service = $values['service'];
    if (!trim($values['sno'])) {
      return FALSE;
    }
    $node = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['title' => $service, 'type' => 'services']);
    if (!$node) {
      return FALSE;
    }
    $values['service'] = reset($node)->id();
    return reset($node)->id();
  }

  protected
  function saveProblemNode($values) {
    if (($values['title'] == '') || ($values['status'] == '')) {
      throw new ProblemImportException('invalid_data');
    }
    $values['sno'] = (int) $values['sno'];

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'problem')
      ->condition('field_s_no', $values['sno'])
      ->accessCheck(FALSE)
      ->execute();

    $node = Node::load(reset($query));
    $nodeExists = FALSE;

    $replace = array('/' => '.', '-' => '.');
    $formatted_date = strtr($values['eroffnet'], $replace);

    $date_time = explode(" ", $formatted_date);

    if (isset($date_time['0'])) {
      $date_format = explode(".", $date_time['0']);
    }
    if (isset($date_time['1'])) {
      $time_format = explode(":", $date_time['1']);
    }

    if (isset($date_format['1']) && isset($date_format['0']) && isset($date_format['2'])) {
      if (isset($time_format['0']) && isset($time_format['1']) && isset($time_format['2'])) {
        $date = mktime((int) $time_format['0'], (int) $time_format['1'], (int) $time_format['2'], (int) $date_format['1'], (int) $date_format['0'], (int) $date_format['2']);
      }
    }


    $eroffnet = (isset($date) ? $date : \Drupal::time()->getRequestTime());

    if ($node) {
//      $created = $node->getCreatedTime();
      unset($values['sno']);
      $existing_node_vals = array();

      $existing_node_vals['status'] = $node->field_problem_status->value;
      $existing_node_vals['service'] = $node->field_services->target_id;
      $existing_node_vals['function'] = $node->field_function->value;
      $existing_node_vals['release'] = $node->field_release->value;
      $existing_node_vals['title'] = $node->getTitle();
      $existing_node_vals['body'] = $node->body->value;
      $existing_node_vals['diagnose'] = $node->field_diagnose->value;
      $existing_node_vals['solution'] = $node->field_solution->value;
      $existing_node_vals['workaround'] = $node->field_work_around->value;
      $existing_node_vals['version'] = $node->field_version->value;
      $existing_node_vals['priority'] = $node->field_priority->value;
      $existing_node_vals['taskforce'] = $node->field_task_force->value;
      $existing_node_vals['comment'] = $node->field_comments->value;
      $existing_node_vals['processing'] = $node->field_processing->value;
      $existing_node_vals['attachment'] = $node->field_attachment->value;
      $existing_node_vals['eroffnet'] = $node->field_eroffnet->value;
      $existing_node_vals['timezone'] = 'Europe/Berlin';
      $existing_node_vals['closed'] = $node->field_closed->value;
      $existing_node_vals['ticketstore_link'] = $node->field_ticketstore_link->value;

      /**
       * $existing_node_vals['problem_eroffnet'] = $node->field_problem_eroffnet->value;
       * // $existing_node_vals['problem_status'] = $node->field_problem_status->value;
       * $existing_node_vals['ticketstore_count'] = $node->field_ticketstore_count->value;
       * $existing_node_vals['ticketstore_link'] = $node->field_ticketstore_link->value;
       */
      if (count(array_diff($existing_node_vals, $values)) == 0) {
        $this->ignored[] = $node->get('field_s_no')->value;
        // Nothing to do when there are no changes for the node. so skipping the node.
        return TRUE;
      }
      $problem_node = $node;
      $nodeExists = TRUE;
//      pr($problem_node);
    }
    else {
      $problem_node = Node::create([
        'uid' => 1,
        'type' => 'problem',
        'created' => \Drupal::time()->getRequestTime(),
        'field_s_no' => $values['sno'],
      ]);

    }
    $problem_node->setTitle(Html::escape($values['title']));
    $problem_node->set('status', 1);
    $problem_node->set('body', array(
      'summary' => '',
      'value' => $values['body'],
      'format' => 'basic_html',
    ));
    $problem_node->set('comment', array(
        'status' => 2,
        'cid' => 0,
        'last_comment_timestamp' => 0,
        'last_comment_name' => '',
        'last_comment_uid' => 0,
        'comment_count' => 0,
      )
    );
    $problem_node->set('field_attachment', Html::escape($values['attachment']));
    $problem_node->set('field_closed', $values['closed']);
    $problem_node->set('field_comments', array(
        'value' => $values['comment'],
        'format' => 'basic_html',
      )
    );
    $problem_node->set('field_services', array(
        'target_id' => $values['service'],
      )
    );
    $problem_node->set('field_diagnose', $values['diagnose']);
    $problem_node->set('field_eroffnet', $values['eroffnet']);
    $problem_node->set('field_function', $values['function']);
    $problem_node->set('field_priority', $values['priority']);
    $problem_node->set('field_problem_eroffnet', $eroffnet);
    $problem_node->set('field_problem_status', $values['status']);
    $problem_node->set('field_processing', $values['processing']);
    $problem_node->set('field_release', $values['release']);
    // $problem_node->set('field_sdcallid', $values['sdcallid']);.
    $problem_node->set('field_solution', array(
      'value' => $values['solution'],
      'format' => 'basic_html',
    ));
    // $problem_node->set('field_s_no', $values['sno']);
    // $problem_node->set('field_release', $values['release']);.
    $problem_node->set('field_task_force', array($values['taskforce']));
    // $problem_node->set('field_release', $values['release']);
    // $problem_node->set('field_ticketstore_count', $values['ticketstore_count']);
    // $problem_node->set('field_release', $values['release']);
    $problem_node->set('field_ticketstore_link', array(
      'value' => $values['ticketstore_link'],
      'format' => 'basic_html',
    ));
    // $problem_node->set('field_timezone', $values['timezone']);.
    $problem_node->set('field_version', $values['version']);
    $problem_node->set('field_work_around', array(
      'value' => $values['workaround'],
      'format' => 'basic_html',
    ));
    /*    pr($problem_node->save());
        echo '=====';
        pr($problem_node->isNew());exit;*/
    if ($problem_node->save()) {
      if (!$nodeExists) {
        $group = Group::load(PROBLEM_MANAGEMENT);
        // Adding node to group.
        $group_content = GroupContent::create([
          'type' => $group->getGroupType()
            ->getContentPlugin('group_node:problem')
            ->getContentTypeConfigId(),
          'gid' => PROBLEM_MANAGEMENT,
          'entity_id' => $problem_node->id(),
          'request_status' => 1,
          'label' => $values['title'],
          'uid' => 1,
        ]);
        return $group_content->save();
      }
      return TRUE;
    }
    else {
      $this->failedNodes[] = $values['sno'];
    }
  }


}