<?php
/**
 * @author Niklas Laxström
 * @license MIT
 */

global $wgResourceModules;

$resourcePaths = array(
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'Sanat'
);

$wgResourceModules['ext.sanat'] = array(
	'styles' => 'resources/ext.sanat.less',
) + $resourcePaths;

$wgResourceModules['ext.sanat.search'] = array(
	'styles' => 'resources/ext.sanat.search.less',
	'scripts' => 'resources/ext.sanat.search.js',
	'dependencies' => array(
		'mediawiki.searchSuggest',
		'mediawiki.Title',
	),
) + $resourcePaths;
