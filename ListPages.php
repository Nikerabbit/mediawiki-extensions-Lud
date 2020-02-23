<?php
/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 * @file
 */

$IP = getenv( 'MW_INSTALL_PATH' ) ?: '../..';
require_once "$IP/maintenance/Maintenance.php";
require_once __DIR__ . '/LyydiConverter.php';
require_once __DIR__ . '/LyydiTabConverter.php';
require_once __DIR__ . '/LyydiFormatter.php';

class LudDelete extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Deletes Lyydi word articles';
		$this->addArg( 'out', 'Dir to list pages to keep' );
	}

	public function execute() {
		$outdir = $this->getArg( 0 );

		$exists = [];
		$toKeep = [];

		$tables = 'page';
		$fields = 'page_title';
		$conds = [
			'page_namespace' => NS_LUD,
		];
		$res = wfGetDB( DB_REPLICA )->select( $tables, $fields, $conds );

		foreach ( $res as $row ) {
			$exists[] = Title::makeTitle( NS_LUD, $row->page_title )->getPrefixedText();
		}

		$iter = new DirectoryIterator( $outdir );
		foreach ( $iter as $entry ) {
			if ( !$entry->isFile() ) {
				continue;
			}

			$filename = $entry->getFilename();

			$titletext = strtr( $filename, '_', '/' );
			$titletext = UtfNormal\Validator::cleanUp( $titletext );

			$title = Title::newFromText( $titletext );
			if ( !$title ) {
				die( "Invalid title from '$filename'" );
			}

			$toKeep[] = $title->getPrefixedText();
		}

		$toDelete = array_diff( $exists, $toKeep );
		echo implode( "\n", $toDelete );
	}
}

$maintClass = 'LudDelete';
require_once RUN_MAINTENANCE_IF_MAIN;
