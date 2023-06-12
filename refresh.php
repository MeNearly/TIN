<?php
/************************************
** Récupération des messagest      **
** (c) 202x MeNearly@gmail.com GPL **
*************************************/
/**********
** V1.2c **
***********/
namespace bot;
require_once 'params.php';
require_once 'messages.php';

/* On récupère le current=xxx, par défaut 0 */
$current=floatval($_REQUEST['current']??0);
$channel=$_REQUEST['channel']??\bot\channels[0];

if ($channel!="") {
  $channel=preg_replace('/@@@/','+',$channel);
  $channel=preg_replace('/%@@/','#',$channel);
/* On récupère le contenu du fichier des messages pour le chan */
  $msgs=\bot\messages\loadMessages($channel,false,false);
  $messages = $msgs['messages'];
  // on ne garde que 'messages' donc on réinitialise $msgs
  $msgs=array();

/* On va scanner les messages jusqu'à ce qu'on tombe sur un message déjà envoyé */
  $k=0;

  while ($k<count($messages) && $messages[$k]['timestamp']>$current) {
    $msgs[$k]=$messages[$k];
    // on préformate la date en mode 00:00:00
    $msgs[$k]['date'] = date('H:i:s', $messages[$k]['timestamp']);
    $k++;
  }
//  if ((!$arc && \bot\archiveMode==1) || $arc)
    $msgs=array_reverse($msgs);
}

header('Content-Type: application/json; charset=UTF-8');

/* On retourne un tableau JSON à la page */
echo json_encode(array('messages'=>$msgs));
