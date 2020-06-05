<?php

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class KeskiLyydiTabConverter {
	public function parse( $filepath ) {
		$in = [];

		foreach ( file( $filepath ) as $line ) {
			$in[] = str_getcsv( $line, '|' );
		}

		$out = [];
		foreach ( $in as $line ) {
			// Skip the header, if present
			if ( $line[0] === 'hakusana' ) {
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
		// 0 - hakusana
		// 1 - keskilyydi Ph
		// 2 - määrittely
		// 3 - keskilyydi Ph, RKS
		// 4 - venäjännös
		// 5 - suomennos
		// 6 - esimerkki
		// 7 - sama esimerkki (RKS)
		// 8 - esim. venäjännös
		// 9 - esim. suomennos

		if ( !$x[0] ) {
			throw new RuntimeException( 'Sanaluokka puuttuu (LyK)' );
		}

		$id = $x[0];

		$translations = [];
		if ( $x[4] !== '' ) {
			$translations['ru'] = self::splitTranslations( $x[4] );
		}
		if ( $x[5] !== '' ) {
			$translations['fi'] = self::splitTranslations( $x[5] );
		}

		$examples = [];
		if ( $x[6] !== '' ) {
			$examples[] = [
				'lud-x-middle' => $x[6],
				'ru' => $x[8],
				'fi' => $x[9],
			];
		}

		$cases = [];
		if ( $x[1] ) {
			$cases = [ 'lud-x-middle' => $x[1] ];
		}

		return [
			'id' => $id,
			'base' => $id,
			'type' => 'entry',
			'language' => 'lud-x-middle',
			'cases' => $cases,
			'properties' => [ 'pos' => $x[2] ],
			'examples' => $examples,
			'translations' => $translations,
			'links' => [],
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
