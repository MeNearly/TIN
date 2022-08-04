# TIN
Tin(y) IRC Node

An archiving bot and telnet client for IRC

## Archiving 
Its main purpose is to archive day by day some channels.
It's designed to be multi-server, and it can 'link' channels to reflect the content of one to another, either on the same server or another.

## Client
It allows telnet local connections in order to act like a simplistic normal client (handling messages, private or not, notice, action)...

## Viewing
Every logged channel may be viewed in HTML format (day by day), and a live version is viewable too.
Log files are zipped every day at midnight.

*All written in php, and javascript (viewing)*

## Installing
Obviously clone the repository.
In order to display the channel, create a directory into your html files directory, then symlink **events.php functions.php IRC.php messages.php params.php refresh.php view.php export.php index.php mirc_colors.php partyline.php refreshView.php** in it, and symlink **mirc.css.php params.php tabs.css.php** into *css* subdirectory, and finally **params.php refresh.js.php** into the *js* one.

You MUST create an tinBot.pem file (in order to use secure connections) !!
e.g. ```cp fullchain.pem tinBot.pem;cat privkey.pem >> tinBot.pem```

/!\ You'll have to edit some files
