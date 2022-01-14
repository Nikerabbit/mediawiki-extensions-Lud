<?php
declare( strict_types=1 );

namespace MediaWiki\Extensions\Lud;

use Exception;
use RuntimeException;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class LyETabConverter {
	public function parse( $filepath ): array {
		$in = self::getLinesFromCsvFile( $filepath );

		$out = [];
		$prev = [];
		foreach ( $in as $line ) {
			// Skip the header, if present
			if ( $line[0] === 'synonyymit' ) {
				continue;
			}

			if ( $line[1] === '' && $line[2] === $prev[2] ) {
				// fill in missing values ('') from the previous lines.
				// Make sure empty strings are unset first so that they
				// will be replaced.
				foreach ( $line as $i => $v ) {
					if ( $v === '' && $i < 4 ) {
						$line[$i] = $prev[$i];
					}
				}
			}

			try {
				$out[] = $this->parseLine( $line );
				$prev = $line;
			} catch ( Exception $e ) {
				$fmt = json_encode( $line, JSON_UNESCAPED_UNICODE );
				echo $e->getMessage() . "\nRivi: $fmt\n\n";
			}
		}

		return $out;
	}

	public static function getLinesFromCsvFile( string $filepath ): array {
		$in = [];

		// There are some accidental newlines in the data.
		// We can detect them by checking if There is " following
		// | or at the start of the line. In this case we join the
		// next line to the current line.
		$full = '';
		foreach ( file( $filepath ) as $line ) {
			$full .= strtr( $line, [ "'" => '’' ] );

			if ( preg_match( '/(^|\|)"[^|]+$/', $full ) ) {
				continue;
			}

			// Normalize newlines to space after comma, or none otherwise
			$full = preg_replace( '/,\n/', ' ', $full );
			$full = preg_replace( '/\n/', '', $full );

			// Ignore fully empty lines
			if ( trim( $full, '|' ) !== '' ) {
				$in[] = str_getcsv( $full, '|' );
			}

			$full = '';
		}

		return $in;
	}

	public function parseLine( $x ): array {
		if ( !$x[1] ) {
			throw new RuntimeException( '[LyE] Sanaluokka puuttuu' );
		}

		$id = str_replace( '/', '', $x[2] );

		$translations = [];
		if ( $x[4] ) {
			$translations['ru'] = array_map( 'trim', preg_split( '/[,;] /', $x[4] ) );
		}
		if ( $x[5] ) {
			$translations['fi'] = array_map( 'trim', preg_split( '/[,;] /', $x[5] ) );
		}

		$examples = [];
		foreach ( [ 6, 9 ] as $i ) {
			if ( !$x[$i] ) {
				continue;
			}

			$examples[] = [
				'lud-x-south' => $x[$i],
				'ru' => $x[$i + 1],
				'fi' => $x[$i + 2],
			];
		}

		return [
			'id' => $id,
			'base' => $id,
			'type' => 'entry',
			'language' => 'lud',
			'cases' => [ 'lud-x-south' => $x[3] ],
			'properties' => [ 'pos' => $x[1] ],
			'examples' => $examples,
			'translations' => $translations,
			'links' => LyKTabConverter::splitTranslations( $x[0] ),
		];
	}
}
