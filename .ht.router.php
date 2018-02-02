<?php
/**
 * @file
 * Handle routing using PHP Server.
 */

$index_file = NULL;
$current_directory = $_SERVER['DOCUMENT_ROOT'];

// Look for index file on parent directories.
while ($current_directory != '/' || $index_file === NULL) {
  if (file_exists($current_directory . DIRECTORY_SEPARATOR . 'index.php')) {
    $index_file = $current_directory . DIRECTORY_SEPARATOR . 'index.php';
  }
  $current_directory = dirname($current_directory);
}

if ($index_file === NULL) {
  echo "Document root and index file not found\n";
}

if (preg_match("/\.(engine|inc|info|install|make|module|profile|test|po|sh|.*sql|theme|tpl(\.php)?|xtmpl)/", $_SERVER["REQUEST_URI"])) {

  // File type is not allowed.
  print "Error\n";
}
elseif (preg_match("/(^|\/)\./", $_SERVER["REQUEST_URI"])) {
  return FALSE;
}
elseif (file_exists($_SERVER["DOCUMENT_ROOT"] . $_SERVER["SCRIPT_NAME"])) {
  return FALSE;
}
else {
  $_GET["q"] = $_SERVER["REQUEST_URI"];
  include $index_file;
}
?>