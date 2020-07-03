<?php
/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */

use MediaWiki\MediaWikiServices;
//use MediaWiki\Revision\SlotRecord;
use MediaWiki\Storage\SlotRecord; // BC

class LudHooks {
	public static function onBeforePageDisplay( OutputPage $out ) {
		$out->addModuleStyles( 'ext.lud.styles' );
	}

	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setFunctionHook( 'lud', [ self::class, 'renderLud' ] );
	}

	public static function renderLud( $parser, $text = '' ) {
		$abbs = self::getAbbreviations();
		$patterns = [];
		foreach ( $abbs as $abb => $title ) {
			$patterns[] = preg_quote( $abb, '/' );
		}
		$pattern = '/(?<= |\(|^)(' . implode( '|', $patterns ) . ')(?= |\)|,|$)/um';

		$cb = function ( $m ) use ( $abbs ) {
			return Html::element( 'abbr', [ 'title' => $abbs[$m[1]] ], $m[1] );
		};

		$text = preg_replace_callback( $pattern, $cb, $text );

		return $text;
	}

	public static function getAbbreviations() {
		$cache = wfGetCache( CACHE_ANYTHING );
		$key = 'lud-abbs';
		$data = $cache->get( $key );
		if ( is_array( $data ) ) {
			return $data;
		}

		$data = [];
		$source = Title::newFromText( 'Lüüdi/Liitteet_ja_lyhenteet' );
		$store = MediaWikiServices::getInstance()->getRevisionStore();
		$revision = $store->getRevisionByTitle( $source );
		if ( $revision !== null ) {
			$contents = ContentHandler::getContentText( $revision->getContent(
				/*SlotRecord::MAIN*/'main' ) );

			if ( $contents ) {
				preg_match_all( '/^(.+) = (.+):?$/m', $contents, $matches );
				$data = array_combine( $matches[1], $matches[2] );
			}
		}

		$cache->set( $key, $data, 3600 );
		return $data;
	}
}
