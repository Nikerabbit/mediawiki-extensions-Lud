<?php
declare( strict_types = 1 );

namespace MediaWiki\Extensions\Lud;

use Exception;
use RuntimeException;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class LyPTabConverter {
	public function parse( string $filepath ): array {
		$in = LyETabConverter::getLinesFromCsvFile( $filepath );
		$out = [];
		foreach ( $in as $line ) {
			// Skip the header, if present
			if ( $line[0] === 'Synonyymit' ) {
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

	public function parseLine( array $x ): array {
		// Synonyymit|Määrittely|kirjalyydi|Murremuoto|Venäjännös|Suomennos|
		// 1. esim.|1. esim:n venäjännös|1. esim:n suomennos|
		// 2. esim.|2. esim:n venäjännös|2. esim:n suomennos
		[ $aliases, $pos, $lit, $lud, $ru, $fi, $ex1lud, $ex1ru, $ex1fi, $ex2lud, $ex2ru, $ex2fi ] =
			$x;

		if ( !$pos ) {
			throw new RuntimeException( '[LyP] Sanaluokka puuttuu' );
		}

		if ( preg_match( '/[~,]/', $lit ) === 1 ) {
			throw new RuntimeException( '[LyP] Odottamattomia merkkejä kentässä kirjalyydi' );
		}

		$links = LyKTabConverter::splitTranslations( $aliases );

		$translations = [];
		$translations['ru'] = LyKTabConverter::splitTranslations( $ru );
		$translations['fi'] = LyKTabConverter::splitTranslations( $fi );
		$cases = [ 'lud-x-north' => trim( $lud ) ];

		$examples = [];
		if ( $ex1lud !== '' ) {
			$examples[] = [
				'lud-x-north' => $ex1lud,
				'ru' => $ex1ru,
				'fi' => $ex1fi,
			];
		}

		if ( $ex2lud !== '' ) {
			$examples[] = [
				'lud-x-north' => $ex2lud,
				'ru' => $ex2ru,
				'fi' => $ex2fi,
			];
		}

		$lit = trim( $lit );

		return [
			'id' => $lit,
			'base' => $lit,
			'type' => 'entry',
			'language' => 'lud-x-north',
			'cases' => $cases,
			'properties' => [ 'pos' => trim( $pos ) ],
			'examples' => $examples,
			'translations' => $translations,
			'links' => $links,
		];
	}

	private function mergeDuplicates( array $x ): array {
		$out = [];

		foreach ( $x as $entry ) {
			$uniqueKey = "{$entry['id']} ({$entry['properties']['pos']})";

			if ( !isset( $out[$uniqueKey] ) ) {
				$out[$uniqueKey] = $entry;
				continue;
			}

			$base = $out[$uniqueKey];

			$entryCases = $entry['cases']['lud-x-north'];
			$baseCases = $base['cases']['lud-x-north'];

			if ( $entryCases !== '' && $entryCases !== $baseCases ) {
				echo "[LyP] Duplikaatti: $uniqueKey – '$entryCases' vai '$baseCases'\n";
				continue;
			}

			// Merge additional examples and translations
			$out[$uniqueKey]['examples'] =
				array_merge_recursive( $base['examples'], $entry['examples'] );
			$out[$uniqueKey]['translations'] =
				array_merge_recursive( $base['translations'], $entry['translations'] );
		}

		return $out;
	}
}
