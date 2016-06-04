#!/bin/sh
if ps -ef | grep -v grep | grep item:import ; then
    exit 0
else
    /var/www/newpsel/app/console item:import -e prod &
    exit 0
fi
