<?php

/**
 * @author Niklas Laxström
 * @license MIT
 */
class LyydiConverter {
	protected $pos = array(
		's. yhd.',
		's. dem.',
		's. mon.',
		's.',
		'v.',
		'a.',
		'adv.',
		'num.',
		'pron. yks.',
		'pron. mon.',
		'pron.',
		'interj.',
		'part.',
		'prep. tai postp.',
		'konj.',
		'postp.',
		'prep.',
		'liitepart.',
		'kieltov. imperat.',
		'kieltov.',
		'?.',
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

		$out = array();
		foreach ( $lines as $i => $line ) {
			if ( $this->isHeader( $line ) ) {
				continue;
			}

			try {
				$out[] = $this->parseLine( $line );
			} catch ( Exception $e ) {
				echo $e->getMessage() . "\n";
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
		$links = array();
		if ( preg_match( "~[.:] Vrt\. (.+)$~u", $line, $match ) ) {
			$links = array_map( 'trim', preg_split( '/[;,]/', $match[1] ) );
			$line = substr( $line, 0, -strlen( $match[0] ) );
		}

		// Normal entries
		$wcs = implode( '|', array_map( 'preg_quote', $this->pos ) );
		$regexp = "/^(.+) ($wcs) (.+) [—–] ([^:]+)(: .+)?$/uU";

		if ( preg_match( $regexp, $line, $match ) ) {
			list( $all, $word, $wc, $inf, $trans ) = $match;

			$props = array();
			$props['pos'] = $wc;

			$translations = $this->splitTranslations( $trans );

			$examples = array();
			if ( isset( $match[5] ) ) {
				$examples = substr( $match[5], 2 );
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

		throw new Exception( "Unable to parse line: $line" );
	}

	public function isHeader( $line ) {
		return strpos( $line, '.' ) === false && mb_strlen( $line, 'UTF-8' ) <= 4;
	}

	public function splitTranslations( $string ) {
		if ( strpos( $string, '/' ) === false ) {
			throw new Exception( "Unable to find / to split translations: $string" );
		}

		$languages = array_filter( array_map( 'trim', explode( ' / ', $string ) ) );
		if ( count( $languages ) !== 2 ) {
			throw new Exception( "Expecting only two languages: $string" );
		}

		return array(
			'fi' => array_map( 'trim', preg_split( '/[,;] /', $languages[0] ) ),
			'ru' => array_map( 'trim', preg_split( '/[,;] /', $languages[1] ) ),
		);
	}

	public function splitExamples( $string ) {
		$string = trim( $string );
		$ret = array();
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
