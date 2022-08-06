<?php
/********************************************
**    An IRC connecion for Tin Irc Node    **
** (c) 2020-2022 ian & xylian.fr           **
** MeNearly@gmail.com                      **
*********************************************/
/**********
** V1.1b **
***********/
namespace bot;
require_once 'functions.php';
require_once 'partyline.php';

const END="\r\n";
const chanPattern="(?P<chan>(#|&)[a-zA-Z0-9\-\+_\.]+)";

class IRC {

  private $shortname;

  /* Server */
  private $hostname, $port, $ssl, $socket, $password;
  private $defaultChan="";

  private $quitMsg=\bot\quitMsg;

  /* User used for this connection */
  private $nickname, $username, $realname, $userpass;
  private $nickServ="NickServ";

  private $runningCmd=false;

  /* CHANS */
  private $channels;
  public $channelsUsers;

  /* Debug */
  private $DEBUG = true;

  /* Handlers & event */
  private $events = array ();

  /* For control connexion */
  private $userPort;
  private $maxUsers = 5;
  private $usersListeningSocket = NULL;
  private $usersConnected = array();
  private $userTokenCounter = 0;

  private $mustStop = false;
  public $connected = false;

  /* Linked Chans */
  private $linkedChannels;

  private $owner; /* In case the connection is owned by a bot -- see RunBot.sh for sample */

  private $ownerNick = ""; /* used by \bot\restrictedLink */

  protected $eventHandlers = array(
    'userslist' =>  array(),
    'nickslist' =>  array(),
    'join'      =>  array(),
    'part'      =>  array(),
    'quit'      =>  array(),
    'kick'      =>  array(),
    'ban'       =>  array(),
    'unban'     =>  array(),
    'voice'       =>  array(),
    'devoice'     =>  array(),
    'nick'      =>  array(),
    'nick2'     =>  array(),
    'privmsg'   =>  array(),
    'notice'    =>  array(),
    'servmsg'   =>  array());

  public function __construct(string $hostname, string $port, bool $ssl = true, string $short = "", int $uport = 1807, string $password = NULL, string $ownerNick = "") {
    /* ATTENTION : this password is the server's one, not the nick's one !! */
    $this->hostname = $hostname;
    $this->port     = intval($port);
    $this->ssl      = $ssl;
    $this->password = $password;
    $this->ownerNick = strtolower($ownerNick);
    $this->channels = array();
    $this->channelsUsers = array();
    if ($short=="") {
      if (($i1=strpos($hostname,"."))!==false && ($i2=strrpos($hostname,"."))!=false && $i2!=$i) {
        $this->shortname=substr($short,$i1,$i2-$i1);
      } else {
        $this->shortname=$hostname;
      }
    } else {
      $this->shortname=$short;
    }
    $this->userPort=$uport;
    $this->linkedChannels=array();
  }

  public function debug(string $entry, string $type = 'raw') {
    if ($this->DEBUG && trim($entry)!="") {
      $time=(new \DateTime())->format("[d-m-y_H:i:s]");
      echo "(".$this->getShortName().") ".$time.' ['.$type.'] '.$entry.END;
    }
  }

  public function setDebug(bool $value) {
    $this->DEBUG=$value;
  }

  public function isCmdRunning():bool {
    return $this->runningCmd;
  }

  public function setCmdRunning(bool $run) {
    $this->runningCmd=$run;
  }

  public function setNickName(string $nick) {
    $this->nickname=$nick;
  }

  public function getNickName(): string {
    return $this->nickname;
  }

  public function setOwner(\bot\Bot $owner) {
    $this->owner=$owner;
  }

  public function getOwner(): \bot\Bot {
    return $this->owner;
  }

  public function getShortname():string {
    return $this->shortname;
  }

  public function getOwnerNick() {
    return $this->ownerNick;
  }

  public function setOwnerNick(string $nick="") {
    if ($nick != "")
      $this->ownerNick=strtoLower($nick);
  }

  public function getChannels():array {
    return $this->channels;
  }

  public function isConnected():bool {
    return $this->connected;
  }

  public function getDefaultChan():string {
    return $this->defaultChan;
  }

