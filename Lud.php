<?php
/**
 * Special additions for Lud in sanat.csc.fi
 *
 * @author Niklas Laxström
 * @license MIT
 */

if ( !defined( 'MEDIAWIKI' ) ) {
	die();
}

$GLOBALS['wgExtensionCredits']['other'][] = array(
	'path' => __FILE__,
	'name' => 'Lud',
	'version' => '2016-12-17',
	'author' => 'Niklas Laxström',
);

$dir = __DIR__;
require_once "$dir/Resources.php";

$GLOBALS['wgMessagesDirs']['Lud'] = "$dir/i18n";

$GLOBALS['wgHooks']['BeforePageDisplay'][] = function ( OutputPage $out ) {
	$out->addModuleStyles( 'ext.sanat.styles' );
	$out->addModules( 'ext.sanat' );
};
