<?php
/*************************************
**    Recherche dans les messages   **
** (c) 2023 ksynet.fr               **
** ian : MeNearly@gmail.com GPL2    **
**************************************/
/**********
** V1.2c **
***********/
namespace bot\search;
require_once 'functions.php';

set_error_handler(function ($err_msg) {
  throw new \Exception( $err_msg);
}, E_WARNING);

function search(string $server, string $channel, string $reg, string $nick_reg="", int $limit=0, bool $stripcodes=true, bool $fromStart=false, int $order=2): array {
// order : 1=ascending, 2=descending (default)
  $files=glob(\bot\archivesDir.DIRECTORY_SEPARATOR.$server."_[&#]".$channel."_*_msgs.*");
  if (!$fromStart) {
    $files=array_reverse($files);
  }
  $result=array();
  $i=0;
  $testNick=($nick_reg!="");
  $testMsg=($reg!="");
  foreach ($files as $fname) {
    if (!file_exists($fname)) {
      echo 'Fichier introuvable : '.$fname.PHP_EOL;
      echo "Erreur majeure, on sort.".PHP_EOL;
      die(1);
    } else {
      $msgs = \bot\messages\loadFile($fname,false);
      $messages = $msgs['messages'];
      if ($fromStart) {
        $messages = array_reverse($messages);
      }
      foreach($messages as $m) {
        $msg=$m['message'];
        if ($stripcodes) {
          $msg=\bot\stripControlCodes($m['message']);
        }
        $nick=$m['nick'];
        $tm=(($testMsg && preg_match($reg,$msg,$matches)) || !$testMsg);
        $tn=(($testNick && preg_match($nick_reg,$nick,$matches)) || ($testMsg && preg_match($reg,$nick,$matches)) || !$testNick);
        if ($tm && $tn) {
          $result[]=$m;
          if ($limit!=0 && (++$i)==$limit)
            break 2;
        }
      }
    }
  }
  if (($fromStart && $order==2) || (!$fromStart && $order==1))
    $result=array_reverse($result);

  return $result;
}

function createRE(string &$reg, bool $ci) {
  $reg=preg_replace("/¤¤¤/","+",$reg);
  $a=ord($reg[0]);
  if ($reg[0]!==$reg[-1] || ($a>47 && $a<58) || ($a>64 && $a<91) || ($a>96 && $a<123)) {
    // try delimiters...
    if (strpos($reg,"/")===false) {
      $reg='/'.$reg.'/';
    } elseif (strpos($reg,"@")===false) {
      $reg='@'.$reg.'@';
    } elseif (strpos($reg,"#")===false) {
      $reg='#'.$reg.'#';
    } elseif (strpos($reg,"~")===false) {
      $reg='~'.$reg.'~';
    }
  }
  if (!$ci) {
    $reg.='i';
  }
}

