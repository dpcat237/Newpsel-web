#!/bin/sh
ps -eaf | grep item:crawling
if [ $? -eq 1 ]
then
/var/www/nps/app/console item:crawling &
fi