  private function initEventsPatterns() {
    $key='/:(?P<server>.+) 353 '.$this->nickname.' . (?P<chan>#.+) :(?P<list>.*)/';
    $this->events['userslist']=$key;
    /* Some servers do not send server name nor bot nick first ... */
    $this->events['nickslist']='/353 . (?P<chan>.+) :(?P<list>.+)/';

    $this->events['join']='/:(?P<nick>.+)!(?P<name>.+)@(?P<host>.+) JOIN :'.chanPattern.'/';
    $this->events['part']='/:(?P<nick>.+)!(?P<name>.+)@(?P<host>.+) PART '.chanPattern.'/';
    $this->events['quit']='/:(?P<nick>.+)!(?P<name>.+)@(?P<host>.+) QUIT :(?P<reason>.*)/';
    $this->events['nick']='/:(?P<userHandler>.+) NICK :(?P<newnick>.+)/';

    $this->events['privmsg']='/:(?P<nick>.+)!(?P<name>.+)@(?P<host>.+) PRIVMSG (?P<to>[^ :]+) :(?P<msg>.+)/';
    $this->events['notice']='/:(?P<nick>.+)!(?P<name>.+)@(?P<host>.+) NOTICE (?P<to>[^ :]+) :(?P<msg>.+)/'; /* TODO does not yet handle channels notice */
    $this->events['servmsg']='/:(?P<serv>.+) (?P<code>[0-9]+) (?P<to>[^ :]+) (?P<msg>.*)/'; /* message from server with RPL_CODE, must be added AFTER 'userslist' */

    $this->events['versionmsg']='/:(?P<nick>.+)!(?P<name>.+)@(?P<host>.+) PRIVMSG (?P<to>[^ :]+) :\001VERSION(.*)\001/';
    $this->events['versiononly']='/\001VERSION(.*)\001/';
    $this->events['kick']='/:(?P<nick>.+)!(?P<name>.+)@(?P<host>.+) KICK '.chanPattern.' (?P<to>[^ :]+) :(?P<msg>.+)/';
    $this->events['ban']='/:(?P<nick>.+)!(?P<name>.+)@(?P<host>.+) MODE '.chanPattern.' (?P<btype>\+([b]+)) (?P<to>.+)/';
    $this->events['unban']='/:(?P<nick>.+)!(?P<name>.+)@(?P<host>.+) MODE '.chanPattern.' (?P<btype>\-([b]+)) (?P<to>.+)/';
    $this->events['voice']='/:(?P<nick>.+)!(?P<name>.+)@(?P<host>.+) MODE '.chanPattern.' (?P<voice>\+([v]+)) (?P<to>.+)/';
    $this->events['devoice']='/:(?P<nick>.+)!(?P<name>.+)@(?P<host>.+) MODE '.chanPattern.' (?P<devoice>\-([v]+)) (?P<to>.+)/';

    $this->events['action']='/\001ACTION (.*)\001/'; /* only used in links, see reformatLinkedMessage */

  }

  public function testPattern(string $name, string $msg) {
    $pattern=$this->events[$name]??"";
    if ($pattern!="") {
      $result=preg_match($pattern,$msg,$matches);
      if ($result)
        return $matches;
    }
    return false;
  }

  public function setIdentity(string $nickname, string $username, string $realname, string $pass = "", string $nickserv="NickServ") {
    list($this->nickname, $this->username, $this->realname, $this->userpass, $this->nickServ) = array($nickname, $username, $realname, $pass, $nickserv);
    /* Maintenant on peut initialiser les handlers, car une des clés est calculée */
    $this->initEventsPatterns();
  }

  public function identify():bool {
    $this->debug("**** Trying to identify ****","NOTICE");
    if (!$this->connected) {
      echo $this->shortname." is not connected".PHP_EOL;
      return false;
    }
    $this->send("PRIVMSG ".$this->nickServ." identify ".$this->userpass);
    $this->debug("Sent identify with ".$this->userpass,"NOTICE");
    $this->identified=true;
    return true;
  }

  /* Channel Users */
  public function getChannelUsers(string $channel):array {
    if (!isset($this->channelsUsers[$channel])) {
      return array("The channel $channel does not exist...");
    }
    $users=$this->channelsUsers[$channel];
    if ($users==NULL)
      $users=array();
    return $users;
  }

