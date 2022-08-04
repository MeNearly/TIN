<?php
/*****************************************************
** Gestion des couleurs mIRC pour PHP et JavaScript **
** (c) 2020 MeNearly@gmail.com GPL                       **
******************************************************/
/**********
** V1.1b **
***********/
namespace bot\colors;

const white="antiquewhite";
const black="#000000";
const blue="#00007F";
const green="#009300";
const red="#FF0000";
const brown="#7F0000";
const purple="#9C009C";
const orange="#FC7F00";
const yellow="#D5D500";
const lightgreen="#00FC00";
const cyan="#009393";
const lightcyan="#58A19D";
const lightblue="#0000FC";
const pink="#FF00FF";
const grey="#7F7F7F";
const lightgrey="#D2D2D2";

/*****************************************
******* NE PAS MODIFIER CI-DESSOUS *******
******************************************/
function getColorsArray():array {
  $consts=get_defined_constants(true)['user'];
  $regex="@".preg_replace("@\\\\@","\\\\\\",__NAMESPACE__)."\\\\(.*)@";
  foreach ($consts as $name => $value) {
    if (preg_match($regex,$name)) {
      $result[]=$value;
    }
  }
  return $result;
}

// Couleurs étendues, de 16 à 98
function getExtendedColors():array {
  return array(16=>"#470000",
"#472100","#474700","#324700","#004700","#00472c","#004747","#002747","#000047","#2e0047","#470047","#47002a",
"#740000","#743a00","#747400","#517400","#007400","#007449","#007474","#004074","#000074","#4b0074","#740074","#740045",
"#b50000","#b56300","#b5b500","#7db500","#00b500","#00b571","#00b5b5","#0063b5","#0000b5","#7500b5","#b500b5","#b5006b",
"#ff0000","#ff8c00","#ffff00","#b2ff00","#00ff00","#00ffa0","#00ffff","#008cff","#0000ff","#a500ff","#ff00ff","#ff0098",
"#ff5959","#ffb459","#ffff71","#cfff60","#6fff6f","#65ffc9","#6dffff","#59b4ff","#5959ff","#c459ff","#ff66ff","#ff59bc",
"#ff9c9c","#ffd39c","#ffff9c","#e2ff9c","#9cff9c","#9cffdb","#9cffff","#9cd3ff","#9c9cff","#dc9cff","#ff9cff","#ff94d3",
"#000000","#131313","#282828","#363636","#4d4d4d","#656565","#818181","#9f9f9f","#bcbcbc","#e2e2e2","#ffffff");
}

function getColorsArrayAssoc():array {
  $consts=get_defined_constants(true)['user'];
  $result=array();
  $regex="@".preg_replace("@\\\\@","\\\\\\",__NAMESPACE__)."\\\\(.*)@";
  foreach ($consts as $name => $value) {
    if (preg_match($regex,$name,$vname)) {
      $result[$vname[1]]=$value;
    }
  }
  return $result;
}

function getCSS():string {
  $out= ".mirc{padding:2px 4px;}".PHP_EOL;
  $i=0;
  foreach (getColorsArrayAssoc() as $name => $value) {
    $i_s=sprintf("%02d",$i);
    $out.= ".bg$i,.bg$i_s{background-color:$value;/*$name*/}".PHP_EOL; /**/
    $i++;
  }
  foreach (getExtendedColors() as $colorIndex) {
    $out.= ".bg$i{background-color:$colorIndex;/*#$i*/}".PHP_EOL; /**/
    $i++;
  }
  $i=0;
  foreach (getColorsArrayAssoc() as $name => $value) {
    $i_s=sprintf("%02d",$i);
    $out.= ".fg$i,.fg$i_s{color:$value;/*$name*/}".PHP_EOL; /**/
    $i++;
  }
  foreach (getExtendedColors() as $colorIndex) {
    $out.= ".fg$i{color:$colorIndex;/*#$i*/}".PHP_EOL; /**/
    $i++;
  }
  return $out;
}

