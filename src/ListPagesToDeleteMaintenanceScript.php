<?php
declare( strict_types=1 );

namespace MediaWiki\Extensions\Lud;

use DirectoryIterator;
use Maintenance;
use MediaWiki\MediaWikiServices;
use Title;
use UtfNormal;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class ListPagesToDeleteMaintenanceScript extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Makes a list of pages to delete' );
		$this->addArg( 'out', 'Dir to list of pages to keep' );
	}

	public function execute(): void {
		$outputDirectory = $this->getArg( 0 );

		$exists = [];
		$toKeep = [];

		$connection = MediaWikiServices::getInstance()->getDBLoadBalancerFactory()->getReplicaDatabase();
		$res = $connection->newSelectQueryBuilder()
			->select( 'page_title' )
			->from( 'page' )
			->where( [ 'page_namespace' => NS_LUD ] )
			->caller( __METHOD__ )
			->fetchResultSet();

		foreach ( $res as $row ) {
			$exists[] = Title::makeTitle( NS_LUD, $row->page_title )->getPrefixedText();
		}

		$iter = new DirectoryIterator( $outputDirectory );
		foreach ( $iter as $entry ) {
			if ( !$entry->isFile() ) {
				continue;
			}

			$filename = $entry->getFilename();

			$titleText = strtr( $filename, '_', '/' );
			$titleText = UtfNormal\Validator::cleanUp( $titleText );

			$title = Title::newFromText( $titleText );
			if ( !$title ) {
				die( "Invalid title from '$filename'" );
			}

			$toKeep[] = $title->getPrefixedText();
		}

		$toDelete = array_diff( $exists, $toKeep );
		echo implode( "\n", $toDelete );
	}
}
