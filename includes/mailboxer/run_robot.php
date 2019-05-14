#!/usr/local/bin/php
<?php

## MAILBOXER PART ##
exec('ps aux | grep -e \'mailboxer\.php\'',$running);
if(sizeof($running) == 0)
{
    exec('~/mailboxer.php --quiet=yes --delete=yes');
}

## MAINPARSER PART ##
exec('ps aux | grep -e \'mainparser\.php\'',$running);
if(sizeof($running) == 0)
{
    exec('~/mainparser.php --quiet=no --delete=yes --enable_plain_bn_all --enable_plain_bn_ned --enable_plain_bn_kn --enable_plain_bn_zdd --enable_plain_bn_ard --enable_nevdom_live_rent');
}

## FINAL STAGE PART ##
exec('ps aux | grep -e \'final_stage\.php\'',$running);
if(sizeof($running) == 0)
{
    exec('~/final_stage.php --quiet=yes --delete=yes');
}


?>