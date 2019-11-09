<?php

/**
 * @author Niklas Laxström
 * @license MIT
 */
class LyydiTabConverter {
	public function parse( $filepath ) {
		$in = array_map( function ( $line ) {
			return str_getcsv( $line, "|" );
		}, file( $filepath ) );

		$out = [];
		$prev = [];
		foreach ( $in as $line ) {
			if ( $line[ 1 ] === 'Hakusana' ) {
				continue;
			}

			if ( $line[ 0 ] === '' && $line[ 1 ] === $prev[ 1 ] ) {
				// fill in missing values ('') from the previous lines.
				// Make sure empty strings are unset first so that they
				// will be replaced.
				foreach ( $line as $i => $v ) {
					if ( $v === '' && $i < 3 ) {
						$line[ $i ] = $prev[ $i ];
					}
				}
			}

			try {
				$out[] = $this->parseLine( $line );
				$prev = $line;
			} catch ( Exception $e ) {
				$fmt =  json_encode( $line, JSON_UNESCAPED_UNICODE );
				echo $e->getMessage() . "\nRivi: $fmt\n\n";
			}
		}

		return $out;
	}

	public function parseLine( $x ) {
		if ( !$x[ 0 ] ) {
			throw new RuntimeException( 'Sanaluokka puuttuu' );
		}

		$id = str_replace( '/', '', $x[ 1 ] );

		$translations = [];
		if ( $x[ 3 ] ) {
			$translations['ru'] = array_map( 'trim', preg_split( '/[,;] /', $x[ 3 ] ) );
		}
		if ( $x[ 4 ] ) {
			$translations['fi'] = array_map( 'trim', preg_split( '/[,;] /', $x[ 4 ] ) );
		}

		$examples = [];
		foreach ( [ 5, 8 ] as $i ) {
			if ( !$x[ $i ] ) continue;

			$examples[] = [
				'lud-x-south' => $x[ $i ],
				'ru' => $x[ $i + 1 ],
				'fi' => $x[ $i + 2 ],
			];
		}

		return [
			'id' => $id,
			'base' => $id,
			'type' => 'entry',
			'language' => 'lud',
			'cases' => [ 'lud-x-south' => $x[ 2 ] ],
			'properties' => [ 'pos' => $x[ 0 ] ],
			'examples' => $examples,
			'translations' => $translations,
			'links' => [],
		];
	}
}
