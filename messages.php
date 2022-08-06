<?php
/***********************************
** GESTION DES FICHIERS MESSAGES  **
** (c) 2020 MeNearly@gmail.com GPL     **
************************************/
/**********
** V1.1b **
***********/
namespace bot\messages;

const LOG_DEBUG=false;

require_once 'params.php';

function log_debug(string $msg) {
  if (LOG_DEBUG)
    echo $msg.PHP_EOL;
}
function getFilename(string $who, bool $arc=false, bool $nozip=false):string {
  $who=strtolower($who);
  if ($arc)
    $dir=\bot\archivesDir;
  else
    $dir=\bot\messagesDir;
  $dir.=($dir[-1]!=DIRECTORY_SEPARATOR?DIRECTORY_SEPARATOR:"");

  if (!$arc || $nozip) {
    $jsonFilename=$dir.$who."_msgs.json";
  } else { /* use zipped files for archives */
    $jsonFilename=$dir.$who."_msgs.zip";
  }
  return $jsonFilename;
}
function loadFile(string $filename, bool $create=true):array {
  if (file_exists($filename)) {
    log_debug("Loading file $filename");
    if (substr($filename,-4)==".zip") {
      $zip=new \ZipArchive();
      $zip->open($filename);
      $content = $zip->getFromIndex(0);
      $zip->close();
    } else {
      $content=file_get_contents($filename);
    }
    $msgs=json_decode($content, TRUE);
    if ($msgs==NULL) {
      $msgs=array('messages' => array());
    }
  } else {
    $msgs=array('messages' => array());
    if ($create)
      file_put_contents($filename,json_encode($msgs));
  }
  return $msgs;
}
function saveFile(string $filename, array $msgs) {
  $zip=(substr($filename,-4)==".zip");
  if ($zip) {
    $zipFile=new \ZipArchive();
    if ($zipFile->open($filename, \ZipArchive::CREATE)!==TRUE) {
      log_debug("Impossible d'ouvrir le fichier $filename");
      return false;
    }
    $zipFile->addFromString(substr($filename,0,-4).".json",json_encode(array('messages'=>$msgs)));
    $zipFile->close();
  } else {
    file_put_contents($filename,json_encode(array('messages'=>$msgs)));
  }
}
function loadMessages(string $who, bool $arc=false, bool $create=false):array {
  $filename=getFilename($who,$arc);
  return loadFile($filename,$create);
}

function del_archiveFromMidnight(\bot\IRC $conn) {
  /* on va de la veille start=00:00:00 à end=23:59:59 */
  $now=(new \DateTime())->getTimestamp();
  /* on se place à 23:59:59.9999 */
  $end=\DateTime::createFromFormat("d-m-Y H:i:s",(new \DateTime())->format("d-m-Y")." 00:00:00")->getTimestamp()-0.0001;
  $start=$end-86399;
  $date=new \DateTime();
  $dateDisplay=new \DateTime();
  foreach ($conn->getChannels() as $chan) {
  /* ATTENTION !! */
  /* ICI les noms de chans ne sont pas préfixés par le serveur */
    $date->setTimestamp($end);
    $date_string=$date->format("_Y_m_d");

    /* On charge les messages courants et on se positionne au départ (minuit-0.0001 seconde) */
    $messages=loadMessages($conn->getShortName()."_".$chan)['messages'];
    $i=0;
    $l=count($messages);
    while ($i<$l && $messages[$i++]['timestamp']>$end);
    $i--;

    /* on charge la sauvegarde courante */
    /* Fichier archive .json */
    $arcFilename=getFilename($conn->getShortName()."_".$chan.$date_string,true,true); /* archive non zippée */
    $merge=false;
    $arc_msgs=loadFile($arcFilename)['messages'];
    /*********************************
    ** fichier existant et non vide **
    ** flag $merge à true           **
    ** ça permet de conserver le    **
    ** on sauvegarde dans $arc_tmp  **
    ** bon ordre dans le fichier    **
    ** et on merge les 2 tableaux   **
    ** sauvegardé finalement        **
    ** avant de sauvegarder         **
    **********************************/
    if (count($arc_msgs)>0) {
      $arc_tmp=$arc_msgs;
      $arc_msgs=array();
      $merge=true;
    }
    if ($i<$l)
      log_debug("Archivage dans ".basename($arcFilename)." à partir de ".($date->format("d/m/Y h:i:s")));
    while ($i<$l) {
      /* Distance temporelle => on archive ssi > messagesDisplay */
      $diff=($now-$messages[$i]['timestamp']);
      $dateDisplay->setTimestamp($messages[$i]['timestamp']);
      /* Si on a changé de jour */
      if ($messages[$i]['timestamp']<$start) {
        log_debug("Changement de jour");
        /* on sauvegarde l'archive */
        log_debug("Sauvegarde de $arcFilename");
        if ($merge) { /* Cas du fichier pré-existant non vide */
          /* Le nouveau tableau en 1er pour conserver l'ordre */
          $arc_final=array_merge($arc_msgs,$arc_tmp);
          $arc_msgs=$arc_final;
        }
        saveFile($arcFilename,$arc_msgs);
        log_debug("On recule d'1 jour");
        /* on recule d'1 jour=86400 seconde */
        $end-=86400;
        $start-=86400;
        $date->setTimestamp($end);
        log_debug("***************************");
        log_debug($dateDisplay->format("d/m/Y H:i:s"));
        log_debug("***************************");
        $date_string=$date->format("_Y_m_d");
        /* Nouveau nom de fichier */
        $arcFilename=getFilename($conn->getShortname()."_".$chan.$date_string,true);
        /* On charge s'il existait déjà */
        $merge=false;
        $arc_msgs=loadFile($arcFilename)['messages'];
        if (count($arc_msgs)>0) { /* Fichier pré-existant et non vide (voir plus haut) */
          $arc_tmp=$arc_msgs;
          $arc_msgs=array();
          $merge=true;
        }
        /* ON N'INCRÉMENTE PAS $i, AFIN DE CRÉER TOUS LES FICHIERS DE LOG */
      } elseif ($i==$l-1) { /* Dernier message */
        if ($diff>\bot\messagesDisplay && $messages[$i]['timestamp']<=$end) {
        /* Message théoriquement concerné par l'archivage [$start;$end]
          log_debug("Dernier message, date : ".($dateDisplay->format("d/m/Y H:i:s")));
          log_debug(substr($messages[$i]['message'],0,20)); */
          $arc_msgs[]=$messages[$i];
          unset($messages[$i]);
          log_debug("Sauvegarde de $arcFilename");
          if ($merge) { /* Cas du fichier pré-existant non vide */
            /* Le nouveau tableau en 1er pour conserver l'ordre */
            $arc_final=array_merge($arc_msgs,$arc_tmp);
            $arc_msgs=$arc_final;
          }
          $zippedArcFilename=getFilename($conn->getShortName()."_".$chan.$date_string,true); /* archive non zippée */
          saveFile($zippedArcFilename,$arc_msgs); /* => on zip l'archive et on supprime l'archive .json */
          unlink($arcFilename);
        }
        /* On incrémente $i pour arrêter la boucle */
        $i++;
      } else { /* Bon jour et pas le dernier message */
        /* Si message archivable (dans [$start;$end] ) et qu'on n'a plus besoin de garder affichable */
        if ($diff>\bot\messagesDisplay && $messages[$i]['timestamp']<=$end) { 
          log_debug("Ajout message à ".basename($arcFilename)." =>".$dateDisplay->format("d/m/Y H:i:s")." : ".substr($messages[$i]['message'],0,20));
        /* on ajoute le message courant */
          $arc_msgs[]=$messages[$i];
          unset($messages[$i]);
        } else { /* < messagesDisplay || > $end => on ne fait rien ! */
        }
        $i++; /* On incrémente dans tous les cas */
      }
    }
    /* On met à jour la liste des messages courants à afficher */
    log_debug("Sauvegarde de ".basename(getFilename($chan)));
    file_put_contents(getFilename($conn->getShortname()."_".$chan),json_encode(array('messages'=>$messages)));
  }
}

