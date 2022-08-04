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
require_once('functions.php');

$channel=$_REQUEST['channel']??"";
$channel=preg_replace('/@@@/','+',$channel);
$channel=preg_replace('/%@@/','#',$channel);

$date_ft=$_REQUEST['date']??"";
// Today && Yesterday !
$date=new \DateTime();
$today_dsp=$date->format("d/m/Y");
$date->setTimestamp($date->getTimestamp()-86400);

if ($date_ft=="") {
  $date_ft=$date->format("Y_m_d");
  $date_dsp=$date->format("d/m/Y");
} else {
  $given_date=\DateTime::createFromFormat("Y_m_d",$date_ft);
  $date_dsp=$given_date->format("d/m/Y");
  // prevent from today
  if ($date_dsp==$today_dsp) {
    $date_dsp=$date->format("d/m/Y");
    $date_ft=$date->format("Y_m_d");
  } else {
    $date_ft=$given_date->format("Y_m_d");
  }
}
if ($channel=="") {
  $channel=substr(\bot\channels[0],1);
}

header("Content-Type: text/csv; charset=UTF-8");
header('Content-Disposition: attachment; filename="'.$channel."_".$date_ft.'.txt"');

$filename=\bot\messages\getFilename($channel."_".$date_ft,true);
if (!file_exists($filename)) {
  echo 'Aucun message pour #'.$channel.' au '.$date_dsp.'...'.PHP_EOL;
} else {
  echo 'Messages pour #'.$channel.' au '.$date_dsp.' :'.PHP_EOL;
  $msgs = \bot\messages\loadFile($filename,false);
  $messages = $msgs['messages'];
  $messages = array_reverse($messages);
  foreach($messages as $m) {
    echo date('H:i:s', $m['timestamp'])." | ".\bot\stripControlCodes($m['nick'])." | ".\bot\stripControlCodes($m['message']).PHP_EOL;
  }
}
