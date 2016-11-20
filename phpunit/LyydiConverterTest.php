<?php

/**
 * @author Niklas Laxström
 * @license MIT
 */
class LyydiConverterTest extends PHPUnit_Framework_TestCase {
	/** @dataProvider testIsHeaderProvider */
	public function testIsHeader( $input, $expected, $comment = null ) {
		$c = new LyydiConverter();
		$output = $c->isHeader( $input );
		$this->assertEquals( $expected, $output, $comment );
	}

	public function testIsHeaderProvider() {
		return array(
			array( 'A', true ),
			array( 'S, Š', true, 'Letter variants' ),
		);
	}
}

require_once __DIR__ . '/../LyydiConverter.php';
