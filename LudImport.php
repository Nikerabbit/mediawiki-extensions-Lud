<?php
/**
 * @author Niklas Laxström
 * @license MIT
 * @file
 */

$IP = getenv( 'MW_INSTALL_PATH' ) ?: '.';
require_once "$IP/maintenance/Maintenance.php";
require_once __DIR__ . '/LyydiConverter.php';
require_once __DIR__ . '/LyydiFormatter.php';


class LudImport extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Imports Lyydi word articles';
		$this->addArg( 'file', 'File to import' );
	}

	public function execute() {
		$file = $this->getArg( 0 );
		$user = User::newFromName( 'Importer' );

		$in = file_get_contents( $file );
		$c = new LyydiConverter();
		$out = $c->parse( $in );

		$f = new LyydiFormatter();

		$out = $f->getEntries( $out );

		foreach ( $out as $struct ) {
			$title = $f->getTitle( $struct );
			if ( !$title ) {
				$this->error( "Invalid title: {$struct['id']}" );
			}

			$page = WikiPage::factory( $title );

			# Read the text
			$text = $f->formatEntry( $struct );
			$content = ContentHandler::makeContent( $text, $title );

			# Do the edit
			$status = $page->doEditContent( $content, '', EDIT_FORCE_BOT );
			if ( $status->isOK() ) {
				$this->output( ".", 'progress' );
			} else {
				$this->error( "Failed to import {$struct['id']}\n" );
			}
		}
	}
}

$maintClass = 'LudImport';
require_once RUN_MAINTENANCE_IF_MAIN;
