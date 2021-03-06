<?php
/**
 * Created by PhpStorm.
 * User: sandeep
 * Date: 27/10/17
 * Time: 3:23 PM
 */

namespace Drupal\problem_management;


use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;
use Drupal\node\Entity\Node;
use Drupal\problem_management\Exception\CustomException;
use Drupal\problem_management\Exception\ProblemImportException;

//Anpassungen für Import der csv Datei von ORBIT
//4 neue Felder
class ProblemImport {
//header of csv-file from ORBIT: "SDCallID";"Status";"Kategorie1";"Kategorie2";"Kategorie3";"Betroffene Releases";"Titel";"Beschreibung";"Ursache";"Lösung";"Workaround";"Behoben mit Release";"Priorität";"TaskForce";"Anmerkungen";"Letztes Update";"Erstellerland";"Eröffnet";"Anhang";"Anzahl Anhänge";"Proaktive Eröffnung";"Schnittstelle betroffen";"Ursache nicht gefunden";"Teilnahme am LÜAFP"
  protected $headers = [
    'sno',
    'status',
    'service',
    'function',
    'symptoms',
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
    'last_update',
    'creator',
    'created',
    'ticketstore_link',
    'ticketstore_count',
    'proactive_creation',
    'interface_affected',
    'unlocated_cause',
    'part_of_lueafp',
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
      throw new ProblemImportException("file_not_found", 0, ['%path' => $this->importPath]);
    }
    setlocale(LC_ALL, 'de_DE.UTF-8');
    $this->fileHandle = fopen($this->importPath, "r");
    if (!$this->fileHandle) {
      throw new ProblemImportException("file_unable_to_read", 0, ['%path' => $this->importPath]);
    }
    $count = 1;
    $data = fgetcsv($this->fileHandle, 5000, ';', '"', '"' );
    if (!$data) {
      throw new ProblemImportException("empty_file", 0, ['%path' => $this->importPath]);
    }
    $this->failedNodes = [];
    $newservices = [];
    while (($data = fgetcsv($this->fileHandle, 5000, ';', '"', '"' )) !== FALSE) {
      foreach ($data as $key => $value) {
        $values[$this->headers[$key]] = $data[$key];
      }
      if (count($values) != count($this->headers)) {
        throw new ProblemImportException("invalid_data", 0, ['%path' => $this->importPath, '%sid' => $values['sno']]);
      }
      if ($this->validateCsv($values)) {
        $this->saveProblemNode($values);
      }
      else {
        $newservices[] = $values['service'];
      }
      $count++;
    }

//    if (count($this->failedNodes) === 0 && $count > 1) {
//    }
//    else {
//    }
    if(count($newservices) > 0) {
        return $newservices;
    } else {
        return FALSE;
    }  
  }

  protected function validateCsv(&$values) {
    $service = $values['service'];
    if (!trim($values['sno'])) {
      return FALSE;
    }
    $node = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadByProperties(['field_problem_name' => $service, 'type' => 'services']);
    if (!$node) {
//        throw new ProblemImportException('new_service_found', 0, ['%service' => $service]);
      return FALSE;
    }
    $values['service'] = reset($node)->id();
    return reset($node)->id();
  }

  protected function saveProblemNode($values) {
    if (($values['title'] == '') || ($values['status'] == '')) {
      throw new ProblemImportException('invalid_data');
    }
 //field_s_no ist out of use. New field_orp_nr is string.
 //   $values['sno'] = (int) $values['sno'];


    //Formatierung/Anpassung von Feldinhalten aus CSV
    if ($values['taskforce'] == 'true'){
      $values['taskforce'] = 'JA';
    } 
    elseif ($values['taskforce'] == 'false'){
      $values['taskforce'] = 'NEIN';
    }

    if ($values['proactive_creation'] == 'true'){
      $values['proactive_creation'] = 'JA';
    }
    elseif ($values['proactive_creation'] == 'false'){
      $values['proactive_creation'] = 'NEIN';
    }

    if ($values['interface_affected'] == 'true'){
      $values['interface_affected'] = 'JA';
    }
    elseif ($values['interface_affected'] == 'false'){
      $values['interface_affected'] = 'NEIN';
    }

    if ($values['unlocated_cause'] == 'true'){
      $values['unlocated_cause'] = 'JA';
    }
    elseif ($values['unlocated_cause'] == 'false'){
      $values['unlocated_cause'] = 'NEIN';
    }

    if ($values['part_of_lueafp'] == 'true'){
      $values['part_of_lueafp'] = 'JA';
    }
    elseif ($values['part_of_lueafp'] == 'false'){
      $values['part_of_lueafp'] = 'NEIN';
    }

    if ($values['created']){
      $DateCreated = explode(' ', $values['created'], -1);
      $strDateCreated = strval($DateCreated[0]);
//      $CreatedDate = date_create_from_format('d.m.y', $strDateCreated);
//      $values['created'] = date_format($CreatedDate, 'd.m.Y'); 
      $values['created'] = $strDateCreated;
    }

    if ($values['last_update']){
      $DateUpdate=explode(' ', $values['last_update'], -1);
      $strDateUpdate = strval($DateUpdate[0]);
//      $UpdateDate = date_create_from_format('d.m.y', $strDateUpdate);
//      $values['last_update'] = date_format($UpdateDate, 'd.m.Y');
      $values['last_update'] = $strDateUpdate;
    }

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'problem')
      ->condition('field_orp_nr', $values['sno'])
     // ->condition('field_s_no', $values['sno'])
      ->accessCheck(FALSE)
      ->execute();
    $node = null;
    $nodeExists = FALSE;
    if($query){
      $node = Node::load(reset($query));
    }
    //funktioniert vermutlich nicht und $eroffnet wird mit time gefüllt
    $replace = array('/' => '.', '-' => '.');
    $formatted_date = strtr($values['created'], $replace);

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
// field timezone is no longer in csv-export from ORBIT
//   unset($values['timezone']);

      $existing_node_vals = array();

      $existing_node_vals['status'] = $node->field_problem_status->value;
      $existing_node_vals['service'] = $node->field_services->target_id;
      $existing_node_vals['function'] = $node->field_function->value;
      $existing_node_vals['symptoms'] = $node->field_problem_symptoms->value;
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
      $existing_node_vals['last_update'] = $node->field_processing->value;
      $existing_node_vals['creator'] = $node->field_attachment->value;
      $existing_node_vals['created'] = $node->field_eroffnet->value;
      $existing_node_vals['ticketstore_link'] = $node->field_ticketstore_link->value;
      $existing_node_vals['ticketstore_count'] = $node->field_ticketstore_count->value;
      $existing_node_vals['proactive_creation'] = $node->field_proactive_creation->value;
      $existing_node_vals['interface_affected'] = $node->field_interface_affected->value;
      $existing_node_vals['unlocated_cause'] = $node->field_unlocated_cause->value;
      $existing_node_vals['part_of_lueafp'] = $node->field_part_of_lueafp->value;

      $diff = TRUE;
      $basic_html_fileds = ['body', 'solution', 'taskforce', 'workaround', 'comment', 'field_comments'];
      foreach ($values as $key => $val) {
        if (in_array($key, $basic_html_fileds)) {
          if (check_markup($values[$key], 'plain_text') != $existing_node_vals[$key]) {
            $diff = FALSE;
            break;
          }
        } elseif ($key == 'title') {
          if ($existing_node_vals[$key] != Html::decodeEntities(Xss::filter($values['title']))) {
            $diff = FALSE;
            break;
          }
        } else {
          if (trim($values[$key]) != trim($existing_node_vals[$key])) {
            $diff = FALSE;
            break;
          }
        }
      }
      if ($diff) {
	//changed from old field_s_no to new field_orp_nr      
	$this->ignored[] = $node->get('field_orp_nr')->value;
        //$this->ignored[] = $node->get('field_s_no')->value;
        // Nothing to do when there are no changes for the node. so skipping the node.
        return TRUE;
      }
      $problem_node = $node;
      $nodeExists = TRUE;
    }
    else {
      $problem_node = Node::create([
        'uid' => 1,
        'type' => 'problem',
        'created' => \Drupal::time()->getRequestTime(),
  //changed from old field_s_no to new field_orp_nr
	'field_orp_nr' => $values['sno'],       
	//'field_s_no' => $values['sno'],
      ]);

    }
    $problem_node->setTitle(Html::decodeEntities(Xss::filter($values['title'])));
    $problem_node->set('status', 1);
    $problem_node->set('body', array(
      'summary' => '',
      'value' => check_markup($values['body'], 'plain_text'),
      'format' => 'plain_text',
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
    $problem_node->set('field_attachment', Html::escape($values['creator']));
    $problem_node->set('field_comments', array(
        'value' => check_markup($values['comment'],'plain_text'),
        'format' => 'plain_text',
      )
    );
    $problem_node->set('field_services', array(
        'target_id' => $values['service'],
      )
    );
    $problem_node->set('field_diagnose', $values['diagnose']);
    $problem_node->set('field_eroffnet', $values['created']);
    $problem_node->set('field_function', $values['function']);
    $problem_node->set('field_problem_symptoms', $values['symptoms']);
    $problem_node->set('field_priority', $values['priority']);
    $problem_node->set('field_problem_eroffnet', $eroffnet);
    $problem_node->set('field_problem_status', $values['status']);
    $problem_node->set('field_processing', $values['last_update']);
    $problem_node->set('field_release', $values['release']);
    // $problem_node->set('field_sdcallid', $values['sdcallid']);.
    $problem_node->set('field_solution', array(
      'value' => check_markup($values['solution'],'plain_text'),
      'format' => 'plain_text',
    ));
    // $problem_node->set('field_s_no', $values['sno']);
    // $problem_node->set('field_release', $values['release']);.
    $problem_node->set('field_task_force', array(
      'value' => check_markup($values['taskforce'],'plain_text'),
      'format' => 'plain_text',
    ));
    $problem_node->set('field_proactive_creation', $values['proactive_creation']);
    $problem_node->set('field_interface_affected', $values['interface_affected']);
    $problem_node->set('field_unlocated_cause', $values['unlocated_cause']);
    $problem_node->set('field_part_of_lueafp', $values['part_of_lueafp']);
    // $problem_node->set('field_release', $values['release']);
    // $problem_node->set('field_ticketstore_count', $values['ticketstore_count']);
    // $problem_node->set('field_release', $values['release']);
    $problem_node->set('field_ticketstore_link', $values['ticketstore_link']);
    $problem_node->set('field_ticketstore_count', $values['ticketstore_count']);
    // $problem_node->set('field_timezone', $values['timezone']);.
    $problem_node->set('field_version', $values['version']);
    $problem_node->set('field_work_around', array(
      'value' => check_markup($values['workaround'], 'plain_text'),
      'format' => 'plain_text',
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
          'label' => Html::decodeEntities(Xss::filter($values['title'])),
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
