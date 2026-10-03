<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['minimum_target_php_version'] = '8.3';
$cfg['suppress_issue_types'] = array_values( array_filter(
	$cfg['suppress_issue_types'],
	static fn ( string $issue ): bool => !str_starts_with( $issue, 'PhanDeprecated' )
) );

return $cfg;
