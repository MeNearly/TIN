<?php
/****************************************************
** Functions to handle the events                  **
** Events are determined with regexp applied to    **
**   the messages from the server                  **
** They must explicetly be specified when creating **
**    the connection to IRC (see class \bot\IRC )  **
** (c) 2020-2022 meNearly@gmail.com  **
** All source files are under GPL                  **
*****************************************************/
namespace bot\events;

function isChan($str):bool {
  return ($str[0]=="#" || $str[0]=="&");
}

/* Nick entrant sur un chan */
function join(array $data, \bot\IRC $conn) {
  \bot\chanNameToLower($data,"chan");
  $nick=$data['nick'];
  if ($data['name']==$conn->getNickName()) {
    $conn->join(array($data['chan']),array());
  }
  $data['msg']="\00309$nick (".$data['host'].") vient d'entrer sur ".$data['chan']."\003";
  $data['to']=$data['chan'];
  $data['nick']="\00309*\003";
  if (!$conn->chanUserExists($data['chan'],$nick))
    $conn->addChanUser($data['chan'],$nick);
  \bot\saveMessage($data,$conn);
  \bot\events\link($data, $conn);
}

/* Liste des nicks reçue */
function userslist(array $data, \bot\IRC $conn) {
  \bot\chanNameToLower($data,"chan");
  $conn->debug("getting users list from ".$data['chan'],"Notice");
  $list=preg_split("@ @",$data['list']);
  foreach ($list as $user) {
    if (preg_match("/[&+~%@](?P<nick>[^ ]*)/",$user,$match))
      $user=$match['nick'];
    $conn->addChanUser($data['chan'],$user);
  }
  natcasesort($conn->channelsUsers[$data['chan']]);
}

/* Nick kické d'un chan */
function kick(array $data, \bot\IRC $conn) {
  \bot\chanNameToLower($data,"chan");
  $nick=$data['nick'];
  $to=$data['to'];
  $chan=$data['chan'];
  $reason=$data['msg'];
  $data['msg']="\00304$nick a expulsé $to de $chan ($reason)\003";
  $data['to']=$chan;
  $data['nick']="\00304*\003";
  $conn->removeChanUser($chan,$to);
  \bot\saveMessage($data,$conn);
  \bot\events\link($data, $conn);
}

/* Nick banni d'un chan */
function ban(array $data, \bot\IRC $conn) {
  \bot\chanNameToLower($data,"chan");
  $nick=$data['nick'];
  $to=$data['to'];
  $chan=$data['chan'];
  $data['msg']="\00304$nick a posé un(des) ban(s) pour $to sur $chan\003";
  $data['to']=$chan;
  $data['nick']="\00304*\003";
  $conn->removeChanUser($chan,$to);
  \bot\saveMessage($data,$conn);
  \bot\events\link($data, $conn);
}

/* Nick débanni d'un chan */
function unban(array $data, \bot\IRC $conn) {
  \bot\chanNameToLower($data,"chan");
  $nick=$data['nick'];
  $to=$data['to'];
  $chan=$data['chan'];
  $data['msg']="\00304$nick a enlevé le(s) ban(s) pour $to sur $chan\003";
  $data['to']=$chan;
  $data['nick']="\00304*\003";
  \bot\saveMessage($data,$conn);
  \bot\events\link($data, $conn);
}

/* Nick gagne un voice */
function voice(array $data, \bot\IRC $conn) {
  \bot\chanNameToLower($data,"chan");
  $nick=$data['nick'];
  $to=$data['to'];
  $chan=$data['chan'];
  $data['msg']="\00309$nick a donné la parole à $to sur $chan\003";
  $data['to']=$chan;
  $data['nick']="\00309*\003";
  \bot\saveMessage($data,$conn);
  \bot\events\link($data, $conn);
}

/* Nick perd un voice */
function devoice(array $data, \bot\IRC $conn) {
  \bot\chanNameToLower($data,"chan");
  $nick=$data['nick'];
  $to=$data['to'];
  $chan=$data['chan'];
  $data['msg']="\00310$nick a enlevé la parole à $to sur $chan\003";
  $data['to']=$chan;
  $data['nick']="\00310*\003";
  \bot\saveMessage($data,$conn);
  \bot\events\link($data, $conn);
}

/* Nick quittant un chan */
function part(array $data, \bot\IRC $conn) {
  \bot\chanNameToLower($data,"chan");
  $nick=$data['nick'];
  $data['msg']="\00307".$data['nick']." (".$data['host'].") est parti\003";
  $data['to']=$data['chan'];
  $data['nick']="\00307*\003";
  if ($nick!=$conn->getNickname()) {
    $conn->removeChanUser($data['chan'],$nick);
  }
  \bot\saveMessage($data,$conn);
  \bot\events\link($data, $conn);
}

/* Nick quittant le serveur (=> uniquement sur les chans ou apparaît 'nick' sinon le premier par défaut) */
function quit(array $data, \bot\IRC $conn) {
  $nick=$data['nick'];
  $data['nick']="\00308*\003";
  $data['msg']="\00308$nick (".$data['host'].") a quitté (".$data['reason'].")";
  $found=false;
  foreach ($conn->getChannels() as $chan) {
    if ($conn->chanUserExists($chan,$nick)) {
      $found=true;
      $data['to']=$chan;
      $conn->removeChanUser($chan,$nick);
      \bot\saveMessage($data,$conn);
      \bot\events\link($data, $conn);
    }
  }
  if (!$found) {
    $chans=$conn->getChannels();
    if (count($chans)==0) {
      $conn->debug("No more channels !","Notice");
    } else { /* Else on 1st chan for $conn */
      $data['to']=strtolower($chans[0]);
      \bot\saveMessage($data,$conn);
    }
  }
}

