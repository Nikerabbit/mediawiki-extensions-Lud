<?php
# This file is managed by puppet

# Protect against web entry
if ( !defined( 'MEDIAWIKI' ) ) {
	exit;
}

$IP = __DIR__;

$VHOST = 'sanat.csc.fi';

ini_set( 'error_log', "/www/$VHOST/logs/error_php" );
ini_set( 'display_errors',         0 );
ini_set( 'ignore_repeated_errors', 1 );
ini_set( 'log_errors',             1 );
ini_set( 'expose_php',             0 );
ini_set( 'memory_limit',      '350M' );
ini_set( 'max_execution_time', '60s' );

error_reporting( E_ALL | E_STRICT );
date_default_timezone_set( 'UTC' );

$wgShowExceptionDetails  = true;
$wgDebugComments         = true;
$wgDevelopmentWarnings   = true;
$wgDebugTimestamps       = true;
$wgDebugPrintHttpHeaders = false;

$wgCacheDirectory = "/www/$VHOST/cache";
$wgLocalisationCacheConf['store'] = 'file';
$wgLocalisationCacheConf['manualRecache'] = true;

$wgSecretKey = trim( file_get_contents( "/www/$VHOST/secret_key" ) );

if ( is_readable( "/www/$VHOST/docroot/logo.png" ) ) {
	$wgLogo = "/logo.png";
}

$wgSitename = "Sanat";
$wgArticlePath = "/wiki/$1";
$wgScriptPath = "/w";

## Database settings
$wgDBtype = "mysql";
$wgDBserver = "localhost";
$wgDBname = "mediawiki";
$wgDBuser = "mediawiki";
$wgDBpassword = trim( file_get_contents( "/www/$VHOST/dbpass" ) );
$wgDBprefix = "";
$wgDBTableOptions = "ENGINE=InnoDB, DEFAULT CHARSET=binary";

## Shared memory settings
$wgMainCacheType = CACHE_MEMCACHED;
$wgMemCachedServers = array( 'localhost:11211' );
$wgSessionsInObjectCache = true;

$wgShellLocale = "en_US.utf8";
$wgDiff3 = "/usr/bin/diff3";
$wgJobRunRate = 0;

$wgLanguageCode = 'fi';
$wgDefaultUserOptions['usenewrc'] = 1;

enableSemantics( $VHOST );
$sfgRedLinksCheckOnlyLocalProps = true;
$sfgRenameEditTabs = true;

$wgGroupPermissions['sysop']['invitesignup'] = true;
$wgGroupPermissions['*']['edit'] = false;
$wgGroupPermissions['*']['createaccount'] = false;
$wgGroupPermissions['sysop']['deletebatch'] = true;

$wgUseRCPatrol = false;
$wgUseNPPatrol = false;

$smwgQMaxInlineLimit = $wgCategoryPagingLimit = 250;

include_once "$IP/extensions/ParserFunctions/ParserFunctions.php";
include_once "$IP/extensions/DeleteBatch/DeleteBatch.php";
include_once "$IP/extensions/Sanat/Sanat.php";

function lfAddNamespace( $id, $name ) {
	global $wgExtraNamespaces, $wgContentNamespaces, $wgNamespacesToBeSearchedDefault,
		$wgCapitalLinkOverrides, $smwgNamespacesWithSemanticLinks;

	$constant = strtoupper( "NS_$name" );

	define( $constant, $id );
	define( $constant . '_TALK', $id + 1 );

	$wgExtraNamespaces[$id] = $name;
	$wgExtraNamespaces[$id + 1] = $name . '_talk';

	$wgContentNamespaces[] = $id;

	$wgNamespacesToBeSearchedDefault[$id] = true;

	$wgCapitalLinkOverrides[1200] = false;
	$wgCapitalLinkOverrides[1201] = false;

	$smwgNamespacesWithSemanticLinks[1200] = true;
}

lfAddNamespace( 1200, 'Lud' );
