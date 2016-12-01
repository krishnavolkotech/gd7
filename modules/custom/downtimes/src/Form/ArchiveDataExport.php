<?php

namespace Drupal\downtimes\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ArchiveDataExport.
 *
 * @package Drupal\downtimes\Form
 */
class ArchiveDataExport extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'archive_data_export';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['text'] = [
      '#type' => 'item',
      '#markup' => $this->t('Please Specify a time period. The export csv file will contain all archived maintenances that started during that time period.'),
    ];
    $form['from'] = [
      '#type' => 'datelist',
      '#title' => $this->t('From'),
      '#date_year_range' => '2009:now',
      '#date_part_order' => array(
        'year',
        'month',
        'day'
      ),
    ];
    $form['to'] = [
      '#type' => 'datelist',
      '#title' => $this->t('To'),
      '#date_year_range' => '2009:now',
      '#date_part_order' => array(
        'year',
        'month',
        'day'
      ),
    ];


    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $from_date = $to_date = '';
    if ($form_state->getValue('from')) {
      $from_date = $form_state->getValue('from');
    }
    if ($form_state->getValue('to')) {
      $to_date = $form_state->getValue('to');
    }

    if ($from_date && $to_date) {
      $from = strtotime($from_date);
      $to = strtotime($to_date);
      if ($from > $to) {
        $form_state->setErrorByName('to', t("End Date must be greater than Start Date."));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $group_node = 'group_node';
    $from = $form_state->getValue('from');
    $to = $form_state->getValue('to');

    if ($from) {
      $from_date = strtotime($from);
      $from = 'ds.startdate_planned >= ' . $from_date . ' and ';
      $filename_from = "_" . date('Ymd', $from_date);
    }
    else {
      $from = '';
    }

    if ($to) {
      //Adding offset 23:59:59 for end date.
      $to_date = strtotime($to) + 86399;
      $nonresolve_to = 'ds.enddate_planned <= ' . $to_date . ' and ';
      $to = 'ri.end_date <= ' . $to_date . ' and ';
      $filename_to = "_" . date('Ymd', $to_date);
    }
    else {
      $to = '';
      $nonresolve_to = '';
    }

    if ($from && !$to) {
      $filename_from = "_" . t("from");
      $filename_to = "_" . date('Ymd', $from_date);
    }
    if (!$from && $to) {
      $filename_from = "_" . t("to");
      $filename_to = "_" . date('Ymd', $to_date);
    }
    if (!$from && !$to) {
      $filename_from = "";
      $filename_to = "_" . date('Ymd', time());
    }

    // Query to get the values from given user inputs.
    $query = 'SELECT group_concat(DISTINCT s.abbr SEPARATOR\', \') AS abbr,
          ds.startdate_planned,
          ds.enddate_planned,
          ri.end_date,
          n.created,
          n.uid,
          ds.service_id AS service_id,
          ds.status,
          ds.description,
          ds.reason
          FROM      {downtimes} ds,
                    {group_content_field_data} gcfa,
                    {resolve_cancel_incident} ri,
                    {node_field_data} n,
                    {states} s
          WHERE     ds.downtime_id = gcfa.entity_id
          AND  gcfa.type like \'%' . $group_node . '%\'
          AND n.type = \'downtimes\'
          AND ' . $from . $to . '
          s.id = ds.state_id
          AND       n.nid = ds.downtime_id
          AND       ds.scheduled_p = 1
          AND       ds.downtime_id  = ri.downtime_id
          AND       ri.type = 1
          AND       ds.resolved = 1
          AND  gcfa.entity_id = n.nid
          GROUP BY 
          n.nid, s.abbr,ds.startdate_planned,ds.enddate_planned,
          ri.end_date,n.created,n.uid,ds.service_id,
          ds.status, ds.description,ds.reason ORDER BY  ds.startdate_planned ASC ';

    $result = db_query($query)->fetchAll();
    //CSV file headers.
    $result2[] = array('Betroffenes Land', 'Beginn', 'Voraussichtliches Ende', 'Tatsaechliches Ende', 'Gemeldet am', 'Gemeldet von', 'Verfahren', 'Status', 'Grund', 'In Wartungsfenster', 'Beschreibung');
    foreach ($result as $result1) {

      $result1 = (array) $result1;
      $service_id = explode(',', $result1['service_id']);

      // Getting service names from service IDs.
      $title = array();
      foreach ($service_id as $key => $value) {
        $query = "select title from {node_field_data} where nid = ?";
        $title[] = db_query($query, array($value))->fetchField();
      }
      $result1['uid'] = db_query("select abbr from {node_field_data} n, {cust_profile} p, {states} s where n.uid = p.uid and state_id = id and n.uid = ?", array($result1['uid']))->fetchField();
      // Converting timestamps to Date format.
      $result1['service_id'] = implode(',', $title);
      $result1['created'] = date('d.m.Y - H:i', $result1['created']);
      $result1['startdate_planned'] = date('d.m.Y - H:i', $result1['startdate_planned']);
      $result1['enddate_planned'] = date('d.m.Y - H:i', $result1['enddate_planned']);
      $result1['end_date'] = date('d.m.Y - H:i', $result1['end_date']);
      //if($result1['status'] == 'R' || $result1['reason'] == 6) {
      if (empty($result1['reason']) || $result1['reason'] == 6) {
        $result1['within_mw'] = 1;
      }
      else {
        $result1['within_mw'] = 0;
      }
      $description = $result1['description'];
      $result1['description'] = \Drupal\Core\Mail\MailFormatHelper::htmlToText($description);
      if ($result1['reason'] > 0) {
        #$result1['reason'] = get_reason_text($result1['reason']);
      }
      else {
        $result1['reason'] = '';
      }

      // Making individual array to single array.
      $result2[] = $result1;
    }

//    // non resolved maintenances
//    $query1 = 'SELECT group_concat(DISTINCT s.abbr SEPARATOR\', \') AS abbr,
//          ds.startdate_planned,
//          ds.enddate_planned,
//          ds.enddate_planned AS end_date,
//          n.created,
//          n.uid,
//          ds.service_id AS service_id,
//          ds.status,
//          ds.description,
//          ds.reason
//          FROM      {downtimes} ds,
//                    {group_content_field_data} oa,
//                    {node_field_data} n,
//                    {states} s
//          WHERE     ds.downtime_id = oa.entity_id
//          AND       ' . $from . $nonresolve_to . 'n.type = \'downtimes\'
//          AND       n.nid = ds.downtime_id
//          AND       s.id = ds.state_id
//          AND       ds.scheduled_p = 1
//          AND       ds.resolved = 1
//          AND       ds.cancelled != 1
//          AND  oa.type like \'%' . $group_node . '%\'
//          AND  oa.entity_id = n.nid
//          GROUP BY  n.nid,s.abbr,ds.startdate_planned,ds.enddate_planned,ds.enddate_planned,
//                    n.created,n.uid,ds.service_id,ds.status,ds.description,ds.reason
//          ORDER BY  ds.startdate_planned ASC';
//    $result = db_query($query1)->fetchAll();
//
//    foreach ($result as $result3) {
//      $result3 = (array) $result3;
//      $service_id = explode(', ', $result3['service_id']);
//
//      // Getting service names from service IDs.
//      $title = array();
//      foreach ($service_id as $key => $value) {
//        $query = "select title from {node_field_data} where nid = ?";
//        $title[] = db_query($query, array($value))->fetchField();
//      }
//      $result3['uid'] = db_query("select abbr from {node_field_data} n, {cust_profile} p, {states} s where n.uid = p.uid and state_id = id and n.uid = ?", array($result3['uid']))->fetchField();
//      // Converting timestamps to Date format.
//      $result3['service_id'] = implode(', ', $title);
//      $result3['created'] = date('d.m.Y - H:i', $result3['created']);
//      $result3['startdate_planned'] = date('d.m.Y - H:i', $result3['startdate_planned']);
//      $result3['enddate_planned'] = date('d.m.Y - H:i', $result3['enddate_planned']);
//      $result3['end_date'] = date('d.m.Y - H:i', $result3['end_date']);
//      //if($result3['status'] == 'R' || $result3['reason'] == 6) {
//      if (empty($result3['reason']) || $result3['reason'] == 6) {
//        $result3['within_mw'] = 1;
//      }
//      else {
//        $result3['within_mw'] = 0;
//      }
//      $description = $result3['description'];
//      unset($result3['description']);
//      $result3['description'] = \Drupal\Core\Mail\MailFormatHelper::htmlToText($description);
//      if ($result3['reason'] > 0) {
//        #$result3['reason'] = get_reason_text($result3['reason']);
//      }
//      else {
//        $result3['reason'] = '';
//      }
//   //   $result3['nid'] = $result3['nid'];
//      // Making individual array to single array.
//      $result2[] = $result3;
//    }
//    print_r($result2);  exit;
    
    $filename = "geplante_blockzeiten" . $filename_from . $filename_to . ".csv";
    self::array_to_csv_download($result2, $filename);
  }

  function array_to_csv_download($array, $filename = "geplante_blockzeiten.csv", $delimiter = ";") {
   //  echo '<pre>';  print_r($array);    exit();
    // open raw memory as file so no temp files needed, you might run out of memory though
    $f = fopen('php://memory', 'w');

    // Excel does not recognize multirow cells properly unless delimter is a semicolon so need to force it
    $delimiter = ";";
    // Excel does not recognize UTF8 csv files properly so need to explicitly write BOM.
    // http://stackoverflow.com/questions/5601904/encoding-a-string-as-utf-8-with-bom-in-php
    fwrite($f, chr(239) . chr(187) . chr(191));

    // loop over the input array
    foreach ($array as $line) {
      // generate csv lines from the inner arrays
      #$line = mb_convert_encoding($line, 'UTF-16LE');
      fputcsv($f, $line, $delimiter);
    }
    // rewrind the "file" with the csv lines
    fseek($f, 0);
    // tell the browser it's going to be a csv file
    header('Content-Type: application/csv; charset=UTF-8');
    // tell the browser we want to save it instead of displaying it
    header('Content-Disposition: attachement; filename="' . $filename . '"');
    // make php send the generated csv lines to the browser
    fpassthru($f);
    exit;
  }

}
