<?php
/**
 * @author Niklas Laxström
 * @license MIT
 * @file
 */

$IP = getenv( 'MW_INSTALL_PATH' ) ?: '.';
require_once "$IP/maintenance/Maintenance.php";

class SanatExport extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Exports infra pages to files';
		$this->addArg( 'target', 'Target directory' );
	}

	public function execute() {
		$target = $this->getArg( 0 );

		$db = wfGetDB( DB_SLAVE );
		$res = $db->select(
			array( 'page' ),
			Revision::selectPageFields(),
			array(
				'page_namespace' => array(
					106, // Form
					102, // Property
					NS_TEMPLATE,
					NS_CATEGORY,
				)
			),
			__METHOD__
		);

		foreach ( $res as $row ) {
			$title = Title::newFromRow( $row );
			$revision = Revision::newFromTitle( $title );
			$content = $revision->getContent();
			$text = ContentHandler::getContentText( $content );
			file_put_contents( "$target/{$title->getPrefixedText()}", $text );
		}
	}
}

$maintClass = 'SanatExport';
require_once RUN_MAINTENANCE_IF_MAIN;
