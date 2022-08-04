#!/usr/bin/bash
cd /home/xylian/monitoring
if test -e log.tinBot.txt;then
  suffix=$(date +_%d_%m_%y)
  zip archives/logTinBot${suffix}.zip log.tinBot.txt
else
  echo "No current TIN Bot log !"
  exit 1
fi
