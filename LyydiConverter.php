<?php

/**
 * @author Niklas Laxström
 * @license MIT
 */
class LyydiConverter {
	protected $pos = array(
		'a.',
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
	);

	public function parse( $content ) {
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
		// Redirects
		if ( preg_match( '/^(.+) ks\. (.+)$/', $line, $match ) ) {
			return array(
				'id' => $match[1],
				'type' => 'redirect',
				'target' => $match[2],
			);
		}

		// References to other words
		$links = [];
		if ( preg_match( "~[.:] Vrt\. (.+)$~u", $line, $match ) ) {
			$links = array_map( 'trim', preg_split( '/[;,]/', $match[1] ) );
			$line = substr( $line, 0, -strlen( $match[0] ) );
		}

		// Normal entries
		$wcs = implode( '|', array_map( 'preg_quote', $this->pos ) );
		$regexp = "/^([^. ]+)\s+(($wcs)+)\s+([^.]+)\s*[—–]\s*([^:]+)(: .+)?$/uU";

		if ( !preg_match( '/[—–]/', $line ) ) {
			throw new Exception( 'Riviltä puuttuu "—"' );
		}

		if ( preg_match( $regexp, $line, $match ) ) {
			list( $all, $word, $wc, $unused, $inf, $trans ) = $match;

			$props = [];
			$props['pos'] = $wc;

			$translations = $this->splitTranslations( $trans );

			$examples = [];
			if ( isset( $match[6] ) ) {
				$examples = substr( $match[6], 2 );
				$examples = $this->splitExamples( $examples );
			}

			return array(
				'id' => $word,
				'base' => $word,
				'type' => 'entry',
				'language' => 'lud',
				'cases' => array( 'lud-x-south' => $inf ),
				'properties' => $props,
				'examples' => $examples,
				'translations' => $translations,
				'links' => $links,
			);
		}

		throw new Exception( 'Rivin jäsentäminen epäonnistui' );
	}

	public function isHeader( $line ) {
		return strpos( $line, '.' ) === false && mb_strlen( $line, 'UTF-8' ) <= 4;
	}

	public function splitTranslations( $string ) {
		if ( strpos( $string, ' / ' ) === false ) {
			throw new Exception( "Käännöksissä häikkää: *$string*" );
		}

		$languages = array_filter( array_map( 'trim', explode( ' / ', $string ) ) );
		if ( count( $languages ) !== 2 ) {
			throw new Exception( "Käännöksissä häikkää: *$string*" );
		}

		return array(
			'fi' => array_map( 'trim', preg_split( '/[,;] /', $languages[0] ) ),
			'ru' => array_map( 'trim', preg_split( '/[,;] /', $languages[1] ) ),
		);
	}

	public function splitExamples( $string ) {
		$string = trim( $string );
		$ret = [];
		$re = '~^([^/]+) [‘’]([^/]+)’ / [‘’]([^/]+)’(?:\. )??~uU';
		while ( preg_match( $re, $string, $match ) ) {
			$literature = false;
			if ( preg_match( '~kirj\.$~', $match[1] ) ) {
				$literature = true;
				$match[1] = preg_replace( '~kirj\.$~', '', $match[1] );
			}

			$ret[] = array(
				'literature' => $literature,
				'lud-x-south' => $match[1],
				'fi' => $match[2],
				'ru' => $match[3],
			);

			$string = substr( $string, strlen( $match[0] ) );
		}

		return $ret;
	}
}