/* Changement de nick (=> uniquement sur les chans ou apparaît 'nick')*/
function nick(array $data, \bot\IRC $conn) {
  $regexp='/(?P<nick>.+)!(?P<username>.+)@(?P<host>.+)/';
  if (preg_match($regexp,$data['userHandler'],$nickvalues)) {
    $nick=$nickvalues['nick'];
  } else {
    $nick=$data['userHandler'];
  }
  $newnick=$data['newnick'];
  $data['msg']="\00302$nick\003 est maintenant connu sous le nom de \00302$newnick\003";
  $data['nick']="\00308*\003";
  $chans=$conn->changeUserNick($nick,$newnick);
  if ($nick==$conn->getNickname()) {
    $conn->setNickname($newnick);
  }
  if (count($chans)==0) { // Si n'apparaît dans aucun channel
    $conn->debug("$nick trouvé nulle part !!","ATTENTION");
  } else {
    foreach ($chans as $chan) {
      $chan=strtolower($chan);
      $data['to']=$chan;
      \bot\saveMessage($data,$conn);
      \bot\events\link($data, $conn);
    }
  }
}

/* Notice received, notify on all chans where it appears, else on 1st available chan */
function notice(array $data, \bot\IRC $conn) {
  $found=false;
  $nick=$data['nick'];
  $data['nick']="\00310>-$nick-<\003";
  foreach ($conn->getChannels() as $chan) {
    $chan=strtolower($chan);
    if ($conn->chanUserExists($chan,$nick)) {
      $found=true;
      $data['to']=$chan;
      \bot\saveMessage($data,$conn);
      \bot\events\link($data, $conn);
    }
  }
  if (!$found) {
    $chans=$conn->getChannels();
    if (count($chans)==0) {
      $conn->debug("No more channels !","Notice");
    } else { /*Else on 1st chan for $conn */
      $data['to']=strtolower($chans[0]);
      \bot\saveMessage($data,$conn);
      \bot\events\link($data, $conn);
    }
  }
  /* On notifie en partyline */
  $dt=new \DateTime();
  $dt=$dt->format("d-m-y H:i:s");
  \bot\partyline\sendReplyAll(PHP_EOL."([$dt] >-".$nick."-<) ".$data['msg']."\x07",$conn,true); /* beep */
  \bot\partyline\sendReplyAll($conn->getPrompt(),$conn,false);
}

/* Message from server */
/* This is to catch replies to commands in partyline */
/* don't need to save... */
function servmsg (array $data, \bot\IRC $conn) {
  $dt=new \DateTime();
  $dt=$dt->format("d-m-y H:i:s");
  \bot\partyline\sendReplyAll(($conn->isCmdRunning()?"":PHP_EOL)."[($dt)] ".$data['code']." ".$data['msg']."\x07",$conn,true); /* beep */

  if ($data['code']=='311') { /* whois or other running command */
    $conn->setCmdRunning(true);
  }
  if ($data['code']=='318' || !$conn->isCmdRunning()) { /* for running whois */
    \bot\partyline\sendReplyAll($conn->getPrompt(),$conn,false);
    $conn->setCmdRunning(false);
  }
}

/* Standard PRIVMSG. */
/* IMPORTANT !! */
/* PRIVMSG must be explicitely linked with e.g. $conn1->addEventHandler('privmsg','\bot\events\link'); or $conn1->addEventHandler('privmsg','\bot\restrictedLink'); */
/* This is to allow other events to use link */

function privmsg(array $data, \bot\IRC $conn) {
  \bot\chanNameToLower($data,"to");
  
  $dt=new \DateTime();
  $dt=$dt->format("d-m-y H:i:s");
  if ($data['to'][0]!="#" && $data['to'][0]!="&" && !$conn->testPattern("versiononly",$data['msg'])) { /* private message to bot */
    echo $conn->getShortname()." : ".$data['nick']." => ".$data['msg'].PHP_EOL;
    \bot\partyline\sendReplyAll(PHP_EOL."[($dt) ".$data['nick']."] ".$data['msg']."\x07",$conn,true); /* Beep */
    if ($data['to']==$conn->getNickname()) {
      $data['to']=$data['nick'];
    }
    \bot\partyline\sendReplyAll($conn->getPrompt(),$conn,false);
  } else {
    $escapedNick=preg_replace_callback("@[\[\]\|]@",function($m) {return "\\".$m[0];},$conn->getNickName());
    if (preg_match("/([\^\ ]*)".$escapedNick."([\ ,]*)/i",$data['msg'],$m)) {
      \bot\partyline\sendReplyAll(PHP_EOL."([$dt] ".$data['nick'].") ".$data['msg']."\x07",$conn,true); /* beep */
      \bot\partyline\sendReplyAll($conn->getPrompt(),$conn,false);
    }
  }
  \bot\saveMessage($data,$conn);
}


/* For mirroring an event */
function link(array $data, \bot\IRC $conn) {
  \bot\chanNameToLower($data,"to");
  $conn->reflectToLink($data['to'],$data); /* chan or user = $data['to'] */
}

/* will only reflect messages from $conn->ownerNick */
function restrictedLink(array $data, \bot\IRC $conn) {
  $from=strtolower($data['nick']);
  if ($conn->isOwnerNick($from)) {
    $words=preg_split("/ /",$data['msg']);
    if (count($words)>2 && preg_match("/!pv/i",$words[0])) {
      $to=\bot\stripControlCodes($words[1]);
      unset($words[0]);
      unset($words[1]);
      $msg=\join(" ",$words);
      $conn->sendLinkedPrivate($data['to'], $to, $msg);
    } else {
      $conn->reflectToLink($data['to'],$data,true); /* chan or user = $data['to'], send asMe (true) */
    }
  } /* else do  nothing */
}


