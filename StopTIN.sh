#!/usr/bin/bash
cd /path/to/TIN
if test -e pid.tinBot;then
  echo "Killing TIN Bot"
  kill -9 $(more pid.tinBot)
  rm pid.tinBot
  exit 1
else
  echo "TIN Bot is not currently running."
  exit 0
fi
