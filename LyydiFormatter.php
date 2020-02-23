<?php

/**
 * @author Niklas Laxström
 * @license GPL-2.0-or-later
 */
class LyydiFormatter {
	public function getEntries( array $list ) : array {
		$out = [];

		// Pass 1: collect homonyms together
		foreach ( $list as $entry ) {
			$id = $entry['id'];
			if ( !isset( $out[$id] ) ) {
				$out[$id] = $entry;
				continue;
			}

			// We already have place holder, append to it
			if ( $out[$id]['type'] === 'disambiguation' ) {
				$out[$id]['pages'][] = $entry;
				continue;
			}

			// Take copy of the entry we are going to replace
			$temp = $out[$id];
			$out[$id] = [
				'id' => $id,
				'type' => 'disambiguation',
				'pages' => [ $temp, $entry ],
			];
		}

		// Pass 2: make honomyns have unique page names
		foreach ( $out as $i => $entry ) {
			if ( $entry['type'] !== 'disambiguation' ) {
				continue;
			}

			$pages = $this->disambiguate( $entry['pages'], $i );
			$out[$i]['pages'] = $pages;

			// Add homonyms as separate pages too
			foreach ( $pages as $x ) {
				$out[$x['id']] = $x;
			}

		}

		return array_values( $out );
	}

	private function disambiguate( array $entries, string $disId ) : array {
		// Pass 1: add (pos) disambig if one exists
		foreach ( $entries as $i => $x ) {
			if ( isset( $x['properties']['pos'] ) ) {
				$entries[$i]['id'] = "{$x['id']} ({$x['properties']['pos']})";
			}
		}

		// Pass 2: add "roman" numerals as last resort

		// Pass 2a: build id => entries map
		$map = [];
		foreach ( $entries as $i => $x ) {
			$map[$x['id']] = $map[$x['id']] ?? [];
			$map[$x['id']][] = $i;
		}

		// Pass 2b: if id has multiple entries, disambiguate them
		foreach ( $map as $id => $z ) {
			$c = count( $z );

			if ( $c <= 1 && $id !== $disId ) {
				// Already unique nothing to do
				continue;
			}

			if ( $c === 1 ) {
				$entries[$z[0]]['id'] .= ' (KK)';
				continue;
			}

			echo "Sanalle $id löytyi $c eri sanaluokatonta artikkelia. Ohitetaan.\n";

			foreach ( $z as $index => $i ) {
				unset( $entries[$id] );
			}
		}

		return $entries;
	}

	public function getTitle( string $page ) : Title {
		return Title::newFromText( 'Lud:' . $page );
	}

	public function formatEntry( array $entry ) : string {
		if ( $entry['type'] === 'disambiguation' ) {
			$out = "{{INT:sanat-da}}\n";
			foreach ( $entry['pages'] as $subentry ) {
				$target = $this->getTitle( $subentry[ 'id' ] )->getPrefixedText();
				$out .= "* [[$target]]\n";
			}
			return $out;
		}

		if ( $entry['type'] === 'redirect' ) {
			return "#REDIRECT [[Lud:{$entry['target']}]]";
		}

		$props = $entry['properties'];

		$out = "{{Word\n";
		$out .= "|baseform={$entry['base']}\n";
		foreach ( $props as $key => $value ) {
			$value = implode( ',', (array)$value );
			$out .= "|$key=$value\n";
		}
		$out .= "|variants=";
		$cases = $this->sortTranslations( $entry['cases'] );
		foreach ( $cases as $lang => $value ) {
			$out .= "{{Variant\n|language=$lang\n|text=$value\n}}\n";
		}
		$out .= "}}\n";

		$out .= "=={{INT:sanat-entry-translations}}==\n\n";
		$translations = $this->sortTranslations( $entry['translations'] );
		foreach ( $translations as $lang => $values ) {
			foreach ( array_unique( $values ) as $value ) {
				$out .= "{{Translation\n|language=$lang\n|text=$value\n}}\n";
			}
		}

		$out .= "=={{INT:sanat-entry-examples}}==\n\n";
		$examples = $this->sortExamples( $entry['examples'] );
		foreach ( $examples as $example ) {
			$out .= "{{Example\n";
			$literature = $example['literature'] ?? false;
			unset( $example['literature'] );
			$out .= $literature ? "|literature=Kyllä\n" : "|literature=Ei\n";

			$example = $this->sortTranslations( $example );

			foreach ( $example as $lang => $value ) {
				$out .= "|$lang=$value\n";
			}

			$out .= "}}\n";
		}

		if ( $entry['links'] !== [] ) {
			$out .= "=={{INT:sanat-entry-seealso}}==\n";
			$links = $this->sortLinks( (array)$entry['links'] );
			foreach ( $links as $link ) {
				$out .= "* [[Lud:$link|]]\n";
			}
		}

		return $out;
	}

	private function sortTranslations( array $ts ): array {
		// literature is a hack for now, pass it through
		$order = [ 'lud', 'lud-x-south', 'lud-x-middle', 'lud-x-north', 'ru', 'fi' ];
		$sorted = [];
		foreach ( $order as $o ) {
			if ( isset( $ts[$o] ) ) {
				$sorted[$o] = $ts[$o];
				unset( $ts[$o] );
			}
		}

		if ( count( $ts ) ) {
			echo "Unknown language codes:\n";
			var_dump( $ts );
		}

		return $sorted;
	}

	private function sortExamples( array $es ): array {
		uasort( $es, function ( $a, $b ) {
			// kirjalyydi, etelälyydi, keskilyydi, pohjoislyydi, venäjä, suomi
			$order = [ 'lud', 'lud-x-south', 'lud-x-middle', 'lud-x-north', 'ru', 'fi' ];
			foreach ( $order as $o ) {
				$aa = isset( $a[$o] ) ? 1 : -1;
				$bb = isset( $b[$o] ) ? 1 : -1;
				$cmp = ( $aa ) <=> ( $bb );
				if ( $cmp !== 0 ) {
					return -$cmp;
				}
			}
		} );

		return $es;
	}

	private function sortLinks( array $ls ): array {
		sort( $ls );
		return $ls;
	}
}
