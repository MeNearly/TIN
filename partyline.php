<?php
namespace bot\partyline;

require_once 'events.php';

const HELP="`Partyline` active commands are :
- /die\t\t\t => stop ALL the IRC connections of the bot
- /quit\t\t\t => quit this PARTYLINE
- /stop\t\t\t => stop this IRC connection
- /channels\t\t => show this connection's channels
- /connections\t\t => show all connections
----------------------------------------------------------
- /msg [#channel] Message => send Message to #channel
      *(if not spécified, the default one is used)
- /pmsg nick Message\t => send Message to nick
- /msgTo connName nick|chan Message :
      Searches for the connection connName and then send
      the message to nick or chan
- /notice and /noticeTo are like /pmsg and /msgTo
      but with notice
- /raw DATA\t\t => send raw DATA to the server, e.g.:
\t\t\t /raw MODE #channel +v nickname
----------------------------------------------------------
- /me [chan] Message\t => send a channel-only action
- /pme [who] Message\t => send an action
- /meTo connName nick|chan Message :
      Search for the connection connName and then send
      the message as action to nick or chan
----------------------------------------------------------
- /nick [new_nick]\t => show [change if new_nick specified] connection nickname
- /away away message\t => mark as away with 'away message'
- /join {#channel} [keys] => join 1,n channel(s)
       * You'll have to update params.php to see the log
- /default #channel\t => change the default channel for direct messages
- /part {#channel}\t => leave 1,n channel(s)
- /users [#channel]\t => give the users on #channel
        *(if not specified, the default one is used)
----------------------------------------------------------
- /spam DEST NB MESSAGE\t\t => send NB times MESSAGE to DEST
- /rainbow DEST NB MESSAGE\t => same, but with colors
----------------------------------------------------------
- /help\t\t\t => THIS short help";

function sendReply($token, string $reply, \bot\IRC $conn, bool $eol=true) {
  try {
    if ($conn->hasUser()) {
      $socket=$conn->getUserSocket($token);
      if ($socket) {
        socket_write($socket,$reply.($eol?PHP_EOL:""));
      }
    }
  } catch (\Exception $ex) {
    $conn->debug("Unknown error :".$ex->message);
  }
}
function sendReplyAll(string $reply, \bot\IRC $conn, bool $eol=true) {
  try {
    $sockets=$conn->getUsersSockets();
    foreach ($sockets as $token => $socket) {
      sendReply($token, $reply, $conn, $eol);
    }
  } catch (\Exception $ex) {
    $conn->debug("Unknown error :".$ex->message);
  }
}

function getChan(array &$words, \bot\IRC $conn):string {
  if (count($words)>1 && \bot\events\isChan($words[1])) {
    $chan=strtolower($words[1]);
    unset($words[1]);
  } else {
    $chan=$conn->getDefaultChan();
  }
  unset($words[0]);
  return $chan;
}


function parseUserInput($token, $line, \bot\IRC $conn) {
  $exited=false;
  $words=preg_split("@ @",$line);
  switch ($words[0]) {
    case "/license" :
      sendReply($token,\bot\getLicense(), $conn);
      break;

    case "/help" :
      sendReply($token,\bot\partyline\HELP,$conn);
      break;

    case "/msg" : // Channel message
      if (count($words)>1) {
        $to=\bot\partyline\getChan($words,$conn);
        $msg=implode(" ",$words);
        $conn->sendTo($to,$msg);
      }
      break;

    case "/pmsg" : // User message
      if (count($words)>2) {
        $to=$words[1];
        unset($words[0]);
        unset($words[1]);
        $msg=implode(" ",$words);
        $conn->sendTo($to,$msg);
      }
      break;

    case "/msgTo" : // Message for connection
      if (count($words)>3) {
        $connName=$words[1];
        $to=$words[2];
        unset($words[0]);
        unset($words[1]);
        unset($words[2]);
        $msg=implode(" ",$words);
        $conn->sendToOther($connName,$to,$msg);
      }
      break;

    case "/notice" : // User message
      if (count($words)>2) {
        $to=$words[1];
        unset($words[0]);
        unset($words[1]);
        $msg=implode(" ",$words);
        $conn->sendTo($to,$msg,false,true); /* sendTo $notice=true */
      }
      break;

    case "/noticeTo" : // Notice for connection
      if (count($words)>3) {
        $connName=$words[1];
        $to=$words[2];
        unset($words[0]);
        unset($words[1]);
        unset($words[2]);
        $msg=implode(" ",$words);
        $conn->sendToOther($connName,$to,$msg,true); /* sendTo $notice=true */
      }
      break;

    case "/raw" : // Notice for connection
      if (count($words)>1) {
        unset($words[0]);
        $msg=implode(" ",$words);
        $conn->send($msg); /* send raw data */
      }
      break;

    case "/me" : // Action
      if (count($words)>1) {
        $chan=\bot\partyline\getChan($words, $conn);
        $msg=implode(" ",$words);
        $conn->sendTo($chan,"\001ACTION ".$msg."\001");
      }
      break;

    case "/pme" : // User action
      if (count($words)>2) {
        $to=$words[1];
        unset($words[0]);
        unset($words[1]);
        $msg=implode(" ",$words);
        $conn->sendTo($to,"\001ACTION ".$msg."\001");
      }
      break;

    case "/meTo" : // Action on other connection/chan
      if (count($words)>2) {
        $con=$words[1];
        $chan=$words[2];
        unset($words[0]);
        unset($words[1]);
        unset($words[2]);
        $msg=implode(" ",$words);
        $conn->sendToOther($con,$chan,"\001ACTION ".$msg."\001");
      }
      break;

    case "/users" : // users list
      if (count($words)>0) {
        $chan=\bot\partyline\getChan($words, $conn);
        $users=implode(" ",$conn->getChannelUsers($chan));
        sendReply($token,"Users on $chan".PHP_EOL.$users,$conn);
      }
      break;

    case "/join" : // Join a chan on current connection
      if (count($words)>1) {
        unset($words[0]);
        $channels=array();
        $keys=array();
        foreach ($words as $param) {
          if (\bot\events\isChan($param[0]))
            $arr_name="channels";
          else
            $arr_name="keys";
          $$arr_name[]=strtolower($param);
        }
        sendReply($token,"Trying to join ".implode(" ",$channels),$conn);
        if (!$conn->join($channels,$keys)) {
          sendReply($token,"** JOIN : an error occured **",$conn);
        }
      } else {
        sendReply($token,"** JOIN : to few parameters **",$conn);
      }
      break;

    case "/part" : // Leave a chan on current connection
      if (count($words)>1) {
        unset($words[0]);
        $channels=array();
        foreach ($words as $param) {
          if (\bot\events\isChan($param[0]))
            $channels[]=strtolower($param);
        }
        sendReply($token,"Trying to leave ".implode(" ",$channels),$conn);
        if (!$conn->part($channels)) {
          sendReply($token,"** PART : an error occured **",$conn);
        }
      } else {
        sendReply($token,"** PART : to few parameters **",$conn);
      }
      break;

    case "/channels" : // Show channels on current connection
      sendReply($token,"Active channels are :".PHP_EOL.implode(" ",$conn->getChannels()),$conn);
      break;

    case "/default" :
      if (count($words)==2 && \bot\events\isChan($words[1])) {
        unset($words[0]);
        $conn->setDefaultChan($words[1]);
      } else {
        sendReply($token,"No valid default channel given....",$conn);
      }
      break;

    case "/connections" : // Show active connections
      sendReply($token,"Active connections are :".PHP_EOL.implode(" ",$conn->getConnections()),$conn);
      break;

    case "/quit" : // Close this telnet connection
      $conn->disconnectUser($token);
      $exited=true;
      break;

    case "/stop" : // Close __THIS__ IRC connection
      $conn->stop();
      $conn->disconnectUsers();
      $exited=true;
      break;

    case "/die" : // Close __ALL__ IRC connections
      unset($words[0]);
      $msg=implode(" ",$words);
      $conn->exit($msg);
      sendReply($token,"End the Bot",$conn);
      $conn->disconnectUsers();
      $exited=true;
      break;

    case "/nick" : // Change nickname
      if (count($words)>1) {
        $newnick=$words[1];
        $nick=$conn->getNickname();
        $conn->debug("Changing nick from $nick to $newnick","Notice");
        $conn->send("NICK :$newnick");
      } else {
        sendReply($token,"My nickname is ".$conn->getNickname(),$conn);
      }
      break;

    case "/away" :
      unset($words[0]);
      $awayMsg=implode(" ",$words);
      $conn->debug("Marking as AWAY ($awayMsg)","Notice");
      $conn->send("AWAY :$awayMsg");
      break;

    case "/whois" :
      if (count($words)>1) {
        $who=$words[1];
        $conn->debug("Whois $who","Notice");
        $conn->send("WHOIS $who");
      }
      break;

    case "/spam" : // SPAM
      if (count($words)>2) {
        $to=$words[1];
        $nb=intval($words[2]);
        unset($words[0]);
        unset($words[1]);
        unset($words[2]);
        $msg=implode(" ",$words);
        for ($i=0;$i<$nb;$i++)
          $conn->sendTo($to,$msg);
      }
      break;

    case "/rainbow" : // SPAM RAINBOW !
      if (count($words)>2) {
        $to=$words[1];
        $nb=intval($words[2]);
        unset($words[0]);
        unset($words[1]);
        $noNb=$words[2];
        unset($words[2]);
        $msg=implode(" ",$words);
        if ($nb==0) {
          $msg="$noNb $msg";
          $nb=1;
        }
        $letters=preg_split("@@",$msg);
        /// RAINBOW
        for ($i=0;$i<$nb;$i++) {
          $msg="\x03";
          $index_c=9;
          foreach($letters as $letter) {
            $msg.="\x03".($index_c)."$letter\x03";
            $index_c=($index_c++)%13+8;
          }
          $msg.="\x03";
          $conn->sendTo($to,$msg);
        }
      } else {
        echo "Error in line";
      }
      break;

    default:
      $conn->debug(implode(" ",$words),"Partyline");
      if (count($words)>0) {
        $first=$words[0];
        $to=\bot\partyline\getChan($words,$conn);
        $msg=implode(" ",$words);
        $conn->sendTo($to,$first." ".$msg);
      }
  }
  if ($conn->hasUser() && !$exited)
    sendReply($token,$conn->getPrompt(),$conn,false);
}
