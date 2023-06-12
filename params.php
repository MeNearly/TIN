<?php
/*******************************************************
** Constantes/Paramètres pour les connexion           **
** (c) 2020-202x ksynet.fr MeNearly@gmail.com GPL     **
********************************************************/
/**********
** V1.1c **
***********/
namespace bot;

const ADMINISTRATOR="monitor"; /* administrator username in .htaccess */

const version_reply="Tin Irc Node v1.3 (c) 2020-202x ksynet.fr";
const quitMsg="Gone with the wind...";

const messagesDir="/home/YOU/monitoring/JSON/";
const archivesDir="/home/YOU/monitoring/archives/";

const refreshInterval=20; /* Pour la visualisation en 'live' */

/* Must include EVERY channels on EVERY connection */
const channels=array("server1_#myNeatChannel");
/*const channels=array(); */

/* channels 'secrets' réservé à l'utilisateur \bot\ADMINISTRATOR */
const secretChannels=array();


/* Activation désactivation de la log */
const logActive=true;

/* L'archivage se fait au fur et à mesure.
    Il y a 2 fichiers : la log courante, et la log datée, qui sont alimentée au même moment.
    Ainsi, à partir de minuit (s'il y a une activité, et à la fermeture normale), toutes les archives
    plus anciennes que la date du jour sont compressées.
    Cependant l'archive courante conserve une durée 'tampon' de 'messagesDisplay' secondes
    pour la visualisation en temps réel. */


const messagesDisplay=3600;  /* en secondes (h×3600), conservés à l'ouverture */

/***********************
** FIN DU PARAMÉTRAGE **
************************/
set_include_path(get_include_path().PATH_SEPARATOR."/var/www/html/monitoring/");

function getLicense():string {
  return file_get_contents("./LICENSE");
}

function getAllowedChannels():array {
/* il existe plusieurs moyens de réserver l'accès */
/* .htaccess en est un, mais un système en php plus élaboré est */
/* intégrable facilement */
  $channels=\bot\channels;
  if (($_SERVER['PHP_AUTH_USER']??"")==\bot\ADMINISTRATOR) {
    $channels=array_merge($channels,\bot\secretChannels);
  }
  return $channels;
}

function channels2JS():string {
  $out="[";
  $chans="";
  $arr=\bot\getAllowedChannels();
  foreach($arr as $chan) {
    if ($chan[0]=="#" || $chan[0]=="&")
      $chan=substr($chan,1);
    $chans.='"'.strtolower($chan).'",';
  }
  $chans=substr($chans,0,-1);
  $out.=$chans."]";
  return $out;
}
