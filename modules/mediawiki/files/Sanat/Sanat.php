<?php
/**
 * Special additions for sanat.csc.fi
 *
 * @author Niklas Laxström
 * @license MIT
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

$GLOBALS['wgExtensionCredits']['other'][] = array(
	'path' => __FILE__,
	'name' => 'Sanat',
	'version' => '2014-06-04',
	'author' => 'Niklas Laxström',
);

$dir = __DIR__;
require_once "$dir/Resources.php";

$GLOBALS['wgMessagesDirs']['Sanat'] = $GLOBALS['IP'] . '/l10n';

$GLOBALS['wgHooks']['BeforePageDisplay'][] = function ( OutputPage $out ) {
	$out->addModules( 'ext.sanat.search' );
	$out->addModuleStyles( 'ext.sanat' );
};
