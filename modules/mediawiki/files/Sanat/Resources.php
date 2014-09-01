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

$wgResourceModules['ext.sanat.styles'] = array(
	'styles' => 'resources/ext.sanat.less',
) + $resourcePaths;

$wgResourceModules['ext.sanat'] = array(
	'scripts' => 'resources/ext.sanat.js',
	'dependencies' => 'mediawiki.util',
	'messages' => 'sanat-inlinetools-delete-example',
) + $resourcePaths;
