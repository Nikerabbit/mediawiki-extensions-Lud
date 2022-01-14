<?php
declare( strict_types=1 );

namespace MediaWiki\Extensions\Lud;

use Exception;
use Maintenance;
use RuntimeException;
use TypeError;

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class ImportMaintenanceScript extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Imports Lyydi word articles';
		$this->addOption( 'LyE-txt', 'TXT file for LyE' );
		$this->addOption( 'LyE-csv', 'CSV file for LyE' );
		$this->addOption( 'LyK', 'CSV file for LyK' );
		$this->addOption( 'LyP', 'CSV file for LyP' );
		$this->addOption( 'LyKK-txt', 'TXT file for LyKK' );
		$this->addOption( 'LyKK-csv', 'CSV file for LyKK' );
		$this->addOption( 'format', 'Output format', false, true );
		$this->addArg( 'out', 'Dir to place output files' );
	}

	public function execute() {
		ini_set( 'display_errors', '1' );
		error_reporting( E_ALL );
		$outdir = $this->getArg( 0 );

		// Master file lud-x-south
		$textfile = $this->getOption( 'LyE-txt' );
		$textin = file_get_contents( $textfile );
		$textc = new LyEConverter();
		$textout = $textc->parse( $textin );

		// Additional entries for lud-x-south in tabular format
		$tabfile = $this->getOption( 'LyE-csv' );
		$tabc = new LyETabConverter();
		$tabout = $tabc->parse( $tabfile );
		$out = $this->merge( array_merge( $textout, $tabout ) );

		// Merge in lud-x-middle
		$LyKfile = $this->getOption( 'LyK' );
		$LyKc = new LyKTabConverter();
		$LyKout = $LyKc->parse( $LyKfile );
		$out = $this->mergeSimpleVariant( $out, $LyKout );

		// Merge in lud-x-north
		$LyPfile = $this->getOption( 'LyP' );
		$LyPc = new LyPTabConverter();
		$LyPout = $LyPc->parse( $LyPfile );
		$out = $this->mergeSimpleVariant( $out, $LyPout );

		// Parse in lud (txt)
		$kirjafile = $this->getOption( 'LyKK-txt' );
		$kirjain = file_get_contents( $kirjafile );
		$kirjac = new LyKKConverter();
		$kirjaout = $kirjac->parse( $kirjain );

		// Parse in lud (csv)
		$LyKK_SUfile = $this->getOption( 'LyKK-csv' );
		$LyKK_SUc = new LyKKTabConverter();
		$LyKK_SUout = $LyKK_SUc->parse( $LyKK_SUfile );
		$kirjaout = $this->mergeKirjaLyydiTranslations( $kirjaout, $LyKK_SUout );

		// Merge both
		$out = $this->mergeKirjaLyydi( $out, $kirjaout );

		$f = new Formatter();
		$out = $f->getEntries( $out );

		$format = $this->getOption( 'format', 'wikitext' );
		switch ( $format ) {
			case 'json':
				$this->outputJson( $out, $outdir );
				break;
			case 'wikitext':
			default:
				$this->outputWikitext( $out, $f, $outdir );
		}
	}

	protected function merge( array $entries ): array {
		$dedup = [];
		foreach ( $entries as $b ) {
			if ( $b['type'] !== 'entry' ) {
				continue;
			}

			$id = $b['id'] . ' ' . $b['properties']['pos'];

			if ( !isset( $dedup[$id] ) ) {
				$dedup[$id] = $b;
				continue;
			}

			try {
				$x = $this->mergeItems( $dedup[$id], $b );
				$dedup[$id] = $x;
			} catch ( Exception $err ) {
				echo "[LyE] Sanatietueiden yhdistäminen eri lähteistä epäonnistui sanalle $id. " .
					"Jälkimmäinen jää pois.\n" . $err->getMessage() . "\n\n";
			}
		}

		return $dedup;
	}

	protected function mergeItems( $a, $b ): array {
		$cases = $a['cases'];

		if ( $a['cases']['lud-x-south'] !== $b['cases']['lud-x-south'] ) {
			$ac = str_replace( '/', '', $a['cases']['lud-x-south'] );
			$bc = str_replace( '/', '', $b['cases']['lud-x-south'] );
			if ( strlen( $ac ) > strlen( $bc ) ) {
				[ $ac, $bc ] = [ $bc, $ac ];
			} else {
				$cases = $b['cases'];
			}

			if ( $ac !== '' && strpos( $bc, $ac ) === false ) {
				throw new RuntimeException(
					json_encode(
						[
							$a['cases']['lud-x-south'],
							$b['cases']['lud-x-south'],
						],
						JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
					)
				);
			}
		}

		if ( $a['properties']['pos'] !== $b['properties']['pos'] ) {
			throw new RuntimeException(
				json_encode( [ 'properties', $a, $b ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE )
			);
		}

		if ( $a['base'] !== $b['base'] ) {
			throw new RuntimeException(
				json_encode( [ 'base', $a, $b ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE )
			);
		}

		if ( $a['type'] !== $b['type'] ) {
			throw new RuntimeException(
				json_encode( [ 'type', $a, $b ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE )
			);
		}

		return [
			'id' => $a['id'],
			'base' => $a['base'],
			'type' => $a['type'],
			'language' => $a['language'],
			'cases' => $cases,
			'properties' => $a['properties'],
			'examples' => array_merge( $a['examples'], $b['examples'] ),
			'translations' => array_merge_recursive( $a['translations'], $b['translations'] ),
			'links' => array_unique( array_merge( $a['links'], $b['links'] ) ),
		];
	}

	private function mergeSimpleVariant( array $all, array $new ): array {
		foreach ( $new as $i => $m ) {
			foreach ( $all as $j => $a ) {
				if ( !$this->matchEntry( $m, $a ) ) {
					continue;
				}

				$all[$j] = $this->mergeSimpleVariantItem( $a, $m );
				unset( $new[$i] );
			}
		}

		foreach ( $new as $m ) {
			$all[] = $m;
		}

		return $all;
	}

	private function matchEntry( array $a, array $b ): bool {
		return $a['id'] === $b['id'] && $a['properties']['pos'] === $b['properties']['pos'];
	}

	private function mergeSimpleVariantItem( array $a, array $b ): array {
		$a['cases'] = array_merge( $a['cases'], $b['cases'] );
		$a['examples'] = array_merge( $a['examples'], $b['examples'] );
		$a['translations'] = array_merge_recursive( $a['translations'], $b['translations'] );
		$a['links'] = array_unique( array_merge( $a['links'], $b['links'] ) );
		return $a;
	}

	private function mergeKirjaLyydiTranslations( array $a, array $b ): array {
		foreach ( $b as $i => $entry ) {
			foreach ( $a as $j => $cand ) {
				if ( $entry['id'] === $cand['id'] ) {
					$a[$j]['translations'] =
						array_merge_recursive( $a[$j]['translations'], $entry['translations'] );
					unset( $b[$i] );
				}
			}
		}

		foreach ( $b as $entry ) {
			$a[] = $entry;
		}

		return $a;
	}

	private function mergeKirjaLyydi( array $south, array $kirja ): array {
		$new = [];

		foreach ( $kirja as $entry ) {
			$id = $entry['id'];

			$cands = [];
			foreach ( $south as $i => $a ) {
				if ( $a['id'] === $id ) {
					$cands[] = $i;
				}
			}

			$c = count( $cands );
			if ( $c !== 1 ) {
				if ( $c > 1 ) {
					// See if we can find single match by also checking cases
					$newcands = [];
					foreach ( $cands as $i ) {
						if ( ( $south[$i]['cases']['lud-x-south'] ?? '#' ) ===
							$entry['cases']['lud'] ) {
							$newcands[] = $i;
						}
					}

					if ( count( $newcands ) === 1 ) {
						# echo "Kirjalyydin sana '$id' yhdistettiin etelälyydin sanaan taivutuksen
						# perusteella.\n\n";
						$south[$newcands[0]] =
							$this->mergeKirjaLyydiItem( $south[$newcands[0]], $entry );
						continue;
					}

					$cases = [];
					foreach ( $cands as $i ) {
						$name =
							$south[$i]['cases']['lud-x-south'] ??
							$south[$i]['cases']['lud-x-south'] ??
							$south[$i]['cases']['lud-x-middle'] ??
							$south[$i]['cases']['lud-x-north'] ?? '#';
						$cases[] = "$name ({$south[$i]['properties']['pos']})";
					}
					$cases = implode( "\n", $cases );
					echo "[LyKK] Kirjalyydin hakusanan '$id' yhdistäminen olemassa olevaan " .
						"sanatietueeseen ei onnistunut. " .
						"Useita vaihtoehtoja. Lisätään omana artikkelinaan.\n$cases\n\n";
				}
				$new[] = $entry;
				continue;
			}

			$south[$cands[0]] = $this->mergeKirjaLyydiItem( $south[$cands[0]], $entry );
		}

		return array_merge( $south, $new );
	}

	private function mergeKirjaLyydiItem( array $a, array $b ): array {
		$cases = array_merge( $a['cases'], $b['cases'] );

		return [
			'id' => $a['id'],
			'base' => $a['base'],
			'type' => $a['type'],
			'language' => $a['language'],
			'cases' => $cases,
			'properties' => $a['properties'],
			'examples' => array_merge( $a['examples'], $b['examples'] ),
			'translations' => array_merge_recursive( $a['translations'], $b['translations'] ),
			'links' => array_unique( array_merge( $a['links'], $b['links'] ) ),
		];
	}

	private function outputJson( array $out, string $outdir ): void {
		$blob = [];
		foreach ( $out as $struct ) {
			if ( $struct['type'] === 'disambiguation' ) {
				$struct['pages'] = array_map(
					function ( $x ) {
						return $x['id'];
					},
					$struct['pages']
				);
			}

			$blob[$struct['id']] = $struct;
		}

		$json = json_encode( $blob, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
		file_put_contents( "$outdir/data.json", $json );
	}

	private function outputWikitext( array $out, Formatter $f, string $outdir ): void {
		foreach ( $out as $struct ) {
			try {
				$title = $f->getTitle( $struct['id'] );
			} catch ( TypeError $e ) {
				echo "Unable to make title for:\n";
				echo json_encode( $struct, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) . "\n";
				echo $e;
				continue;
			}

			$text = $f->formatEntry( $struct );
			$title = str_replace( '/', '_', $title->getPrefixedText() );

			file_put_contents( "$outdir/$title", $text );
		}
	}
}
