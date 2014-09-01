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

$GLOBALS['wgMessagesDirs']['Sanat'] = "$dir/i18n";

$GLOBALS['wgHooks']['BeforePageDisplay'][] = function ( OutputPage $out ) {
	$out->addModuleStyles( 'ext.sanat.styles' );
	$out->addModules( 'ext.sanat' );
};
