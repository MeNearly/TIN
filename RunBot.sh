#!/usr/bin/php
<?php
/***************************************
** Script de lancement du robot       **
** (c) 2022 MeNearly@gmail.com GPL    **
****************************************/
/**********
** V1.1b **
***********/

require_once('Bot.php');

$Bot=new \bot\Bot();


// On crée la connection à Server one
$conn1 = new \bot\IRC("irc.first.server", 6697, true, "server_one", 1807);
$conn1->setDebug(true);
$conn1->setDefaultChan("#myChannel");
$conn1->setIdentity("Nick","Handler","Realnam","MyPassword");
$conn1->addEventHandler('userslist');
$conn1->addEventHandler('privmsg');
$conn1->addEventHandler('privmsg','\bot\events\link'); /* to allow links */
$conn1->addEventHandler('notice');
$conn1->addEventHandler('nick');
$conn1->addEventHandler('join');
$conn1->addEventHandler('part');
$conn1->addEventHandler('quit');
$conn1->addEventHandler('kick');
$conn1->addEventHandler('ban');
$conn1->addEventHandler('unban');
$conn1->addEventHandler('server_unban','\bot\events\unban');
$conn1->addEventHandler('voice');
$conn1->addEventHandler('devoice');
/* Doit être ajouté à la fin, car trop générique et peut capturer
  un autre message avec un formatage comportant des nombres !! */
$conn1->addEventHandler('servmsg');

// On ajoute server_one
$Bot->addConnection($conn1);
$Bot->addChannels("server_one",array("#myChannel"));

// On crée la connection à Xylian

$conn2 = new \bot\IRC("irc.second.server", 6697, true, "server_two", 1807, "server_pass", "server_two user reflected to other connection");
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


// On ajoute server_2
$Bot->addConnection($conn2);
$Bot->addChannels("server_two",array("#myChannel-mirror"));

// Links
$conn1->addLink("#myChannel",$conn2,"#myChannel-mirror");
// This is used but the event handling \bot\evens\restrictedLink
$conn2->addLink("#myChannel-mirror",$conn1,"#myChannel");


$Bot->Start();