function archiveOnTheRun(string $who, \DateTime $now_dt, array $message) {
  $suffix=$now_dt->format("_Y_m_d");
  $arc_filename=getFilename($who.$suffix,true,true); /* on the run => no zip file, .json */
  $msgs=loadFile($arc_filename)['messages'];
  array_unshift($msgs,$message);
  saveFile($arc_filename,$msgs);
}

function purgeFromYesterday(string $who, \DateTime $dt_now) {
  /* back to the yesterday (now - messagesDisplay)*/
  $dt_yester=new \DateTime();
  $dt_yester->setTimestamp($dt_yester->getTimestamp()-86370); /* Timestamp is in seconds */
  /* Date format for date comparison : messages to delete from current .json file */
  $now_ft=$dt_now->format("Ymd");

  $filename=getFilename($who,false); /* current messages */
  $msgs=loadFile($who,false)['messages'];
  $i=0;
  $dt_msg=new \DateTime();
  foreach ($msgs as $msg) {
    $dt_msg->setTimestamp($msg['timestamp']);
    $dt_msg_ft=$dt_msg->format("Ymd");
    if ($dt_msg_ft<$now_ft && ($dt_now->getTimestamp()-$msg['timestamp'])>\bot\messagesDisplay) {
      unset($msgs[$i]);
    }
    $i++;
  }

  saveFile($filename,$msgs); /* save remaining messages to $who.json*/
}

function zipOlderFiles() {
  $glob=\bot\archivesDir;
  $glob.=($glob[-1]!=DIRECTORY_SEPARATOR?DIRECTORY_SEPARATOR:"");
  $dt=new \DateTime();
  $day=$dt->format("d");
  $glob.="*_?[!".$day[1]."]_msgs.json";
  $arr=glob($glob);
  if ($arr!==false) { /* found non zipped json files different from today's day of month */
    foreach ($arr as $filename) {
      $zippedFilename=substr($filename,0,strrpos($filename,".json")).".zip"; /* zipped filename */
      echo "$filename => $zippedFilename".PHP_EOL;
      $zip=new \ZipArchive();
      if ($zip->open($zippedFilename, \ZipArchive::CREATE)!==TRUE) {
        log_debug("Impossible de créer le fichier $zippedFilename");
        continue;
      }
      $zip->addFile($filename,basename($filename),0,0,\ZipArchive::FL_ENC_UTF_8);
      $zip->close();
      unlink($filename); /* erase non zipped older file */
    }
  }
}


