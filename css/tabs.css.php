 <?php
/**************************************
** Simple feuille de styles          **
** pour les panneaux des chans       **
** (c) 202x MeNearly@gmail.com GPL2  **
***************************************/
/**********
** V1.2c **
***********/
require_once 'mirc_colors.php';
header("Content-type: text/css");
?>
.clickable:hover {cursor:pointer}
.clickable.clicked:hover {cursor:default}
.tab {
  overflow-y: hidden;
  border: 1px solid #ccc;
  background-color: <?=\bot\colors\white?>;
}

.button2 {
  display: inline-block;
  padding: 0.5em 1em;
  font-size: 1.2em;
  text-align: center;
  cursor: pointer;
  outline: none;
  color: #3ff;
  background-color: #6585c2;
  border: none;
  border-radius: 8px;
  box-shadow: 0 2px #99a;
}

.button2:hover {background-color: #ec6e21}
@media (hover: none) {
    .button2 {
        background-color: #c95566;
    }
}

.button2[disabled]{
  border: 1px solid #999999;
  background-color: #cccccc;
  color: #666666;
  cursor: not-allowed;
  pointer-events: none;
}

.button2:active {
  background-color: #ea9c22;
  box-shadow: 0 2px #666;
  transform: translateY(4px);
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

.tabView {
  overflow-y: hidden;
  border: 1px solid #ccc;
  background-color: <?=\bot\colors\lightcyan?>;
}

.tabView.active {
  background-color: #0cc;
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

.tab button:hover {
  background-color: #ddd;
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

.floater {
    position: absolute;
    top: 100px;
    right: 1px;
    width: 30px;
    height: 100px;
    -webkit-transition: all 0.5s ease-in-out;
    transition: all 0.5s ease-out;
    z-index: 100;
    border-radius: 8px 0 0 8px;
    padding: 5px;
    background-color: #41a6d9;
    color: white;
    text-align: center;
    font-size: 2rem;
    box-sizing: border-box;
}

.floater_h:hover {
  color:#EE4444;
  cursor: pointer;
}