  /* JOIN channel{,channel} [key{,key}] */
  public function join($channels,$keys=""):bool {
    if (!$this->connected) {
      echo $this->shortname." is not connected".PHP_EOL;
      return false;
    }
    if (!is_array($channels)) { /* Simple format */
      $channel=strtolower($channels);
      if ($channel[0]!="#" && $channel[0]!="&") {
        echo "** bad JOIN channel : $channels **".PHP_EOL;
        \bot\partyline\sendReplyAll("** bad JOIN channel : $channels **",$this);
        return false;
      }
      if (is_array($keys)) {
        echo "** multiple keys for joining a single channel **".PHP_EOL;
        \bot\partyline\sendReplyAll("** multiple keys for joining a single channel **",$this);
        return false;
      }
      $cmd_line="JOIN $channel $keys";
      $this->channelsUsers[$channel]=array();
      $this->channels[]=$channel;
      $this->send($cmd_line);
    } else {
      if (is_array($keys) && count($keys)>count($channels)) {
        echo "** more keys than channels for JOIN **".PHP_EOL;
        \bot\partyline\sendReplyAll("** more keys than channels for JOIN **",$this);
        return false;
      }
      foreach ($channels as $chan) {
        $chan=strtolower($chan);
        $this->channelsUsers[$chan]=array();
        $this->channels[]=$chan;
      }
      $channels=strtolower(implode(",",$channels));
      if (is_array($keys)) {
        $keys=implode(",",$keys);
      }
      $cmd_line="JOIN $channels $keys";
      $this->send($cmd_line);
    }
    $this->channels=array_unique($this->channels);
    return true;
  }

  /* PART channel{,channel} */
  public function part(array $channels):bool {
    if (!$this->connected) {
      echo $this->shortname." is not connected".PHP_EOL;
      return false;
    }
    $chans2del=array();

    foreach ($channels as $chan) {
      $chan=strtolower($chan);
      if (array_search($chan,$this->channels)===false) {
        echo "** not connected to $chan **".PHP_EOL;
        \bot\partyline\sendReplyAll("** not connected to $chan **",$this);
      } else {
        $this->channelsUsers[$chan]=array();
        $chans2del[]=$chan;
      }
    }
    $channels_str=strtolower(implode(",",$channels));
    $cmd_line="PART $channels_str";
    $this->send($cmd_line);

    $tmp=array_diff($this->channels,$chans2del);
    sort($tmp); /* re-index chans */

    $this->channels=$tmp;
    \bot\partyline\sendReplyAll("** left $channels_str **",$this);
    return true;
  }

  public function setDefaultChan(string $dchan) {
    $this->defaultChan=strtolower($dchan);
    $this->channels[]=$this->defaultChan;
    $this->channels=array_unique($this->channels);
  }

