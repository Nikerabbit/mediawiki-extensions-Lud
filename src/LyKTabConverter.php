<?php
declare( strict_types=1 );

namespace MediaWiki\Extensions\Lud;

use Exception;
use RuntimeException;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class LyKTabConverter {
	public function parse( $filepath ) {
		$in = LyETabConverter::getLinesFromCsvFile( $filepath );
		$out = [];
		foreach ( $in as $line ) {
			// Skip the header, if present
			if ( $line[1] === 'hakusana' ) {
				continue;
			}

			try {
				$out[] = $this->parseLine( $line );
			} catch ( Exception $e ) {
				$fmt = json_encode( $line, JSON_UNESCAPED_UNICODE );
				echo $e->getMessage() . "\nRivi: $fmt\n\n";
			}
		}

		$out = $this->mergeDuplicates( $out );

		return $out;
	}

	public function parseLine( $x ) {
		// 0 - synonyymit
		// 1 - hakusana
		// 2 - keskilyydi Ph
		// 3 - määrittely
		// 4 - keskilyydi Ph, RKS
		// 5 - venäjännös
		// 6 - suomennos
		// 7 - esimerkki
		// 8 - sama esimerkki (RKS)
		// 9 - esim. venäjännös
		// 10 - esim. suomennos

		if ( !$x[1] ) {
			throw new RuntimeException( '[LyK] Hakusana puuttuu' );
		}

		if ( !$x[3] ) {
			throw new RuntimeException( '[LyK] Sanaluokka puuttuu' );
		}

		$id = $x[1];

		$translations = [];
		if ( $x[5] !== '' ) {
			$translations['ru'] = self::splitTranslations( $x[5] );
		}
		if ( $x[6] !== '' ) {
			$translations['fi'] = self::splitTranslations( $x[6] );
		}

		$examples = [];
		if ( $x[7] !== '' ) {
			$examples[] = [
				'lud-x-middle' => $x[7],
				'ru' => $x[9],
				'fi' => $x[10],
			];
		}

		$cases = [];
		if ( $x[2] ) {
			$cases = [ 'lud-x-middle' => $x[2] ];
		}

		return [
			'id' => $id,
			'base' => $id,
			'type' => 'entry',
			'language' => 'lud-x-middle',
			'cases' => $cases,
			'properties' => [ 'pos' => trim( $x[3] ) ],
			'examples' => $examples,
			'translations' => $translations,
			'links' => self::splitTranslations( $x[0] ),
		];
	}

	public static function splitTranslations( string $x ): array {
		$ph = [];

		// Replace parenthetical expressions with placeholders to avoid splitting inside them
		$regexp = '/\([^)]+\)/';

		$match = [];
		while ( preg_match( $regexp, $x, $match ) ) {
			$key = '!#¤%' . count( $ph );
			$x = str_replace( $match[0], $key, $x );
			$ph[$key] = $match[0];
		}

		$translations = array_map( 'trim', preg_split( '/[,;]\s+/u', $x ) );
		foreach ( $translations as $i => $t ) {
			$translations[$i] = str_replace( array_keys( $ph ), array_values( $ph ), $t );
		}

		$translations = array_filter( $translations );

		return $translations;
	}

	private function mergeDuplicates( array $x ): array {
		$out = [];

		foreach ( $x as $entry ) {
			$uniqueKey = $entry['id'] . '|' . $entry['properties']['pos'];

			if ( !isset( $out[$uniqueKey] ) ) {
				$out[$uniqueKey] = $entry;
				continue;
			}

			# echo "Yhdistetään esimerkit sanalle $uniqueKey (LyK)\n";
			$out[$uniqueKey]['examples'] =
				array_merge( $out[$uniqueKey]['examples'], $entry['examples'] );
		}

		return $out;
	}
}
