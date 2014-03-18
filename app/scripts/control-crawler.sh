#!/bin/sh
if ps -ef | grep -v grep | grep item:crawling ; then
    exit 0
else
    /var/www/nps/app/console item:crawling -e prod &
    exit 0
fi