  public function connect(string $defaultChan="", float $timeout = 5.0):bool {
    if ($this->connected) {
      echo $this->shortname." is already connected...".PHP_EOL;
      return true;
    }
    if ($defaultChan!="")
      $this->setDefaultChan($defaultChan);
    /* Suppression de la vérification du certificat SSL (http://i.justrealized.com/2009/allowing-self-signed-certificates-for-pfsockopen-and-fsockopen/) */
    if ($this->ssl) {
      $context = stream_context_create();
      stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
      stream_context_set_option($context, 'ssl', 'verify_peer', false);
      /* Doesn't seem to work ... */
      stream_context_set_option($context, 'ssl', 'local_cert', "./tin.pem");
      stream_context_set_option($context, 'ssl', 'verify_peer_name', false);
      $this->socket = stream_socket_client('ssl://'.$this->hostname.':'.$this->port,$errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
    } else {
      $this->socket = fsockopen($this->hostname, $this->port, $errno, $errstr, $timeout);
    }

    if (!$this->socket) {
      throw new \Exception('Impossible de se connecter à '.$this->hostname.':'.$this->port.' ("'.$errstr.'", code '.$errno.').');
    }

    /*... THEN continue to IRC */
    stream_set_blocking($this->socket,false); /* non blocking */
    /* PASS ? */
    if ($this->password != NULL) {
      $this->send('PASS '.$this->password);
      /* TODO: exception */
    }

    /* NICK */
    $this->send('NICK '. $this->nickname);
    /* TODO: si nick déjà pris ... ??? */

    /* USER */
    $this->send('USER '. implode(' ', array($this->username, $this->defaultChan, '.', $this->realname)));
    /* TODO: si erreur ?? */

    /* Post-connection messages */
    $flag=false;
    while (!$flag) {
      $content = $this->read();
      foreach($content as $c) {
        $this->debug($c);

        /* PING */
        if (preg_match('/^PING :(.+)$/', $c, $matches)) {
          $this->send('PONG :'. $matches[1]);
          $this->debug("*** ".$this->getShortname()." : PING PONG");
          continue;
        }

        /* AUTOJOIN may have been sent while initializing */
        if (($matches=$this->testPattern('join', $c))!==false) {
          $channel=strtolower($matches['chan']);
          $this->channelsUsers[$channel]=array();
          $this->channels[]=$channel;
        }

        /* subsequently, a userslist may have been sent... */
        if (($matches=$this->testPattern('userslist', $c))!==false || ($matches=$this->testPattern('nickslist', $c))!==false) {
          \bot\events\userslist($matches,$this);
        }

        /* Connection closed */
        if (preg_match('/^ERROR :Closing Link: ([^\ ]+) (.*)$/', $c, $matches)) {
          echo "*** /!\\ ***".$this->getShortname()." Connexion closed, reason : ".$matches[2].PHP_EOL;
          fclose($this->socket);
          $this->connected=false;
          return false;
        }

        /* MODE : tout est OK */
        if (preg_match('/^:'.$this->nickname.' MODE/', $c)) {
          echo $this->getShortname()." : Ready to listen".PHP_EOL;
          $flag=true;
        }
      }
    }

    /* Finally THE USERS SOCKET */
    $this->setUsersListeningSocket();

    /* set IRC Connected ! */
    $this->connected=true;

    /* Try to identify */
    if (! $this->userpass !== "") {
      $this->identify();
    }

    return true;
  }

/*******************************
**          MAIN LOOP
*******************************/

  public function listen(bool $loop=true):bool {
    if (!$this->connected) {
      echo $this->shortname." is not connected".PHP_EOL;
      return false;
    }
    while (!$this->mustStop) { /* Partylines over telnet */
      $this->acceptUser();
      if ($this->hasUser()) {
        $read=$this->usersConnected;
        $write=array();
        $except=array();
        $result=socket_select($read,$write,$except,0); /* non blocking, timeout = 0 */
      } else {
        $result=true;
      }
      if ($result===false) {
        echo "*** ".socket_strerror((socket_last_error()))." ***".PHP_EOL;
      } elseif ($this->hasUser() && $result>0) {
        foreach ($this->usersConnected as $token => $userSocket) {
          $userInput=$this->readUser($token);
          if (is_array($userInput) && count($userInput)>0) {
            if (trim($userInput[0])!="") {
              foreach ($userInput as $line) {
                echo $this->shortname." : ".$line.PHP_EOL;
                \bot\partyline\parseUserInput($token,$line,$this);
              }
            }
          } else {
            if ($userInput=="ERROR") {
              echo "*** Read error : try to close the socket, and remove the user.".PHP_EOL;
            }
            socket_close($this->usersConnected[$token]);
            unset($this->usersConnected[$token]);
          }
        }
      }
      $this->readIRC();
      if (!$loop) break;
    }
    if ($loop || $this->mustStop) { /* Terminated */
      $this->coreQuit();
      $this->connected=false;
      echo "*** ".$this->getShortname()." is now disconnected ***".PHP_EOL;
      return false;
    }
    return true;
  }

  public function stop($quitMsg) {
    if ($quitMsg=="") $quitMsg=$this->quitMsg;
    $this->mustStop=true;
    $this->quitMsg=$quitMsg;
  }

  private function coreQuit() {
    $this->send("QUIT :".$this->quitMsg);
    fclose($this->socket);
  }

  public function exit($quitMsg="") {  /* terminates all connections [/die] */
    if ($this->owner != NULL) {
      foreach ($this->owner->getConnections() as $other) {
        $other->stop($quitMsg);
      }
    } else {
      $this->stop($quitMsg);
    }
  }

  public function hasUser() {
    return count($this->usersConnected)>0;
  }

  public function disconnectUser($token) {
    socket_close($this->usersConnected[$token]);
    unset($this->usersConnected[$token]);
  }

  public function disconnectUsers() {
    foreach ($this->usersConnected as $token => $userSocket) {
      socket_close($userSocket);
      unset($this->usersConnected[$token]);
    }
  }

  public function getUserSocket($token) {
    $s=$this->usersConnected[$token]??null;
    if (!$s) {
      $this->debug("Token no more valid : $token","ERROR");
    }
    return $s;
  }

  public function getUsersSockets() {
    return $this->usersConnected;
  }

  private function readIRC() {
    $content = $this->read();
    foreach($content as $c) {
      /* PING */
      if (preg_match('/^PING :(.+)$/', $c, $data)) {
        $this->send('PONG :'. $data[1]);
        $this->debug("*** ".$this->getShortname()." : PING PONG");
        continue;
      }
      $this->debug($c);

      /* VERSION */
      if (preg_match($this->events['versionmsg'],$c,$data)) {
        if ($this->getOwner())
          $version=$this->getOwner()->getVersionReply();
        else
          $version=\bot\version_reply;
        $reply="NOTICE ".$data['nick']." :VERSION :".$version;
        $this->send($reply);
        continue;
      }

      /* CALL HANDLERS */
      foreach($this->events as $event => $pattern) {
        if (preg_match($pattern, $c, $data)) {
          foreach($this->eventHandlers[$event] as $f) {
            $f($data, $this);
          }
          continue 2;
        }
      }
    }
  }

  /* Link to mirror a chan to another (to use with events) */
  public function addLink(string $from, \bot\IRC $link, string $to) {
    $from=strtolower($from);
    if (!isset($this->linkedChannels[$from])) { /* create */
      $this->linkedChannels[$from]=array();
    }
    $this->linkedChannels[$from][$link->getShortname()]=array("connection" => $link, "channel" => $to);
  }

  public function reformatLinkedMessage(array $data, string $chan, bool $asMe = false) {
    $nick=$data['nick'];
    if ($matches=$this->testPattern("action",$data['msg'])) {
      if (!$asMe) {
        $data['msg']="\00302\002$nick\002\003 ".$matches[1];
        $message="(".$chan.")"." ".$data['msg'];
/*      $message="(".$chan.")".$this->getShortName()." ".$nick." ".$data['msg']; /* sample */
      } else {
        $message=$data['msg']; /* ELSE if sent asMe ($this->ownerNick ==> $this->nickname ==> $otherConn->nickname, message kept the same */
      }
    } else {
      $message="";
      if (!$asMe) {
        $message="(".$chan.")"." \002\x1F".$nick."\x1F\002 ".$data['msg'];
      } else {
        $message.=$data['msg']; /* ELSE same as above */
      }
    }
    return $message;
  }

  public function sendLinkedPrivate(string $chan, string $to, string $msg) {
    foreach ($this->linkedChannels[$chan] as $name => $conn) {
      if ($this->owner && !$this->owner->botSent($name)) {
        $conn['connection']->sendto($to,$msg);
        $this->owner->setBotSent($conn['channel']);
      }
    }
  }

  public function reflectToLink(string $to, array $data, bool $asMe = false) { /* to do what the function name says ;op */
    /* PRIVMSG must use \bot\events\link or \bot\events\restrictedLink explicitely, otherwise we discard mirroring */
    if ((array_search('\bot\events\link',$this->eventHandlers["privmsg"])===FALSE && !$asMe) || ($asMe && array_search('\bot\events\restrictedLink',$this->eventHandlers["privmsg"])===FALSE) )
      return;
    /* VERSION is discarded too */
    if ($this->testPattern("versiononly",$data['msg'])) {
      return;
    }

    /***********************************************
    *  To avoid ping pong !!
    ************************************************/
    /* /!\ This depends on reformatLinkedMessage settings !! */
    $regex="/^\(".chanPattern."\) \002\x1F(?P<nick>[^ ]*)\x1F\002/U";
    if (preg_match($regex,$data['msg'],$m)) {
      echo "** avoiding pink pong **".PHP_EOL;
      return;
    }
    /***********************************************/

    $nick=$data['nick'];
    if (isset($this->linkedChannels[$to])) { /* Message for a channel */
      /* reformat for message or action */
      $message=$this->reformatLinkedMessage($data,$to,$asMe);
      /* SEND ONE TIME ONLY */
      foreach ($this->linkedChannels[$to] as $name => $conn) {
        if ($this->owner && !$this->owner->botSent($name)) {
          $conn['connection']->sendto($conn['channel'],$message);
          $this->owner->setBotSent($conn['channel']);
        }
      }
    } elseif ($to==$this->nickname) { /* PRIVATE MSG for this connection */
      $msg_words=preg_split("@ @",$data['msg']);
      $msg_to=\bot\stripControlCodes($msg_words[0]);
      unset($msg_words[0]);
      /* reformat for message or action */
      $mpnochan=false; /* if a PM is allowed even if the users is not on a channel */
      if ($msg_to[0]=="@") {
        $message=\join(" ",$msg_words);
        $data['msg']=$message;
        $msg_to=substr($msg_to,1); /* messages must be prefixed by @ => @To_nick */
        $data['to']=$msg_to;

        /* Send to all linked connections */
        $message=$this->reformatLinkedMessage($data,$to,true); /* as Me */
        if (!\bot\events\isChan($msg_to)) {
          $message="(".$data['nick'].") ".$message;
        }
        foreach ($this->channels as $chan) {
          if (array_key_exists($chan,$this->linkedChannels)) {
            foreach ($this->linkedChannels[$chan] as $name => $conn) {
              if (!$this->owner->botSent($name)) {
                $conn['connection']->sendTo($msg_to,$message);
                echo "Sent message to $msg_to on ".$conn['connection']->getShortname().PHP_EOL;
                $this->owner->setBotSent($name); /* to avoid further PM to $conn['connection'] */
              }
            }
          }
        }
      } else {
        /* search linked channel */
        foreach ($this->channels as $chan) {
          if (array_key_exists($chan,$this->linkedChannels)) {
            foreach ($this->linkedChannels[$chan] as $name => $conn) {
              if (!$this->owner->botSent($name)) {
                $data['to']=$chan;
                $message="\x0303\x02--".$data['nick']."--\x03\x02 ".$data['msg'];
                $conn['connection']->sendTo($conn['channel'],$message);
                echo "Sent message to ".$conn['channel']." on ".$conn['connection']->getShortname().PHP_EOL;
                $this->owner->setBotSent($name); /* to avoid further PM to $conn['connection'] */
              }
            }
          }
        }
      }

    }
  }

  public function addEventHandler(string $event,string $f="") {
    if ($f=="")
      $f="\\".__NAMESPACE__."\\events\\".$event;
    array_push($this->eventHandlers[$event], $f);
  }

  public function send(string $content):bool {
    if (preg_replace("@[\r|\n]@","",$content)=="") return false;
    try {
      fputs($this->socket, $content . (!preg_match('/[\n]$/', $content)?END:''));
    } catch (\Exception $ex) {
      $this->debug("Not sent to ".$this-getShortName()." -> exit","ERROR");
      \bot\partyline\sendReplyAll("** ERROR ** content not sent : ".$content,$this);
      $this->exit();
      return false;
    }
    return true;
  }

  public function sendTo(string $to, string $line, $conn=false, $notice=false):bool {
    if ($to[0]=="#" || $to[0]=="&") {
      $to=strtolower($to);
      if (array_search($to,$this->channels)===false) {
        echo "** not connected to $to **".PHP_EOL;
        \bot\partyline\sendReplyAll("** not connected to $to **",($conn===false?$this:$conn));
        return false;
      }
    }
    if (!$notice) {
      $content="PRIVMSG $to :$line";
    } else {
      $content="NOTICE $to :$line";
    }
    fputs($this->socket, $content . (!preg_match('/[\n]$/', $content)?END:''));
    $data=array("to" => $to, "msg" => $line, "nick" => $this->getNickname());
    \bot\saveMessage($data, $this);
    \bot\events\link($data, $this);
    return true;
  }

  /* Other connection */
  public function sendToOther(string $other, string $to, string $line, $notice=false):bool {
    if ($this->owner==NULL) {
      echo "** no other connection available **".PHP_EOL;
      \bot\partyline\sendReplyAll("** no other connection available **",$this);
      return false;
    }
    $conn=$this->owner->getConnection($other);
    if ($conn===false) {
      echo "** this connection is not available : $other **".PHP_EOL;
      \bot\partyline\sendReplyAll("** this connection is not available : $other **",$this);
      return false;
    }
    return $conn->sendTo($to, $line, $this, $notice);
  }

  public function getConnections():array { /* get available other connections */
    if ($this->owner==NULL) return array();
    $result=array();
    foreach ($this->owner->getConnections() as $name => $co) {
      if ($co->isConnected())
        $result[]=$name;
    }
    return $result;
  }

  private function read($bufferSize = 65535):array {
    $content = fread($this->socket, $bufferSize);
    if (!preg_match('/[\n]$/', $content)) {
      $content .= fgets($this->socket, $bufferSize);
    }
    return explode(END, trim($content));
  }

  public function addChanUser(string $chan, string $user) {
    $chan=strtolower($chan);
/*    $this->debug("Adding $user to $chan"); */
    if (!isset($this->channelsUsers[$chan])) {
      $this->debug("Creating users list for $chan while adding $user","Notice");
      $this->channelsUsers[$chan]=array();
    }
    if (isset($this->channelsUsers[$chan]) && !$this->chanUserExists($chan,$user)) {
      $this->channelsUsers[$chan][]=$user;
      natcasesort($this->channelsUsers[$chan]);
    }
  }

  public function removeChanUser(string $chan, string $user) {
    if (isset($this->channelsUsers[$chan])) {
      $this->channelsUsers[$chan]=array_diff($this->channelsUsers[$chan],array($user));
      if (count($this->channelsUsers[$chan])==0)
        $this->debug("Users list for $chan has been emptied","ERROR");
    } else
      $this->debug("$chan users list does not exist","ERROR");
    $this->debug("$user removed from $chan users list","Notice");
  }

  public function changeUserNick(string $user, string $newnick) { /* returns impacted channels */
    $chans=array();
    foreach ($this->channelsUsers as $chan => $users) {
      if (($index=array_search($user,$users))!==false) {
        $this->channelsUsers[$chan][$index]=$newnick;
        $chans[]=$chan;
      }
    }
    $this->debug("Changement $user -> $newnick","Notice");
    return $chans;
  }

  public function chanUserExists($chan,$user) {
    if (isset($this->channelsUsers[$chan])) {
      return (array_search($user,$this->channelsUsers[$chan])!==false);
    } else
      return false;
  }

  private function setUsersListeningSocket() {
    $s=socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
    if ($s!==false) {
      socket_set_nonblock($s); /* non-blocking */
      socket_set_option($s, SOL_SOCKET, SO_REUSEADDR, 1);
      socket_bind($s,"127.0.0.1",$this->userPort);
      if (socket_listen($s,$this->maxUsers)===false) { /* max 5 connections */
        $this->debug(socket_strerror(socket_last_error()),"ERROR");
        die();
      }
      $this->usersListeningSocket=$s;
      echo $this->shortname." : NOW waiting for connections on port ".$this->userPort.PHP_EOL;
    } else {
      $this->debug("Impossible de créer une socket d'écoute pour un user...","ERROR");
      echo socket_strerror(socket_last_error()).PHP_EOL;
      die();
    }
  }

  public function getPrompt() {
    return $this->defaultChan."(".$this->shortname.")> ";
  }

  private function acceptUser() {
    if (count($this->usersConnected)<$this->maxUsers && ($s=socket_accept($this->usersListeningSocket))!==false) {
      $token=++$this->userTokenCounter;
      echo $this->shortname." : user connected, id=".$this->userTokenCounter.PHP_EOL;
      $this->usersConnected[$token]=$s;
      socket_write($this->usersConnected[$token],$this->getPrompt());
    }
  }

  private function readUser($token, $bufferSize = 8192) {
    if (!$this->hasUser()) {
      return array("");
    }
    try {
      $buf = socket_read($this->getUserSocket($token), $bufferSize);
      if ($buf!==false) {
        if (!preg_match('/[\r\n]$/', $buf)) {
          $buf .= socket_read($this->getUserSocket($token), $bufferSize);
        }
        return explode(END, trim($buf));
      }
    } catch (\Exception $ex) {
      return "ERROR";
      echo "****** ERROR ******".PHP_EOL;
    }
    return array("");
  }

}
