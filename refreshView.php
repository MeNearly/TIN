<?php
/*****************************************
****  Récupération des messages    *******
** (c) 2020-2022 MeNearly@gmail.com GPL **
******************************************/
/**********
** V1.1b **
***********/
namespace bot;
require_once 'functions.php';

$channel=$_REQUEST['channel']??"";
$date_ft=$_REQUEST['date']??"";
if ($date_ft=="") {
  $date=new \DateTime();
  $date_ft=$date->format("Y_m_d");
  $date_dsp=$date->format("d/m/Y");
} else {
  $date=\DateTime::createFromFormat("Y_m_d",$date_ft);
  $date_dsp=$date->format("d/m/Y");
}
$today=new \DateTime();
$today_ft=$today->format("Y_m_d");
$isToday=($date_ft==$today_ft);

if ($channel=="") {
  $channel=strtolower(\bot\channels[0]);
}
$channel=preg_replace('/@@@/','+',$channel);
$channel=preg_replace('/%@@/','#',$channel);
header("Content-Type: text/HTML; charset=UTF-8");

$filename=\bot\messages\getFilename($channel."_".$date_ft, true, $isToday); /* $isToday means $nozip==true */
if (!file_exists($filename)) {
  echo '      <tr><td><span style="color:darkred">Aucun message pour '.$channel.' au '.$date_dsp.'...</span></td></tr>'.PHP_EOL;
} else {
  $msgs = \bot\messages\loadFile($filename,false);
  $messages = $msgs['messages'];
  $messages = array_reverse($messages);
  foreach($messages as $m) {
    echo "<tr>".PHP_EOL;
    echo "<td class='tabline_date'>".date('H:i:s', $m['timestamp'])."</td>".PHP_EOL;
    echo "<td class='tabline_nick'>".\bot\irc2html($m['nick'])."</td>".PHP_EOL;
    echo "<td class='tabline_msg'>".\bot\irc2html($m['message'])."</td>".PHP_EOL;
    echo "</tr>".PHP_EOL;
  }
}
