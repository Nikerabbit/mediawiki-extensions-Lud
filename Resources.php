<?php
/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */

global $wgResourceModules;

$resourcePaths = [
	'localBasePath' => __DIR__,
	'remoteExtPath' => 'Sanat'
];

$wgResourceModules['ext.sanat.styles'] = [
	'styles' => 'resources/ext.sanat.less',
] + $resourcePaths;

$wgResourceModules['ext.sanat'] = [
	'scripts' => 'resources/ext.sanat.js',
	'dependencies' => 'mediawiki.util',
	'messages' => 'sanat-inlinetools-delete-example',
] + $resourcePaths;
