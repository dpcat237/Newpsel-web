#!/bin/sh

modsecs=0

if [ -f /var/www/newpsel/app/logs/prod.log ]; then
    modsecs=$(date --utc --reference=/var/www/newpsel/app/logs/prod.log +%s)
fi

nowsecs=$(date +%s)
delta=$(($nowsecs-$modsecs))

if [ $delta -lt 280 ]; then
  # do something
  # echo "File prod.log was modified $delta secs ago"
  cat /var/www/newpsel/app/logs/prod.log | mail -s "Production WEB log" dpcat237@gmail.com
  echo "" > /var/www/newpsel/app/logs/prod.log
fi
