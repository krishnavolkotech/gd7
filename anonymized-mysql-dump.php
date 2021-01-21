<?php
error_reporting(E_ALL);

require_once __DIR__ . '/vendor/autoload.php';
include_once(dirname(__FILE__) .'/vendor/ifsnop/mysqldump-php/src/Ifsnop/Mysqldump/Mysqldump.php');


use Ifsnop\Mysqldump as IMysqldump;

$date = date('Ymd');
$dumpSettings = array(
    'compress' => IMysqldump\Mysqldump::NONE,
    'no-data' => false,
    'add-drop-table' => true,
    'single-transaction' => true,
    'lock-tables' => true,
    'add-locks' => true,
    'extended-insert' => true,
    'disable-foreign-keys-check' => true,
    'skip-triggers' => false,
    'add-drop-trigger' => true,
    'databases' => true,
    'add-drop-database' => true,
    'hex-blob' => true
    );

$dump = new IMysqldump\Mysqldump("mysql:host=localhost;dbname=hzdupgrd_dev", "hzdupgrd_dev", "HzDgrd!deVv#0026", $dumpSettings);

$dump->setTransformTableRowHook(function ($tableName, array $row) {
  include_once(dirname(__FILE__) .'/anonymize-tables-list.php');
  /*
   $anonymize_tables = array(
    'node_field_data' => array('column' => 'title'),
    'node__body' => array('column' => 'body_value'),
    'node__field_abnormality_description' => array('column' => 'field_abnormality_description_value'),
    'node__field_installation_duration' => array('column' => 'field_installation_duration_value'),
    'node__field_custom_title' => array('column' => 'field_custom_title_value'),
    'node__field_related_transfer_number' => array('column' => 'field_related_transfer_number_value')
  );
  */
  $anonymize_tables = anonimize_tables_list();

  if (array_key_exists($tableName, $anonymize_tables)) {
      if (isset($row['nid'])) {
        $ent_id = $row['nid'];
        $type = $row['type'];
      }
      else if (isset($row['entity_id'])) {
        $ent_id = $row['entity_id'];
        $type = $row['bundle'];
      }
      else {
        $ent_id = '';
        $type = '';
      }

     $replace = $ent_id .' : '. $type . " : Lorum Ipsum Dummy content ";

     if ($tableName == 'node_field_data') {
       if ($row['type'] == 'services' || $row['type'] == 'group') {
          $replace = $row['title']; 
       }
     }
     $row[$anonymize_tables[$tableName]['column']] = $replace;
   }
   return $row;
});

$dump->start("anonymized-backup-${date}.sql");

?>
