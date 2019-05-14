#!/usr/local/bin/php
<?php

define('BN_FILES_DIRECTORY', '/root/mysql/mycc/bn_robot/bulletin');
define('BN_INI_DIRECTORY', '/root/mysql/mycc/bn_robot/bulletin');
define('KV_FILES_DIRECTORY', '/root/mysql/mycc/bn_robot/kvart');
$DELETE = true; //okay to delete imap messages

require('/home/bsnrobot/mailboxer.class');
$mailboxer = new mailboxer('pop3.sweb.ru:110', 'bsn.ru+bn_robot', 'Mhfw_3._3rg');

//print_r($mailboxer->fetch_message(18));die();
if ($mailboxer->msg_count() > 0)
{
	for ($current_msgno = 1; $current_msgno <= $mailboxer->msg_count(); $current_msgno++)
	{
		$msg_info = $mailboxer->fetch_message($current_msgno);
		if ($msg_info->from_addr == 'bn@bnmail.ru')
		{
			foreach ($msg_info->parts as $part)
			{
				if (isset($part->filename))
				{
					if (preg_match("/BULL_[0-9]+\.ZIP/i", $part->filename))
					{
						$fp = fopen(BN_FILES_DIRECTORY . "/" . strtoupper($part->filename), "w");
						fwrite($fp, $part->content);

						$fp = fopen(BN_INI_DIRECTORY . "/bn_on_bsn.ini", "w");
						fwrite($fp, strtoupper($part->filename)."\n");
						$fp = fopen(BN_INI_DIRECTORY . "/bsn.ini", "w");
						fwrite($fp, strtoupper($part->filename)."\n");
						$fp = fopen(BN_INI_DIRECTORY . "/bulletin.ini", "w");
						fwrite($fp, strtoupper($part->filename)."\n");
					}
				}
			}
		}
		else if ($msg_info->from_addr == 'tss@bnmail.ru')
		{
			foreach ($msg_info->parts as $part)
			{
				if (isset($part->filename))
				{
					if (preg_match("/KVART\.ARJ/i", $part->filename))
					{
						$fp = fopen(KV_FILES_DIRECTORY . "/" . 'KVART_'.date('Y-M-d_H:i', strtotime($msg_info->date)).'.ARJ', "w");
						fwrite($fp, $part->content);
					}
				}
			}
		}

		$mailboxer->delete($current_msgno);
	}
}

// arj -> tar.bz2 converter
$cont = scandir(KV_FILES_DIRECTORY);
foreach($cont as $c)
{
	if(preg_match("/^(KVART.*)\.ARJ$/", $c, $matches))
	{
		$filename = $matches[1];
		mkdir(KV_FILES_DIRECTORY.'/tmp');
		rename(KV_FILES_DIRECTORY.'/'.$c, KV_FILES_DIRECTORY.'/tmp/'.$c);

		chdir(KV_FILES_DIRECTORY.'/tmp');
		system('/usr/local/bin/arj e '.KV_FILES_DIRECTORY.'/tmp/'.$c);
		unlink($c);
		system('/bin/tar -cf tmp.tar *');
		system('/usr/bin/bzip2 -9v tmp.tar');
		rename('tmp.tar.bz2', '../'.$filename.'.tar.bz2');
		system('rm *');
		rmdir(KV_FILES_DIRECTORY.'/tmp');
	}
}

unset($mailboxer);
?>
