<?php
/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 * @file
 */

$IP = getenv( 'MW_INSTALL_PATH' ) ?: '.';
require_once "$IP/maintenance/Maintenance.php";
require_once __DIR__ . '/LyydiConverter.php';
require_once __DIR__ . '/LyydiFormatter.php';

class FindPages extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Find pages to delete';
	}

	public function execute() {
		$target = $this->getArg( 0 );

		$db = wfGetDB( DB_REPLICA );
		$res = $db->select(
			[ 'page' ],
			Revision::selectPageFields(),
			[
				'page_namespace' => [
					NS_MAIN,
					NS_LUD,
				],
			],
			__METHOD__
		);

		foreach ( $res as $row ) {
			$title = Title::newFromRow( $row );
			if ( $title->getText() === $GLOBALS['wgContLang']->ucFirst( $title->getText() ) ) {
				echo $title->getPrefixedText() . "\n";
			}
		}
	}
}

$maintClass = 'FindPages';
require_once RUN_MAINTENANCE_IF_MAIN;
