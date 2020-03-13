<?php

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 * @covers \LyydiConverter
 */
class LyydiConverterTest extends PHPUnit\Framework\TestCase {
	/** @dataProvider testIsHeaderProvider */
	public function testIsHeader( $input, $expected, $comment = null ) {
		$c = new LyydiConverter();
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

require_once __DIR__ . '/../LyydiConverter.php';
