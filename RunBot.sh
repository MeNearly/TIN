#!/usr/bin/php
<?php
/*****************************************
** Script de lancement du robot         **
** (c) 202x MeNearly@gmail.com GPL2     **
*****************************************/
/**********
** V1.2c **
***********/

require_once('Bot.php');

$Bot=new \bot\Bot(version: "TIN is neat !", pidFile: "./pid.myBotname");


// On crée la connexion à Server one
$conn1 = new \bot\IRC("irc.first.server", 6697, true, "server_one", 1807);
$conn1->setDebug(true);
$conn1->setDefaultChan("#myChannel");
$conn1->setIdentity("Nick","Handler","Realname","MyPassword");
$conn1->addEventHandler('userslist');
$conn1->addEventHandler('privmsg');
$conn1->addEventHandler('privmsg','\bot\events\link'); /* to allow links */
$conn1->addEventHandler('notice');
$conn1->addEventHandler('nick');
$conn1->addEventHandler('join');
/* On peut aussi faire ceci */
//$conn1->addCapability('extended-join');
//$conn1->addEventHandler('ejoin','\bot\events\join');
$conn1->addEventHandler('part');
$conn1->addEventHandler('quit');
$conn1->addEventHandler('kick');
$conn1->addEventHandler('ban');
$conn1->addEventHandler('unban');
$conn1->addEventHandler('server_unban','\bot\events\unban');
$conn1->addEventHandler('voice');
$conn1->addEventHandler('devoice');
$conn1->addEventHandler('servmsg');
$conn1->addEventHandler('other_servmsg');

$conn1->addPerforms("NICK Suffix"); /* example for the nicksuffix module w/ unreal */
//$conn1->addPerforms("NICK myNewNick|afk");

// On ajoute server_one
$Bot->addConnection($conn1);
$Bot->addChannels("server_one",array("#myChannel"));

// On crée la connexion à Server Two

$conn2 = new \bot\IRC("irc.second.server", 6697, true, "server_two", 1807, "server_pass", array("botAdmin1","botAdmin2"));
$conn2->setDebug(true);
$conn2->setDefaultChan("#myChannel-mirror");
$conn2->setIdentity("Nick","Handler","Real Name","myPassword","NickServ_Nickname");
$conn2->addEventHandler('userslist');
$conn2->addEventHandler('privmsg');
$conn2->addEventHandler('privmsg','\bot\events\restrictedLink');
$conn2->addEventHandler('notice');
$conn2->addEventHandler('nick');
$conn2->addEventHandler('join');
$conn2->addEventHandler('part');
$conn2->addEventHandler('quit');
$conn2->addEventHandler('kick');
$conn2->addEventHandler('ban');
$conn2->addEventHandler('unban');
$conn2->addEventHandler('voice');
$conn2->addEventHandler('devoice');
$conn2->addEventHandler('servmsg');

$conn2->addPerforms("privmsg botAdmin1 :I'm alive !");


// On ajoute server_2
$Bot->addConnection($conn2);
$Bot->addChannels("server_two",array("#myChannel-mirror"));

// Links
$conn1->addLink("#myChannel",$conn2,"#myChannel-mirror");
// This is not used but with the event handling \bot\events\restrictedLink
$conn2->addLink("#myChannel-mirror",$conn1,"#myChannel");


$Bot->Start();
