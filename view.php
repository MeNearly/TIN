<?php
/*************************************
** Visualisation des logs archivées **
** (c) 2020 MeNearly@gmail.com GPL       **
**************************************/
/**********
** V1.1b **
***********/
require_once("functions.php");
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="fr" dir="ltr">
  <head>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="css/mirc.css.php" />
    <link rel="stylesheet" type="text/css" href="css/tabs.css.php" />
    <title>Saved channels</title>
    <script src='js/refresh.js.php' type="text/javascript"></script>
  </head>
  <body style="background-color:<?=\bot\colors\lightcyan?>">
   <center>
   <script type="text/javascript">
     document.body.style.cursor='wait';
   </script>
<?php
    $date=$_GET['date']??"";
    if ($date=="") {
      $date_dt=NULL;
    } else {
      $date_dt=\DateTime::createFromFormat("Y_m_d",$date);
    }
// Tab links
    echo '<div class="tabView">'.PHP_EOL;
    $channels=\bot\getAllowedChannels();
    foreach ($channels as $chan) {
      $chan=strtolower($chan);
      echo '<button class="tablinks" onclick="openChannelTab(event, \''.$chan.'\')" id="'.$chan.'Button">'.$chan.'</button>'.PHP_EOL;
    }
    echo "</div>".PHP_EOL;
    foreach ($channels as $chan) {
      $chan=strtolower($chan);
?>
    <div id="<?=$chan?>" class="tabcontent">
    <table style="border:1px solid;border-color:darkslateblue;table-layout:fixed" width="100%">
      <thead>
        <tr><th></th><th></th>
        <th style="width:85vw">Channel <?=$chan?> du <span id="<?=$chan?>_date_lbl"><?=($date_dt?$date_dt->format("d/m/Y"):"")?></span>&nbsp;&nbsp;
<!-- ATTENTION CARACTÈRES PARFOIS INVISIBLES POUR LES BOUTONS (suivant l'éditeur de texte) -->
          <input type="date" value="" id="<?=$chan?>_date" onchange="changeDate(event,'<?=$chan?>')" />

          <button id="<?=$chan?>RefreshBtn" style="display:none" class="clickable" onclick="changeDate(event,'<?=$chan?>');"><span style="font-size:12pt;color:blue;text-weight:bold">🗘</span></button>
          <!-- Export button only for other dates than today -->
          <button id="<?=$chan?>ExportBtn" style="display:none" class="clickable" onclick="exportDate(event,'<?=$chan?>',document.getElementById('<?=$chan?>_date').value);"><span style="font-size:12pt;color:blue;text-weight:bold">💾</span></button>
         </th></tr>
      </thead>
      <tbody id="<?=$chan?>Tab" class="messages">
<?php
      $filename=\bot\messages\getFilename($chan."_".$date,true);
      if (!file_exists($filename)) {
        echo '      <tr><td><span style="color:darkred">Aucun message pour '.$chan.($date_dt?" au ".$date_dt->format("d/m/Y"):"").'</span></td></tr>'.PHP_EOL;
      } else {
        $msgs = \bot\messages\loadFile($filename,false);
        $messages = $msgs['messages'];
        $messages = array_reverse($messages);
        foreach($messages as $m) {
?>
        <tr>
          <td class='tabline_date'><?=date('H:i:s', $m['timestamp'])?></td>
          <td class='tabline_nick'><?=\bot\irc2html($m['nick'])?></td>
          <td class='tabline_msg'><?=\bot\irc2html($m['message'])?></td>
        </tr>
<?php
        }
      }
?>
      </tbody>
    </table>
    </div>
<?php
    }
?>
   </center>
   <script type="text/javascript">
     document.body.style.cursor='default';
     document.getElementById('<?=strtolower($channels[0])?>Button').click();
     let channelTab=document.getElementById('<?=strtolower($channels[0])?>Tab');
     let chanTabDate=document.getElementById('<?=strtolower($channels[0])?>_date');
     let isToday=false;
     if (((new Date()).getTime()-Date.parse(chanTabDate.value))<86400000) {
       isToday=true;
     }
     channelTab.scrollTop=isToday?1000000:0; /* today? 1000000, else => from start */
   </script>
  </body>
</html>

