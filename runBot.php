#!/usr/bin/php
<?php
/*********************************
** Script de lancement du robot **
** (c) 2020 ian@sibian.fr GPL   **
**********************************/
/**********
** V1.1a **
***********/

require_once('Bot.php');

$Bot=new \bot\Bot();

if (\bot\archiveMode!=1 && \bot\archiveMode!=2) {
  echo "*********************************".PHP_EOL;
  echo "**           ERREUR !!         **".PHP_EOL;
  echo "** AUCUN ARCHIVAGE SÉLECTIONNÉ **".PHP_EOL;
  echo "*********************************".PHP_EOL;
  die();
}

// On crée la connection à Chaat
$conn1 = new \bot\IRC("irc.chaat.fr", 6697, true, "chaat", 1807);
$conn1->setDebug(true);
$conn1->setDefaultChan("#momo");
$conn1->setDefaultChan("#adultes");
$conn1->setIdentity("GolanTrevize","Golan","40 H Comporellon","");
$conn1->addEventHandler('privmsg','\bot\link');
$conn1->addEventHandler('privmsg');
$conn1->addEventHandler('nick');
$conn1->addEventHandler('join');
$conn1->addEventHandler('userslist');
$conn1->addEventHandler('part');
$conn1->addEventHandler('quit');
$conn1->addEventHandler('kick');


// On ajoute chaat
$Bot->addConnection($conn1);
$Bot->addChannels("chaat",array("#lesanciensdelirc","#adultes","#test_scrabble",/*"#accueil","#momo","#Crazy"*/));

// On crée la connection à EpkInet

$conn2 = new \bot\IRC("irc.epiknet.org", 6667, false, "epiknet", 1808);
$conn2->setDebug(false);
$conn2->setDefaultChan("#seldon");
$conn2->setIdentity("Golan","Golan Trevize","35 M Comporellon","");
$conn2->addEventHandler('privmsg');
$conn2->addEventHandler('privmsg','\bot\link');
$conn2->addEventHandler('nick2');
$conn2->addEventHandler('join');
$conn2->addEventHandler('userslist');
$conn2->addEventHandler('part');
$conn2->addEventHandler('quit');
$conn2->addEventHandler('kick');

// On ajoute epik
$Bot->addConnection($conn2);
$Bot->addChannels("epiknet",array("#seldon"));

// Links
/*$conn1->addLink("#adultes",$conn1,"#test_scrabble");
$conn2->addLink("#seldon",$conn1,"#test_scrabble");
$conn1->addLink("#momo",$conn1,"#test_scrabble");
$conn1->addLink("#test_scrabble",$conn2,"#seldon");
$conn2->addLink("#35+ans",$conn1,"#test_scrabble");
$conn1->addLink("#test_scrabble",$conn2,"#seldon");

*/
$Bot->Start();
