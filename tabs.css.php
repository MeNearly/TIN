 <?php
/*************************************
** Simple feuille de styles         **
** pour les panneaux des chans      **
** (c) 2020 MeNearly@gmail.com GPL  **
**************************************/
/**********
** V1.1b **
***********/
require_once("mirc_colors.php");
header("Content-type: text/css");/* Style the tab */
?>
.clickable:hover {cursor:pointer}
.clickable.clicked:hover {cursor:default}
.tab {
  overflow: hidden;
  border: 1px solid #ccc;
  background-color: <?=\bot\colors\white?>;
}

.tabView {
  overflow: hidden;
  border: 1px solid #ccc;
  background-color: <?=\bot\colors\lightcyan?>;
}

/* Style des boutons */
.tab button {
  background-color: inherit;
  float: left;
  border: none;
  outline: none;
  cursor: pointer;
  padding: 14px 16px;
  transition: 0.3s;
}

.tab button:hover {
  background-color: #ddd;
}

.tabView button {
  background-color: inherit;
  float: left;
  border: none;
  outline: none;
  cursor: pointer;
  padding: 14px 16px;
  transition: 0.3s;
}

.tabView button:hover {
  background-color: #ddd;
}

.tabView button.active {
  background-color: #ccc;
}

.tabView button.active {
  background-color: #ccc;
}

.tabcontent {
  display: none;
  padding: 6px 12px;
  border: 1px solid #ccc;
  border-top: none;
}

.tabline_date {
  color: #2C3E50;
  padding: 2px;
}

.tabline_nick {
  color: #2C3E50;
  text-align: center;
  padding: 2px;
  font-weight: bold;
  word-break: break-all;
}

.tabline_msg {
  padding: 2px;
}
