<?php
/*************************************
** Visualisation des logs archivées **
** (c) 2020 MeNearly@gmail.com GPL       **
**************************************/
/**********
** V1.1b **
***********/
require_once 'functions.php';
$today=new \DateTime();
$today_ft=$today->format("Y_m_d");
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
       var tc;
     </script>
<?php
    $date=$_GET['date']??"";
    if ($date=="") {
      $date_dt=NULL;
    } else {
      $date_dt=\DateTime::createFromFormat("Y_m_d",$date);
    }
    $isToday=($date==$today_ft);

// Tab links
?>
      <div class="tabView">
<?php
    $channels=\bot\getAllowedChannels();
    foreach ($channels as $chan) {
      $chan=strtolower($chan);
?>
        <button class="tabView" onclick="openChannelTab(event, '<?=$chan?>')" id="<?=$chan?>Button"><?=$chan?></button>
<?php
    }
?>
        <span style="font-weight:bold;float:right"><a href="index.php">Live</a></span>
      </div>
<?php
    foreach ($channels as $chan) {
      $chan=strtolower($chan);
?>
      <div id="<?=$chan?>" class="tabcontent">
        <table style="border:1px solid;border-color:darkslateblue;table-layout:fixed" width="100%">
          <thead>
            <tr>
              <th style="width:2vw"></th><th></th>
              <th style="width:78vw">Channel <?=$chan?> du <span id="<?=$chan?>_date_lbl"><?=($date_dt?$date_dt->format("d/m/Y"):"")?></span>&nbsp;&nbsp;
  <!-- ATTENTION CARACTÈRES PARFOIS INVISIBLES POUR LES BOUTONS (suivant l'éditeur de texte) -->
              <input type="date" value="" id="<?=$chan?>_date" onchange="changeDate(event,'<?=$chan?>')" />

              <button id="<?=$chan?>RefreshBtn" style="display:none" class="clickable" onclick="changeDate(event,'<?=$chan?>');"><span style="font-size:12pt;color:blue;text-weight:bold">🗘</span></button>
              <!-- Export button only for other dates than today -->
              <button id="<?=$chan?>ExportBtn" style="display:none" class="clickable" onclick="exportDate(event,'<?=$chan?>',document.getElementById('<?=$chan?>_date').value);"><span style="font-size:12pt;color:blue;text-weight:bold">💾</span></button>
             </th></tr>
          </thead>
          <tbody id="<?=$chan?>Tab" class="messages">
<?php
      $filename=\bot\messages\getFilename($chan."_".$date,true,$isToday);
      if (!file_exists($filename)) {
?>
            <tr><td colspan=3 align="center"><span style="color:darkred">Aucun message pour <?=$chan.($date_dt?($isToday?"aujourd'hui":" au ".$date_dt->format("d/m/Y")):"")?></span></td></tr>
<?php
      } else {
?>
<?php
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
      <div class="floater" id="chansFloater"><span class="floater_h" onclick="document.documentElement.scrollTop=0">⤴</span><br/><span class="floater_h" onclick="document.documentElement.scrollTop=10000000">⤵</span></div>
      <script type="text/javascript">
        window.onscroll=function() {
          let f=document.getElementById("chansFloater");
          var winScrollTop = document.documentElement.scrollTop;
          var winHeight = window.innerHeight;
          var floaterHeight = Number.parseInt(window.getComputedStyle(f).height);
          var top = winScrollTop + Math.floor((winHeight - floaterHeight)/2);
          f.style.top=top + 'px';
        };
      </script>
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
      let lines=document.getElementsByClassName("tabline_msg");
      if (lines.length==0) {
        let floater=document.getElementById("chansFloater");
        floater.style.display="none";
      }
      let rlines=document.getElementsByClassName("Xreverse");
      for (let i=0;i<rlines.length;i++) {
        reverseVideo(rlines[i]);
      }
      channelTab.scrollTop=isToday?1000000:0; /* today? 1000000, else => from start */
    </script>
  </body>
</html>

