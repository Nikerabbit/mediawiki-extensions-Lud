<?php

use MediaWiki\Extensions\Lud\ImportMaintenanceScript;

$env = getenv( 'MW_INSTALL_PATH' );
$IP = $env !== false ? $env : __DIR__ . '/../..';
require_once "$IP/maintenance/Maintenance.php";
require_once __DIR__ . '/../src/ImportMaintenanceScript.php';
$maintClass = ImportMaintenanceScript::class;
require_once RUN_MAINTENANCE_IF_MAIN;
