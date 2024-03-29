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
        </span>

        <b>Recherche :</b>&nbsp;<input id="reString" type="text" value="" size=80 onkeypress="let nick=document.getElementById('nick');let btn1=document.getElementById('search_btn');let btn2=document.getElementById('save_btn');if (this.value!='' || nick.value!='') {btn1.disabled=false;btn2.disabled=false;} else {btn1.disabled=true;btn2.disabled=true;}"/><br/>
        <small><i>pseudo ?</i>&nbsp;<input style="font:0.7em;font-family:courrier new" id="nick" type="text" value=""  onkeypress="let re=document.getElementById('reString');let btn1=document.getElementById('search_btn');let btn2=document.getElementById('save_btn');if (this.value!='' || re.value!='') {btn1.disabled=false;btn2.disabled=false;} else {btn1.disabled=true;btn2.disabled=true;}" size=30 /></small><br/>
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
        <input type="checkbox" id="fromStart" value=1 onchange="if (this.checked) {document.getElementById('asc').checked=true;} else {document.getElementById('desc').checked=true;}">&nbsp;Depuis le début,&nbsp;&nbsp;<b>Horodatage</b>&nbsp;<input type="radio" name="order" id="asc" value="asc"> croissant&nbsp;&nbsp;<input type="radio" name="order" id="desc" value="desc" checked> décroissant<br/>
        <input type="number" id="limit" value=50 min=10 max=2000 size=5>&nbsp;résultats maximum&nbsp;&nbsp;
        <input type="checkbox" id="stripCodes" value=1>&nbsp;Ignorer le formatage
        <br/>
        <button class="button2" id="search_btn" onclick="launchSearch()" disabled>Search !</button>&nbsp;&nbsp;<button class="button2" id="save_btn" onclick="exportSearch()" disabled>💾</button>
        <button class="button2" id="abort_btn" style="display:none;font-size:0.8em;color:#f91660;background-color:#ffdaaa;" onclick="abortSearch()">Abort...</button>
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
        document.addEventListener("copy",(event) => {
          let sel=document.getSelection();
          let node=sel.anchorNode.parentElement;
          while (!!node && !node.classList.contains("tabline_msg"))
          node=node.parentElement;
          if (!!node && node.classList.contains("tabline_msg")) {
            console.log(node.dataset["mirc"]);
            alert("Formatage IRC de la ligne (à copier):\n"+node.dataset["mirc"]);
            event.clipboardData.setData("web image/gif",node.dataset["mirc"]);
            return;
          } else
            event.clipboardData.setData("text/plain",sel.toString());
        });
      </script>
    </center>
  </body>
</html>

