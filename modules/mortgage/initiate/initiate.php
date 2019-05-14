<?php
require_once dirname(__FILE__).'/bsnclient.php';

bsnClient::setInitialOptions(array(
    'pid'           => 'BSN45jts345GJR3',
    'cache_dir'     => dirname(__FILE__).'/cache/'
));