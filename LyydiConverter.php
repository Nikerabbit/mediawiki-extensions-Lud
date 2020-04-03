<?php

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class LyydiConverter {
	protected $pos = [
		'a.',
		'a. mod.',
		'a. yhd.',
		'adv.',
		'arv.',
		'dem.',
		'frekv.',
		'harv.',
		'hum.',
		'imperat.',
		'indef.',
		'interj.',
		'iron.',
		'itkuv.',
		'kansanr.',
		'kar.',
		'kieltov.',
		'kirj.',
		'komp.',
		'konj.',
		'konj. (part.)',
		'ks.',
		'kuv.',
		'laul.',
		'liitepart.',
		'loits.',
		'mom.',
		'mon.',
		'num.',
		'part.',
		'pers.',
		'pn.',
		'postp.',
		'prep.',
		'prep. tai post.',
		'pron.',
		'refl.',
		's.',
		'sl.',
		'sp.',
		'sup.',
		'taipum.',
		'uskom.',
		'uud.',
		'v.',
		'yhd.',
		'yks.',
		'yksipers.',
		' ',
	];

	public function parse( $content ) {
		// Break into lines, not a perf issue with our file size
		$lines = explode( PHP_EOL, $content );

		// Remove empty lines
		$lines = array_filter(
			$lines,
			function ( $x ) {
				return $x !== '';
			}
		);

		// Remove beginning
		foreach ( $lines as $i => $line ) {
			if ( $line === 'A' ) {
				break;
			}
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

	public function isHeader( $line ) {
		return strpos( $line, '.' ) === false && mb_strlen( $line, 'UTF-8' ) <= 4;
	}

	public function parseLine( $line ) {
		// Redirects
		if ( preg_match( '/^(.+) ks\. (.+)$/', $line, $match ) ) {
			return [
				'id' => $match[1],
				'type' => 'redirect',
				'target' => $match[2],
			];
		}

		// References to other words
		$links = [];
		if ( preg_match( "~[.:] Vrt\.\s+(.+)$~u", $line, $match ) ) {
			$links = array_map( 'trim', preg_split( '/[;,]/', $match[1] ) );
			$line = substr( $line, 0, - strlen( $match[0] ) );
		}

		// Normal entries
		$wcs = implode( '|', array_map( 'preg_quote', $this->pos ) );
		$regexp = "/^([^. ]+(?: [I]+)?)\s+(($wcs)+)\s+([^.]+)\s*[—–]\s*([^:]+)(: .+)?$/uU";

		if ( !preg_match( '/[—–]/', $line ) ) {
			throw new Exception( 'Riviltä puuttuu "—" (LyE):' );
		}

		if ( preg_match( $regexp, $line, $match ) ) {
			[ $all, $word, $wc, $unused, $inf, $trans ] = $match;

			$props = [];
			$props['pos'] = $wc;

			$translations = $this->splitTranslations( $trans );

			$examples = [];
			if ( isset( $match[6] ) ) {
				$examples = substr( $match[6], 2 );
				$examples = $this->splitExamples( $examples );
			}

			return [
				'id' => $word,
				'base' => $word,
				'type' => 'entry',
				'language' => 'lud',
				'cases' => [ 'lud-x-south' => $inf ],
				'properties' => $props,
				'examples' => $examples,
				'translations' => $translations,
				'links' => $links,
			];
		}

		throw new Exception( 'Rivin jäsentäminen epäonnistui (LyE):' );
	}

	public function splitTranslations( $string ) {
		if ( strpos( $string, ' / ' ) === false ) {
			throw new Exception( "Käännöksissä häikkää: *$string*" );
		}

		$languages = array_filter( array_map( 'trim', explode( ' / ', $string ) ) );
		if ( count( $languages ) !== 2 ) {
			throw new Exception( "Käännöksissä häikkää: *$string*" );
		}

		return [
			'fi' => KeskiLyydiTabConverter::splitTranslations( $languages[0] ),
			'ru' => KeskiLyydiTabConverter::splitTranslations( $languages[1] ),
		];
	}

	public function splitExamples( $string ) {
		$string = trim( $string );
		$ret = [];
		$re = '~^([^/]+) [‘’]([^/]+)’ / [‘’]([^/]+)’(?:\. )??~uU';
		while ( preg_match( $re, $string, $match ) ) {
			$ret[] = [
				'lud-x-south' => $match[1],
				'ru' => $match[3],
				'fi' => $match[2],
			];

			$string = substr( $string, strlen( $match[0] ) );
		}

		return $ret;
	}
}
