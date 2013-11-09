#!/bin/sh

modsecs=0
modesecsadmin=0

if [ -f /var/www/nps/app/logs/prod.log ]; then
    modsecsadmin=$(date --utc --reference=/var/www/nps/app/logs/prod.log +%s)
fi

nowsecs=$(date +%s)
delta=$(($nowsecs-$modsecs))
deltaadmin=$(($nowsecs-$modsecsadmin))

if [ $delta -lt 280 ]; then
  # do something
  # echo "File prod.log was modified $delta secs ago"
  cat /var/www/nps/app/logs/prod.log | mail -s "Production WEB log" dpcat237@gmail.com
  echo "" > /var/www/nps/app/logs/prod.log
fi