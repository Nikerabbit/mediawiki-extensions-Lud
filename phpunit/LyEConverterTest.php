<?php

namespace MediaWiki\Extensions\Lud;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 * @covers \MediaWiki\Extensions\Lud\LyEConverter
 */
class LyEConverterTest extends PHPUnit\Framework\TestCase {
	/** @dataProvider testIsHeaderProvider */
	public function testIsHeader( $input, $expected, $comment = null ) {
		$c = new LyEConverter();
		$output = $c->isHeader( $input );
		$this->assertEquals( $expected, $output, $comment );
	}

	public function testIsHeaderProvider() {
		return [
			[ 'A', true ],
			[ 'S, Š', true, 'Letter variants' ],
		];
	}
}
