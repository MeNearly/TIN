#!/usr/bin/php
<?php
/***********************************
**    Exportation des messages    **
** (c) 2020-2022 Xylian.fr        **
** ian : MeNearly@gmail.com GPL   **
************************************/
/**********
** V1.1b **
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
  die();
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
