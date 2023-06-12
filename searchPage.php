<?php
/**************************************
** Recherche dans les archives       **
** (c) 2023 MeNearly@gmail.com GPL2  **
***************************************/
/**********
** V1.2c **
***********/
require_once 'functions.php';
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="fr" dir="ltr">
  <head>
    <meta charset="utf-8">
    <link rel="stylesheet" type="text/css" href="css/mirc.css.php" />
    <link rel="stylesheet" type="text/css" href="css/tabs.css.php" />
    <title>🔍 Recherche</title>
    <script src='js/refresh.js.php' type="text/javascript"></script>
  </head>
  <body style="background-color:rgb(216, 250, 215)">
    <center>
      <div style="overflow-y: hidden;border: 1px solid #ccc;">
        <span style="font-weight:bold;float:right">
          <a href="index.php">Live</a>&nbsp;&nbsp;
          <a href="view.php">Archives</a>&nbsp;&nbsp;
          <a href="admin.php">Admin...</a>
        </span>

        <b>Recherche :</b>&nbsp;<input id="reString" type="text" value="" size=80 required /><br/>
        <small><i>pseudo ?</i>&nbsp;<input style="font:0.7em;font-family:courrier new" id="nick" type="text" value="" size=30 /></small><br/>
        <b>Salon :</b> <select id="channel" required>
<?php
    $channels=\bot\getAllowedChannels();
    foreach ($channels as $chan) {
      $chan=strtolower($chan);
?>
        <option value="<?=$chan?>"><?=$chan?></option>
<?php
    }
?>
        </select><br/>
        <input type="checkbox" id="caseSensitive" value=1>&nbsp;Sensible à la casse<br/>
        <small>⚠ Il s'agit d'une expression régulière, et même si vous ne savez pas bien l'utiliser, pour une recherche simple pensez à ajouter <b>\</b> devant au moins ces caractères spéciaux <b>" ' ( ) [ ] { } </b><i>et surtout (!)</i><b> |</b>. </small><br/>
        <input type="checkbox" id="fromStart" value=1>&nbsp;Depuis le début,&nbsp;&nbsp;<b>Horodatage</b>&nbsp;<input type="radio" name="order" id="asc" value="asc"> croissant&nbsp;&nbsp;<input type="radio" name="order" id="desc" value="desc" checked> décroissant<br/>
        <input type="number" id="limit" value=50 min=10 max=2000 size=5>&nbsp;résultats maximum&nbsp;&nbsp;
        <input type="checkbox" id="stripCodes" value=1>&nbsp;Ignorer le formatage
        <br/>
        <button class="button2" onclick="launchSearch()">Search !</button>&nbsp;&nbsp;<button class="button2" onclick="exportSearch()">💾</button>
      </div>
      <div id="result" class="tabcontent">
        <table style="border:1px solid;border-color:darkslateblue" width="100%">
          <thead>
            <tr>
              <th style="width:2vw"></th><th></th>
              <th style="width:78vw"></th>
            </tr>
          </thead>
          <tbody id="resultTab" class="messages">
          </tbody>
        </table>
      </div>
      <div class="floater" id="chansFloater" style="display:none"><span class="floater_h" onclick="document.documentElement.scrollTop=0">⤴</span><br/><span class="floater_h" onclick="document.documentElement.scrollTop=10000000">⤵</span></div>
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
  </body>
</html>

