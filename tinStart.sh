#!/usr/bin/bash
cd ~/TIN
if test -e pid.tinBot;then
  echo "TIN Bot seems to be already running..."
  exit 1
fi
./RunBot.sh 2>&1 >> log.tinBot.txt &
echo $! > pid.tinBot
echo "TIN Bot started"
