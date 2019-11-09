<?php

/**
 * @author Niklas Laxström
 * @license MIT
 */
class KirjaLyydiConverter {
	public function parse( string $content ) : array {
		// Break into lines, not a perf issue with our file size
		$lines = explode( PHP_EOL, $content );

		// Remove empty lines
		$lines = array_filter( $lines, function ( $x ) { return $x !== ''; } );

		// Remove beginning
		foreach ( $lines as $i => $line ) {
			if ( $line === 'A' ) break;
			unset( $lines[$i] );
		}

		$out = [];
		foreach ( $lines as $i => $line ) {
			if ( $this->isHeader( $line ) ) {
				continue;
			}

			try {
				$out[] = $this->parseLine( $line );
			} catch ( Exception $e ) {
				echo $e->getMessage() . "\nRivi: $line\n\n";
			}
		}

		return $out;
	}

	public function parseLine( $line ) {
		// References to other words
		$links = [];
		if ( preg_match( "~, ср\. (.+)$~u", $line, $match ) ) {
			$links = array_map( 'trim', preg_split( '/[;,]/', $match[1] ) );
			$line = substr( $line, 0, -strlen( $match[0] ) );
		}

		if ( !preg_match( '/[—–]/', $line ) ) {
			throw new Exception( 'Riviltä puuttuu "—"' );
		}

		$regexp = "/^(.+)\s*[—–]\s*([^:]+)(: .+)?$/uU";
		if ( preg_match( $regexp, $line, $match ) ) {
			list( $all, $word, $trans, $examples ) = $match;

			$translations = $this->splitTranslations( $trans );
			$examples = $this->splitExamples( $examples );

			list( $base, $rest ) = explode( ',', $word, 2 );
			$base = str_replace( '/', '', $base );

			return [
				'id' => $base,
				'base' => $base,
				'type' => 'entry',
				'language' => 'lud',
				'cases' => [ 'lud' => $word ],
				'properties' => [], // POS unknown
				'examples' => $examples,
				'translations' => $translations,
				'links' => $links,
			];
		}

		throw new Exception( 'Rivin jäsentäminen epäonnistui' );
	}

	public function isHeader( $line ) {
		return strpos( $line, '.' ) === false && mb_strlen( $line, 'UTF-8' ) <= 4;
	}

	public function splitTranslations( $string ) {
		return [
			'ru' => array_map( 'trim', preg_split( '/[,;]/', $string ) ),
		];
	}

	public function splitExamples( $string ) {
		$string = ltrim( $string, ':' );
		$string = trim( $string );
		$ret = [];
		$re = '~(.+) [‘’]([^‘’]+)[‘’](,|$)~uU';
		while ( preg_match( $re, $string, $match ) ) {

			$ret[] = [
				'lud' => trim( $match[1] ),
				'ru' => trim( $match[2] ),
			];

			$string = substr( $string, strlen( $match[0] ) );
		}

		return $ret;
	}
}