if (php_sapi_name()=="cli") {
  $cmd=array_shift($argv);
  $server=$argv[0]??"";
  $channel=$argv[1]??"";
  $reg=$argv[2]??"";
  $nick="";
  $casesensitive=boolval($argv[3]??"");
  $limit=intval($argv[4]??0);
  $stripcodes=boolval($argv[5]??1);
  $orderName="desc";
} else {
  $server=$_REQUEST['server']??"";
  $channel=$_REQUEST['chan']??"";
  $reg=$_REQUEST['reg']??"";
  $nick=trim($_REQUEST['nick']??"");
  $casesensitive=boolval($_REQUEST['case']??"");
  $limit=intval($_REQUEST['limit']??0);
  $fromStart=boolval($_REQUEST['fromStart']??0);
  $orderName=$_REQUEST['order']??"desc";
  $stripcodes=boolval($_REQUEST['strip']??1);
}
$order=($orderName=="asc"?1:($orderName=="desc"?2:0));
$export=boolval($_REQUEST['export']??false);
file_put_contents("reg.txt",$reg);
if ($export) {
  header("Content-Type: text/csv; charset=UTF-8");
  header('Content-Disposition: attachment; filename="'.$server.'_'.$channel.'_search.txt"');
}
if ($server=="" || $channel=="" || ($reg=="" && $nick=="") ||$order==0) {
  if (php_sapi_name()=="cli") {
    echo "Usage : $cmd Server Channel STRING CaseSensitive limit [stripcodes (0 or 1)]".PHP_EOL;
    echo "STRING must be a well-formed regexp".PHP_EOL;
    echo "Be careful, do not use either # nor & for the channel name,".PHP_EOL;
    echo "and both channel and server are case sensitive !".PHP_EOL;
    echo "CaseSensitive MUST be specified (0 or 1)".PHP_EOL;
    echo "limit is the number of matching messages, must be specified, but if omitted, as stripcodes, there is no limit...".PHP_EOL;
    echo "stripcodes is a boolean flag (0 or 1, default is 1), which allows text formatting search".PHP_EOL;
    die(1);
  } else {
    echo "<tr><td colspan=3><span style='color:red'>ERREUR !<br/>".PHP_EOL;
    echo "reg=$reg&nbsp;nick=$nick</span>".PHP_EOL;
    echo "</td><tr>".PHP_EOL;
    die(1);
  }
}
if ($reg!="")
  createRE($reg,$casesensitive);
if ($nick!="")
  createRE($nick,$casesensitive);
try {
  if ($reg!="")
    preg_match($reg,"Useless testing string",$matches);
  if ($nick!="")
    preg_match($nick,"Useless testing string",$matches);
} catch (\Exception $e) {
  if (php_sapi_name()=="cli") {
    echo "Usage : $cmd Server Channel STRING CaseSensitive [stripcodes (0 or 1)]".PHP_EOL;
    echo "STRING is not a well-formed regexp...".PHP_EOL;
    echo "Provided : $reg".PHP_EOL;
    die(1);
  } else {
    echo "<tr><td colspan=3><span style='color:red'><b>Erreur de recherche : </b></span><i>$reg</i> ou <i>$nick</i> n'est pas une expression régulière valide...</td></tr>";
    die();
  }
}

$messages=search($server, $channel, $reg, $nick, $limit, $stripcodes, $fromStart, $order);
restore_error_handler();

$search=($reg!=""?"'$reg'":"").($nick!=""?" par '$nick'":"");
if ($export) {
  echo "Recherche de messages $search, depuis l".($fromStart?"e début":"a fin").", par date ".($order==2?"dé":"")."croissante, $limit résultats maximum.".PHP_EOL;
  echo count($messages)." résultats.".PHP_EOL;
} else {
  echo "<tr><td align='center' colspan=3>Recherche de messages $search, depuis l".($fromStart?"e début":"a fin").", par date ".($order==2?"dé":"")."croissante, $limit résultats maximum. ".count($messages)." lignes.<br/>";
}

if (count($messages)==0) {
  echo "<tr><td colspan='3' style='text-align:center;color:darkred'>Aucun message</td></tr>";
} else {
  if (php_sapi_name()=="cli" || $export) {
    foreach($messages as $m) {
      echo date('d/m/Y H:i:s', $m['timestamp'])." | ".\bot\stripControlCodes($m['nick'])." | ".\bot\stripControlCodes($m['message']).PHP_EOL;
    }
  } else {
    foreach($messages as $m) {
      echo "<tr>".PHP_EOL;
      echo "<td class='tabline_date'>".date('d/m/Y H:i:s', $m['timestamp'])."</td>".PHP_EOL;
      echo "<td class='tabline_nick'>".\bot\irc2html($m['nick'])."</td>".PHP_EOL;
      echo "<td class='tabline_msg'>".\bot\irc2html($m['message'])."</td>".PHP_EOL;
      echo "</tr>".PHP_EOL;
    }
  }
}
