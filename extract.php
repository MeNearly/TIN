#!/usr/bin/php
<?php
/***********************************
**    Exportation des messages    **
** (c) 202x ian/KsyNET            **
** ian : MeNearly@gmail.com GPL2  **
************************************/
/**********
** V1.2c **
***********/
namespace bot;
require_once 'functions.php';

if (php_sapi_name()!=="cli") {
  die("Only on console");
}
$cmd=array_shift($argv);
$filename=$argv[0]??"";

if ($filename=="") {
  echo "Usage : $cmd FILENAME".PHP_EOL;
  die(1);
}

if (!file_exists($filename)) {
  echo 'Fichier introuvable : '.$filename.PHP_EOL;
} else {
  $msgs = \bot\messages\loadFile($filename,false);
  $messages = $msgs['messages'];
  $messages = array_reverse($messages);
  foreach($messages as $m) {
    echo date('H:i:s', $m['timestamp'])." | ".\bot\stripControlCodes($m['nick'])." | ".\bot\stripControlCodes($m['message']).PHP_EOL;
  }
}
