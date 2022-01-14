<?php
declare( strict_types=1 );

namespace MediaWiki\Extensions\Lud;

use ContentHandler;
use Html;
use MediaWiki\MediaWikiServices;
use MediaWiki\Storage\SlotRecord;
use ObjectCache;
use OutputPage;
use Parser;
use Title;
use const PREG_SET_ORDER;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class Hooks {
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
		$pattern = '/(?<=[ (-]|^)(' . implode( '|', $patterns ) . ')(?=[ ),-]|$)/um';

		$cb = function ( $m ) use ( $abbs ) {
			return Html::element( 'abbr', [ 'title' => $abbs[$m[1]] ], $m[1] );
		};

		return preg_replace_callback( $pattern, $cb, $text );
	}

	public static function getAbbreviations() {
		$cache = ObjectCache::getInstance( CACHE_ANYTHING );
		$key = 'lud-abbs-v2';
		$data = $cache->get( $key );
		if ( is_array( $data ) ) {
			return $data;
		}

		$data = [];
		$source = Title::newFromText( 'Lüüdi/Liitteet_ja_lyhenteet' );
		$store = MediaWikiServices::getInstance()->getRevisionStore();
		$revision = $store->getRevisionByTitle( $source );
		if ( $revision !== null ) {
			$contents = ContentHandler::getContentText(
				$revision->getContent(SlotRecord::MAIN )
			);

			if ( $contents ) {
				preg_match_all( '/^(.+) = (.+):?$/m', $contents, $matches, PREG_SET_ORDER );
				foreach ( $matches as $m ) {
					if ( isset( $data[$m[1]] ) ) {
						$data[$m[1]] .= "\n" . $m[2];
					} else {
						$data[$m[1]] = $m[2];
					}
				}
			}
		}

		$cache->set( $key, $data, 3600 );
		return $data;
	}
}
