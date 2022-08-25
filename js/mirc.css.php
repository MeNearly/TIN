<?php
/************************************
** Simple feuille de styles        **
** calculée pour les couleurs      **
** (c) 2022 MeNearly@gmail.com GPL **
*************************************/
/**********
** V1.1b **
***********/
require_once 'mirc_colors.php';
header("Content-type: text/css");
echo \bot\colors\getCSS();
