<?php

declare( strict_types=1 );

namespace MediaWiki\Extensions\Lud;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 * @covers \MediaWiki\Extensions\Lud\LyEConverter
 */
final class LyEConverterTest extends TestCase {
	#[DataProvider( 'provideIsHeader' )]
	public function testIsHeader( string $input, bool $expected, string $comment = '' ): void {
		$c = new LyEConverter();
		$output = $c->isHeader( $input );
		$this->assertSame( $expected, $output, $comment );
	}

	public static function provideIsHeader(): \Iterator {
		yield [ 'A', true ];
		yield [ 'S, Š', true, 'Letter variants' ];
	}
}
