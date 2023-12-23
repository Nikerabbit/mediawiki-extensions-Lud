<?php
declare( strict_types=1 );

namespace MediaWiki\Extensions\Lud;

use Html;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use ObjectCache;
use Override;
use Parser;
use TextContent;
use Title;
use const PREG_SET_ORDER;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class Hooks implements BeforePageDisplayHook, ParserFirstCallInitHook {
	#[Override]
	public function onBeforePageDisplay( $out, $skin ): void {
		$out->addModuleStyles( 'ext.lud.styles' );
	}

	#[Override]
	public function onParserFirstCallInit( $parser ): void {
		$parser->setFunctionHook( 'lud', self::renderLud( ... ) );
	}

	public static function renderLud( Parser $parser, $text = '' ): string {
		$abbs = self::getAbbreviations();
		$patterns = [];
		foreach ( $abbs as $abb => $title ) {
			$patterns[] = preg_quote( $abb, '/' );
		}
		$pattern = '/(?<=[ (-]|^)(' . implode( '|', $patterns ) . ')(?=[ ),-]|$)/um';

		$cb = static function ( $m ) use ( $abbs ) {
			return Html::element( 'abbr', [ 'title' => $abbs[$m[1]] ], $m[1] );
		};

		return preg_replace_callback( $pattern, $cb, $text );
	}

	public static function getAbbreviations(): array {
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
			$content = $revision->getContent( SlotRecord::MAIN );
			if ( $content instanceof TextContent ) {
				preg_match_all( '/^(.+) = (.+):?$/m', $content->getText(), $matches, PREG_SET_ORDER );
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
