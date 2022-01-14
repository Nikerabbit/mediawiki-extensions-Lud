<?php
declare( strict_types=1 );

namespace MediaWiki\Extensions\Lud;

use Exception;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class LyKKTabConverter {
	public function parse( string $filepath ): array {
		$in = [];

		foreach ( file( $filepath ) as $line ) {
			$in[] = str_getcsv( $line, '|' );
		}

		$out = [];
		foreach ( $in as $line ) {
			// Skip the header, if present, and empty lines
			if ( $line[0] === 'suomi' || $line[0] === '' ) {
				continue;
			}

			try {
				$out[] = $this->parseLine( $line );
			} catch ( Exception $e ) {
				$fmt = json_encode( $line, JSON_UNESCAPED_UNICODE );
				echo $e->getMessage() . "\nRivi: $fmt\n\n";
			}
		}

		return $this->mergeDuplicates( $out );
	}

	public function parseLine( array $x ): array {
		// 0 - suomi
		// 1 - lyydi
		// 2 - venäjä

		$id = str_replace( '/', '', trim( $x[1] ) );

		$translations = [];
		$translations['ru'] = LyKTabConverter::splitTranslations( $x[2] );
		$translations['fi'] = LyKTabConverter::splitTranslations( $x[0] );
		$cases = [ 'lud' => trim( $x[1] ) ];

		return [
			'id' => $id,
			'base' => $id,
			'type' => 'entry',
			'language' => 'lud',
			'cases' => $cases,
			'properties' => [],
			'examples' => [],
			'translations' => $translations,
			'links' => [],
		];
	}

	private function mergeDuplicates( array $x ): array {
		$out = [];

		foreach ( $x as $entry ) {
			$uniqueKey = $entry['id'];

			if ( !isset( $out[$uniqueKey] ) ) {
				$out[$uniqueKey] = $entry;
				continue;
			}

			// Merge translations
			$out[$uniqueKey]['translations'] =
				array_merge_recursive( $out[$uniqueKey]['translations'], $entry['translations'] );
		}

		return $out;
	}
}
