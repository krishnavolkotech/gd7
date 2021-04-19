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
    'databases' => false,
    'add-drop-database' => false,
    'hex-blob' => true
    );

$dump = new IMysqldump\Mysqldump("mysql:host=localhost;dbname=bpktst", "bputst", "bputst", $dumpSettings);

$dump->setTransformTableRowHook(function ($tableName, array $row) {
  include_once(dirname(__FILE__) .'/anonymize-tables-list.php');
  $anonymize_tables = anonymize_tables_list();

  if (array_key_exists($tableName, $anonymize_tables)) {
      if (isset($row['nid'])) {
        $ent_id = $row['nid'];
        $type = isset($row['type'])?$row['type']:'';
      }
      else if (isset($row['entity_id'])) {
        $ent_id = $row['entity_id'];
        $type = isset($row['bundle'])?$row['bundle']:'';
      }
      else {
        $ent_id = '';
        $type = '';
      }

     $replace = $ent_id .' : '. $type . " : Lorum Ipsum Lorum Ipsum ";
     
     if ($tableName == 'node_field_data') {
         if ($row['type'] == 'services' || $row['type'] == 'group') {
             //$replace = $row['title']; 
         }
         else {
             $row['title'] = $replace; 
         }
     }
     else if ($tableName == 'path_alias') {
         if (strpos($row['path'], '/user/') !== false) {
             $row['alias'] = '';
	 }
     }   
     else if ($tableName == 'users_field_data') {
         $row['name'] = "user_".$row['uid'];
         $row['pass'] = "asdasdaewdyytrg523rg425vowOPdWHJaad";
         $row['mail'] = "user_".$row['uid'].'@test.com';
     }
     else {
         foreach($anonymize_tables[$tableName]['columns'] as $säule) {
             $row[$säule['column']] = ($säule['replace'] != ''?$säule['replace']:$replace);
         }
     }
  }
  return $row;
});

$dump->start("anonymized-backup-${date}.sql");

?>
