<?php
/**************************************
** Visualisation des chans en direct **
** (c) 2022 MeNearly@gmail.com GPL   **
***************************************/
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
    <title>Channels</title>
    <script src='js/refresh.js.php' type="text/javascript"></script>
  </head>
  <body style="background-color:<?=\bot\colors\white?>">
   <center>
<?php
// Tab links
    echo '<div class="tab">'.PHP_EOL;
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
    <table style="border:1px solid;border-color:darkslateblue" width="100%">
      <thead>
        <tr><th colspan=3>Channel <?=$chan?></th></tr>
      </thead>
      <tbody id="<?=$chan?>Tab" class="messages">
<?php
      $filename=\bot\messagesDir.$chan.'_msgs.json';
      if (!file_exists($filename)) {
        echo '      <tr><td><span style="color:darkred">Aucun message pour '.$chan.'</span></td></tr>'.PHP_EOL;
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
    refreshAll(true);
    startRefresh(interval);
    document.getElementById('<?=strtolower($channels[0])?>'+'Button').click();
  </script>
  </body>
</html>

