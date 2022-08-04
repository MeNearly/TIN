<?php

namespace bot;
require("IRC.php");

class Bot {
  private $version_reply;

  private $connections;

  private $connectionsChannels;

  private $botSent;

  public function __construct(string $version=\bot\version_reply) {
    $this->version_reply=$version;
    $this->connections=array();
    $this->connectionsChannels=array();
    $this->connectionsChannelsKeys=array();
    $this->botSent=array();
  }

  public function getVersionReply(): string {
    return $this->version_reply;
  }

  public function resetBotSent() {
    $this->botSent=array();
    foreach ($this->connections as $name => $conn) {
      $this->botSent[$name]=false;
    }
  }

  public function setBotSent(string $channel) {
    $this->botSent[$channel]=true;
  }

  public function botSent(string $channel):bool {
    return $this->botSent[$channel];
  }

  public function addConnection(\bot\IRC &$conn=NULL) {
    if (!$conn) {
      die("Cannot add a NULL connection....");
    }
    $conn->setOwner($this);
    $this->connections[$conn->getShortname()]=$conn;
  }

  public function getConnections():array {
    return $this->connections;
  }

  public function getConnection(string $shortname) {
    return $this->connections[strtolower($shortname)]??false;
  }

  public function stopConnection(string $name) {
    if (isset($this->connections[$name])) {
      $conn=$this->connections[$name];
      $conn->stop();
      unset($this->connections[$name]);
    } else {
      echo "Error, the connection does not exist : $name".PHP_EOL;
    }
  }

  public function addChannels(string $name,array $chans=array(),array $keys=array()) {
    if (count($chans)>0) {
      if (count($keys)>0 && count($keys)!=count($chans)) {
        echo "CRITICAL : The supplied keys count is different from the channels count for $name...";
        die("critical error");
      }
      if (!isset($this->connectionsChannels[$name]))
        $this->connectionsChannels[$name]=array();
      if (!isset($this->connectionsChannelsKeys[$name]))
        $this->connectionsChannelsKeys[$name]=array();
      foreach ($chans as $chan)
        $this->connectionsChannels[$name][]=strtolower($chan);
      if (count($keys)>0) {
        foreach ($keys as $key)
          $this->connectionsChannelsKeys[$name][]=$key;
      } else {
        for ($i=0;$i<count($chans);$i++)
          $this->connectionsChannelsKeys[$name][]="";
      }
    }
  }
/****************
***** MAIN ******
*****************/
  public function Start() {
    $flags=array();
    $atLeastOne=false;
    foreach ($this->connections as $conn) {
      $name=$conn->getShortname();
      echo "Starting $name...".PHP_EOL;
      if ($conn->connect() && $conn->join($this->connectionsChannels[$name],$this->connectionsChannelsKeys[$name])) {
        $flags[$name]=true;
        $atLeastOne=true;
      } else {
        $flags[$name]=false;
      }
    }
    echo "***************************************************".PHP_EOL;
    echo "Début de l'écoute....".PHP_EOL;
    echo "***************************************************".PHP_EOL;
    while ($atLeastOne) {
      $atLeastOne=false;
      foreach ($this->connections as $conn) {
        $name=$conn->getShortname();
        $this->resetBotSent();
        if ($flags[$name] && $conn->connected) {
          $atLeastOne|=$conn->listen(false);
        }
      }
    }
    echo "Start ending TIN...".PHP_EOL;
    echo "-> Zip older files...".PHP_EOL;
    \bot\messages\zipOlderFiles();
    echo "TIN Bot ended".PHP_EOL;
    unlink("./pid.tinBot");
  }
}

