#!/bin/sh

# This file is sheduled by bsnrobot crontab at 09:00 every day

# Backup of queue files
dirname=`date +%y.%m.%d_%H:%M:%S`

# /queue/input
cd ~/queue/input/
fcount=`ls *.xml 2>/dev/null | wc -l`

if [ $fcount != '0' ]
then
    mkdir $dirname
    mv *.xml $dirname
fi

# /queue/final
cd ~/queue/final/
fcount=`ls *.xml 2>/dev/null | wc -l`

if [ $fcount != '0' ]
then
    mkdir $dirname
    mv *.xml $dirname
fi

# Launch robots
#~/mailboxer.php --quiet=yes --delete=yes
#~/mainparser.php --quiet=no --delete=no --enable_plain_bn_all --enable_plain_bn_ned --enable_plain_bn_kn -enable_plain_bn_zdd --enable_plain_bn_ard --enable_nevdom_live_rent
#~/final_stage.php