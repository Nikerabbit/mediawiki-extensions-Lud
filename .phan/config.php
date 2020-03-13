<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

// These are too spammy for now. TODO enable
//$cfg['null_casts_as_any_type'] = true;
//$cfg['scalar_implicit_cast'] = true;

$cfg['file_list'] = array_merge(
	$cfg['file_list'],
	[
		'FindPages.php',
		'KeskiLyydiTabConverter.php',
		'KirjaLyydiConverter.php',
		'KirjaLyydiTabConverter.php',
		'ListPages.php',
		'Lud.php',
		'LudHooks.php',
		'LudImport.php',
		'LyydiConverter.php',
		'LyydiFormatter.php',
		'LyydiTabConverter.php',
	]
);

return $cfg;
