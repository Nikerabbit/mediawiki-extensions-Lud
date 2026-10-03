<?php

namespace MediaWiki\Extensions\Lud;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 * @covers \MediaWiki\Extensions\Lud\LyEConverter
 */
class LyEConverterTest extends TestCase {
	#[DataProvider( 'provideIsHeader' )]
	public function testIsHeader( $input, $expected, $comment = '' ) {
		$c = new LyEConverter();
		$output = $c->isHeader( $input );
		$this->assertEquals( $expected, $output, $comment );
	}

	public static function provideIsHeader() {
		return [
			[ 'A', true ],
			[ 'S, Š', true, 'Letter variants' ],
		];
	}
}
