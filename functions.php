<?php
/*************************************
**       Sauvegarde et rendu        **
**         des messages IRC         **
** (c) 202x MeNearly@gmail.com GPL2 **
**************************************/
/**********
** V1.2c **
***********/

namespace bot;

require_once 'params.php';
require_once 'events.php';
require_once 'messages.php';
require_once 'mirc_colors.php';


function chanNameToLower(array &$data, string $field) {
  if (\bot\events\isChan($data[$field]))
    $data[$field]=strtolower($data[$field]);
}

/* Sauvegarde générique des message d'un channel */
function saveMessage(array $data, \bot\IRC $conn) {
  /* If no log, return */
  if (!\bot\logActive) return;

  /* Get current .json file */
  $who=$data['to'];
  $who=strtolower($who);
  $who=$conn->getShortname()."_".$who;
  $msgs=\bot\messages\loadMessages($who);

  /* Verify char encoding */
   if (\mb_detect_encoding($data['msg'], 'UTF-8', true) != 'UTF-8')
     $data['msg'] = iconv("ISO-8859-1", "UTF-8", $data['msg']);

  /* ACTION */
  if (($matches=$conn->testPattern('action',$data['msg']))!==false) {
    $data['msg']="\00302\002".$data['nick']."\002\003 ".$matches[1];
    $data['nick']="*";
  }

  /* Traitement du message */
  $newMessage = array('nick'=>$data['nick'], 'message'=>$data['msg'], 'timestamp'=>round(floatval(microtime(true)),4));
  /* attention ! microtime renvoie des secondes en flottant (précision jusqu'à la µseconde) */
   /* Car il y a un arrondi... */

  /* On ajoute au tout début du tableau */
  array_unshift($msgs['messages'], $newMessage);

  /* On ré-écrit dans le fichier .json en cours */
  $jsonFilename=\bot\messages\getFilename($who);
  \bot\messages\saveFile($jsonFilename, $msgs['messages']);

  /* Archive */
  $dt_now=new \DateTime();
  $now=$dt_now->getTimestamp();
  $dt_now_f=$dt_now->format("Ymd");

  $dt_last=new \DateTime();
  $dt_last->setTimestamp($msgs['messages'][count($msgs['messages'])-1]['timestamp']);
  $dt_last_f=$dt_last->format("Ymd");

  \bot\messages\archiveOnTheRun($who,$dt_now,$newMessage);
  if ($dt_last_f<$dt_now_f) { /* day comparison */
    \bot\messages\purgeFromYesterday($who,$dt_now); /* => json to zip */
  }
  \bot\messages\zipOlderFiles();
}


/*************************************************
******** POUR view.php et export.php *************
* Conversion codes de contrôle IRC => HTML/CSS  **
* et Annulation des codes de contrôle IRC       **
**************************************************/
function url2simpleName(string $str):string {
  $i=strrpos($str,"/",$str[-1]=="/"?-2:0);
  $str2=substr($str, $i+1,$str[-1]=="/"?-1:strlen($str)-$i);
  $i2=strpos($str2,".");
  return substr($str2,0,$i2!==false?$i2:strlen($str2));
}

function irc2html(string $text):string {
  $lines = explode("\n", $text);
  $out = '';

  foreach ($lines as $line) {

    $line = nl2br(htmlentities($line, ENT_COMPAT));
    $line = preg_replace_callback('/[\003](\d{0,2})(,\d{1,2})?([^\003\x0F]*)(?:[\003](?!\d))?/', function($matches) {
      $colors = \bot\colors\getColorsArray();
      $options = '';
      if ($matches[2] != '') {
        $bgcolor = trim(substr($matches[2], 1));
        if ((int) $bgcolor < count($colors)) {
          $options .= 'background-color: ' . $colors[(int) $bgcolor] . '; ';
        }
      }
      $forecolor = trim($matches[1]);
      if ($forecolor != '' && (int) $forecolor < count($colors)) {
        $options .= 'color: ' . $colors[(int) $forecolor] . ';';
      }
      if ($options != '') {
        return '<span style="' . $options . '" data-mirc="'.addslashes($matches[0]).'">' . $matches[3] . '</span>';
      } else {
        return $matches[3];
      }
    }, $line);
    $line = preg_replace('/[\002]([^\002\x0F]*)(?:[\002])?/', '<strong data-mirc="$0">$1</strong>', $line);
    $line = preg_replace('/[\x1F]([^\x1F\x0F]*)(?:[\x1F])?/', '<span style="text-decoration:underline" data-mirc="$0">$1</span>', $line);
    $line = preg_replace('/[\x12]([^\x12\x0F]*)(?:[\x12])?/', '<span style="text-decoration:line-through" data-mirc="$0">$1</span>', $line);
    $line = preg_replace('/[\x1D]([^\x1D\x0F]*)(?:[\x1D])?/', '<span style="font-style:italic" data-mirc="$0">$1</span>', $line);

/* gestion naïve de la vidéo inversée */
    $line = preg_replace('/[\x16]([^\x16\x0F]*)(?:[\x16])?/', '<span class="Xreverse" data-mirc="$0">$1</span>', $line);

    $line = preg_replace('/\x0F/','',$line);
    $line = preg_replace_callback('@(https?://([-\w\.]+)+(:\d+)?(/([\S+]*(\?\S+)?)?)?)@', function($matches) {
      return "<a href='".$matches[1]."' class='topic' target='".url2simpleName($matches[1])."'>".$matches[1]."</a>";
    }, $line);
    /* ajout de la ligne à la variable de retour */
    if ($line != '') {
      $out .= $line;
    }
  }
  return $out;
}

function stripControlCodes(string $text):string {
  $lines = explode("\n", $text);
  $out = '';

  foreach ($lines as $line) {

    $line = preg_replace('@(https?://([-\w\.]+)+(:\d+)?(/([\S+]*(\?\S+)?)?)?)@', "$1", $line);
    $line = preg_replace_callback('/[\003](\d{0,2})(,\d{1,2})?([^\003\x0F]*)(?:[\003](?!\d))?/', function($matches) {
      return $matches[3];
    }, $line);
    $line = preg_replace('/[\002]([^\002\x0F]*)(?:[\002])?/', '$1', $line);
    $line = preg_replace('/[\x1F]([^\x1F\x0F]*)(?:[\x1F])?/', '$1', $line);
    $line = preg_replace('/[\x12]([^\x12\x0F]*)(?:[\x12])?/', '$1', $line);
    $line = preg_replace('/[\x1D]([^\x1D\x0F]*)(?:[\x1D])?/', '$1', $line);
/* vidéo inversée */
    $line = preg_replace('/[\x16]([^\x16\x0F]*)(?:[\x16])?/', '$1', $line);

    $line = preg_replace('/\x0F/','',$line);

    /* ajout de la ligne à la variable de retour */
    if ($line != '') {
      $out .= $line;
    }
  }
  return $out;
}


