<?php
/**
 * @author Niklas Laxström
 * @license MIT
 * @file
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$IP = getenv( 'MW_INSTALL_PATH' ) ?: '../..';
require_once "$IP/maintenance/Maintenance.php";
require_once __DIR__ . '/LyydiConverter.php';
require_once __DIR__ . '/LyydiTabConverter.php';
require_once __DIR__ . '/LyydiFormatter.php';
require_once __DIR__ . '/KirjaLyydiConverter.php';

class LudImport extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = 'Imports Lyydi word articles';
		$this->addOption( 'textfile', 'Text file to import' );
		$this->addOption( 'tabfile', 'Tabular file to import' );
		$this->addOption( 'kirjalyydi', 'Text file' );
		$this->addArg( 'out', 'Dir to place wiki pages' );
	}

	public function execute() {
		$outdir = $this->getArg( 0 );

		$textfile = $this->getOption( 'textfile' );
		$textin = file_get_contents( $textfile );
		$textc = new LyydiConverter();
		$textout = $textc->parse( $textin );


		$tabfile = $this->getOption( 'tabfile' );
		$tabc = new LyydiTabConverter();
		$tabout = $tabc->parse( $tabfile );

		$out = $this->merge( array_merge( $textout, $tabout ) );


		$kirjafile = $this->getOption( 'kirjalyydi' );
		$kirjain = file_get_contents( $kirjafile );
		$kirjac = new KirjaLyydiConverter();
		$kirjaout = $kirjac->parse( $kirjain );

		$out = $this->mergeKirjaLyydi( $out, $kirjaout );

		$f = new LyydiFormatter();
		$out = $f->getEntries( $out );

		foreach ( $out as $struct ) {
			try {
				$title = $f->getTitle( $struct['id'] );
			} catch ( TypeError $e ) {
				echo "Unable to make title for:\n";
				echo json_encode( $struct, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ."\n";
				echo $e;
				continue;
			}

			$text = $f->formatEntry( $struct );
			$title = str_replace( '/', '_', $title );

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
				[$ac, $bc] = [$bc, $ac];
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
			throw new RuntimeException( json_encode( [ 'properties', $a, $b ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
		}

		if ( $a[ 'base' ] !== $b[ 'base' ] ) {
			throw new RuntimeException( json_encode( [ 'base', $a, $b ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
		}

		if ( $a[ 'type' ] !== $b[ 'type' ] ) {
			throw new RuntimeException( json_encode( [ 'type', $a, $b ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
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

		foreach ( $new[ 'translations' ] as $lang => $v ) {
			$new[ 'translations'][ $lang ] = array_unique( $v );
		}

		return $new;
	}

	private function mergeKirjaLyydi( array $south, array $kirja ) : array {
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
					echo "Kirjalyydin hakusanalla '$id' löytyi useita etelälyydin sanoja. Lisätään erikseen\n";
				}
				$entry['id'] = "{$entry['id']} (KK)";
				#echo "Adding $id as new KK entry\n";
				$new[] = $entry;
			} else {
				#echo "Merging $id to south\n";
				$south[$cands[0]] = $this->mergeKirjaLyydiItem( $south[$cands[0]], $entry );
			}
		}

		return array_merge( $south, $new );
	}

	private function mergeKirjaLyydiItem( array $a, array $b ) : array {
		$new = [
			'id' => $a[ 'id' ],
			'base' => $a[ 'base' ],
			'type' => $a[ 'type' ],
			'language' => $a[ 'language' ],
			'cases' => array_merge( $a[ 'cases' ], $b[ 'cases'] ),
			'properties' => $a[ 'properties' ],
			'examples' => array_merge( $a[ 'examples' ], $b[ 'examples' ] ),
			'translations' => array_merge_recursive( $a[ 'translations' ], $b[ 'translations' ] ),
			'links' => array_unique( array_merge( $a[ 'links' ], $b[ 'links' ] ) ),
		];

		foreach ( $new[ 'translations' ] as $lang => $v ) {
			$new[ 'translations'][ $lang ] = array_unique( $v );
		}

		return $new;
	}
}

$maintClass = 'LudImport';
require_once RUN_MAINTENANCE_IF_MAIN;
