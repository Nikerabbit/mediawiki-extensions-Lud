<?php
/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 * @file
 */

$IP = getenv( 'MW_INSTALL_PATH' ) ?: '../..';
require_once "$IP/maintenance/Maintenance.php";
require_once __DIR__ . '/LyydiConverter.php';
require_once __DIR__ . '/LyydiTabConverter.php';
require_once __DIR__ . '/LyydiFormatter.php';
require_once __DIR__ . '/KeskiLyydiTabConverter.php';
require_once __DIR__ . '/KirjaLyydiConverter.php';
require_once __DIR__ . '/KirjaLyydiTabConverter.php';

class LudImport extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Imports Lyydi word articles';
		$this->addOption( 'textfile', 'Text file to import' );
		$this->addOption( 'tabfile', 'Tabular file to import' );
		$this->addOption( 'kirjalyydi', 'Text file' );
		$this->addOption( 'LyK', 'CSV file for lud-x-middle' );
		$this->addOption( 'LyKK_SU', 'CSV file for lud' );
		$this->addArg( 'out', 'Dir to place wiki pages' );
	}

	public function execute() {
		ini_set( 'display_errors', '1' );
		error_reporting( E_ALL );
		$outdir = $this->getArg( 0 );

		// Master file lud-x-south
		$textfile = $this->getOption( 'textfile' );
		$textin = file_get_contents( $textfile );
		$textc = new LyydiConverter();
		$textout = $textc->parse( $textin );

		// Additional entries for lud-x-south in tabular format
		$tabfile = $this->getOption( 'tabfile' );
		$tabc = new LyydiTabConverter();
		$tabout = $tabc->parse( $tabfile );
		$out = $this->merge( array_merge( $textout, $tabout ) );

		// Merge in lud-x-middle
		$LyKfile = $this->getOption( 'LyK' );
		$LyKc = new KeskiLyydiTabConverter();
		$LyKout = $LyKc->parse( $LyKfile );
		$out = $this->mergeKeskilyydi( $out, $LyKout );

		// Parse in lud (txt)
		$kirjafile = $this->getOption( 'kirjalyydi' );
		$kirjain = file_get_contents( $kirjafile );
		$kirjac = new KirjaLyydiConverter();
		$kirjaout = $kirjac->parse( $kirjain );

		// Parse in lud (csv)
		$LyKK_SUfile = $this->getOption( 'LyKK_SU' );
		$LyKK_SUc = new KirjaLyydiTabConverter();
		$LyKK_SUout = $LyKK_SUc->parse( $LyKK_SUfile );
		$kirjaout = $this->mergeKirjaLyydiTranslations( $kirjaout, $LyKK_SUout );

		// Merge both
		$out = $this->mergeKirjaLyydi( $out, $kirjaout );

		$f = new LyydiFormatter();
		$out = $f->getEntries( $out );

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

	protected function merge( array $entries ) : array {
		$dedup = [];
		foreach ( $entries as $b ) {
			if ( $b[ 'type' ] !== 'entry' ) {
				continue;
			}

			$id = $b[ 'id' ] . ' ' . $b[ 'properties' ][ 'pos' ];

			if ( !isset( $dedup[ $id ] ) ) {
				$dedup[ $id ] = $b;
				continue;
			}

			try {
				$x = $this->mergeItems( $dedup[ $id ], $b );
				$dedup[ $id ] = $x;
			} catch ( Exception $err ) {
				echo "Yhdistetään sanaa $id: " . $err->getMessage() . "\n";
			}
		}

		return $dedup;
	}

	protected function mergeItems( $a, $b ) {
		$cases = $a[ 'cases' ];

		if ( $a[ 'cases' ][ 'lud-x-south' ] !== $b[ 'cases' ][ 'lud-x-south' ] ) {
			$ac = str_replace( '/', '', $a[ 'cases' ][ 'lud-x-south' ] );
			$bc = str_replace( '/', '', $b[ 'cases' ][ 'lud-x-south' ] );
			if ( strlen( $ac ) > strlen( $bc ) ) {
				[ $ac, $bc ] = [ $bc, $ac ];
			} else {
				$cases = $b[ 'cases' ];
			}

			if ( strpos( $bc, $ac ) === false ) {
				throw new RuntimeException( json_encode(
					[
						$a[ 'cases' ][ 'lud-x-south' ],
						$b[ 'cases' ][ 'lud-x-south' ]
					],
					JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE )
				);
			}
		}

		if ( $a[ 'properties' ][ 'pos' ] !== $b[ 'properties' ][ 'pos' ] ) {
			throw new RuntimeException(
				json_encode( [ 'properties', $a, $b ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE )
			);
		}

		if ( $a[ 'base' ] !== $b[ 'base' ] ) {
			throw new RuntimeException(
				json_encode( [ 'base', $a, $b ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE )
			);
		}

		if ( $a[ 'type' ] !== $b[ 'type' ] ) {
			throw new RuntimeException(
				json_encode( [ 'type', $a, $b ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE )
			);
		}

		$new = [
			'id' => $a[ 'id' ],
			'base' => $a[ 'base' ],
			'type' => $a[ 'type' ],
			'language' => $a[ 'language' ],
			'cases' => $a[ 'cases' ],
			'properties' => $a[ 'properties' ],
			'examples' => array_merge( $a[ 'examples' ], $b[ 'examples' ] ),
			'translations' => array_merge_recursive( $a[ 'translations' ], $b[ 'translations' ] ),
			'links' => array_unique( array_merge( $a[ 'links' ], $b[ 'links' ] ) ),
		];

		return $new;
	}

	private function mergeKeskilyydi( array $all, array $middle ): array {
		foreach ( $middle as $i => $m ) {
			foreach ( $all as $j => $a ) {
				if ( !$this->matchEntry( $m, $a ) ) {
					continue;
				}

				$all[$j] = $this->mergeKeskilyydiItem( $a, $m );
				unset( $middle[$i] );
			}
		}

		foreach ( $middle as $m ) {
		# echo "Keskilyydin sanalle {$m['id']} ({$m['properties']['pos']}) ei löytynyt vastinetta\n";
			$all[] = $m;
		}

		return $all;
	}

	private function matchEntry( array $a, array $b ): bool {
		return $a['id'] === $b['id'] && $a['properties']['pos'] === $b['properties']['pos'];
	}

	private function mergeKeskilyydiItem( array $a, array $b ): array {
		$a['cases'] = array_merge( $a['cases'], $b['cases'] );
		$a['examples'] = array_merge( $a[ 'examples' ], $b[ 'examples' ] );
		$a['translations'] = array_merge_recursive( $a[ 'translations' ], $b[ 'translations' ] );
		// Can skip links (none), pos (already same)
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
						if ( $south[$i]['cases']['lud-x-south'] ?? '#' === $entry['cases']['lud'] ) {
							$newcands[] = $i;
						}
					}

					if ( count( $newcands ) === 1 ) {
						echo "Kirjalyydin sana '$id' yhdistettiin etelälyydin sanaan taivutuksen perusteella.\n";
						$south[$newcands[0]] = $this->mergeKirjaLyydiItem( $south[$newcands[0]], $entry );
						continue;
					}

					$cases = [];
					foreach ( $cands as $i ) {
						$name = $south[$i]['cases']['lud-x-south'] ?? $south[$i]['cases']['lud-x-middle'] ?? '#';
						$cases[] = "$name ({$south[$i]['properties']['pos']})";
					}
					$cases = implode( "\n", $cases );
					echo "Kirjalyydin hakusanan '$id' yhdistäminen ei onnistunut. " .
						"Useita vaihtoehtoja. Lisätään omana artikkelinaan.\n$cases\n";
				}
				$new[] = $entry;
				continue;
			}

			$south[$cands[0]] = $this->mergeKirjaLyydiItem( $south[$cands[0]], $entry );
		}

		return array_merge( $south, $new );
	}

	private function mergeKirjaLyydiItem( array $a, array $b ): array {
		$cases = array_merge( $a[ 'cases' ], $b[ 'cases'] );

		$new = [
			'id' => $a[ 'id' ],
			'base' => $a[ 'base' ],
			'type' => $a[ 'type' ],
			'language' => $a[ 'language' ],
			'cases' => $cases,
			'properties' => $a[ 'properties' ],
			'examples' => array_merge( $a[ 'examples' ], $b[ 'examples' ] ),
			'translations' => array_merge_recursive( $a[ 'translations' ], $b[ 'translations' ] ),
			'links' => array_unique( array_merge( $a[ 'links' ], $b[ 'links' ] ) ),
		];

		return $new;
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
}

$maintClass = 'LudImport';
require_once RUN_MAINTENANCE_IF_MAIN;
