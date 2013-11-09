#!/bin/sh
PCOUNT=`ps -eaf | grep item:crawling | wc -l`
if [ $PCOUNT -eq 1 ]; then
    /var/www/nps/app/console item:crawling &
fi