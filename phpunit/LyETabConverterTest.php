<?php

namespace MediaWiki\Extensions\Lud;

use Override;
use PHPUnit\Framework\TestCase;

/** @covers \MediaWiki\Extensions\Lud\LyETabConverter */
class LyETabConverterTest extends TestCase {
	private string $filename;

	#[Override]
	protected function setUp(): void {
		parent::setUp();
		$this->filename = tempnam( sys_get_temp_dir(), 'lud-test-' );
	}

	#[Override]
	protected function tearDown(): void {
		unlink( $this->filename );
		parent::tearDown();
	}

	public function testMissingPartOfSpeechOnFirstRow(): void {
		file_put_contents( $this->filename, '||word|cases||||||||' );
		$this->expectOutputRegex( '/Sanaluokka puuttuu/' );
		$this->assertSame( [], ( new LyETabConverter() )->parse( $this->filename ) );
	}

	public function testContinuationRowInheritsPreviousFields(): void {
		file_put_contents( $this->filename, "|s.|word|cases||||||||\n||word|||||||||" );
		$entries = ( new LyETabConverter() )->parse( $this->filename );
		$this->assertCount( 2, $entries );
		$this->assertSame( 's.', $entries[1]['properties']['pos'] );
		$this->assertSame( 'cases', $entries[1]['cases']['lud-x-south'] );
	}
}
