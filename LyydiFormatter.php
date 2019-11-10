<?php

/**
 * @author Niklas Laxström
 * @license MIT
 */
class LyydiFormatter {
	public function getEntries( array $list ) : array {
		$list = $this->findHomonyms( $list );
		foreach ( $list as $entry ) {
			if ( $entry['type'] !== 'entry' ) {
				continue;
			}

			foreach ( $entry['examples'] as $index => $example ) {
				$list[] = [
					'id' => "{$entry['id']}/$index",
					'type' => 'entry-example',
					'example' => $example,
				];
				unset( $entry['examples'] );
			}
		}

		return $list;
	}

	public function findHomonyms( array $list ) : array {
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
			if ( count( $z ) <= 1 && $id !== $disId ) {
				// Already unique nothing to do
				continue;
			}

			foreach ( $z as $index => $i ) {
				// Assume there is never more than three :E
				$entries[$i]['id'] .=  ' ' . str_repeat( 'I', $index + 1 );
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

		if ( $entry['type'] === 'entry-example' ) {
			$out = "{{Example\n|entries=";
			foreach ( $entry['example'] as $lang => $text ) {
				$out .= "{{Example-entry|language=$lang\n|text=$text\n}}";

			}
			$out .= "\n}}\n";

			return $out;
		}

		$props = $entry['properties'];

		$out = "{{Word\n";
		$out .= "|baseform={$entry['base']}\n";
		foreach ( $props as $key => $value ) {
			$value = implode( ',', (array)$value );
			$out .= "|$key=$value\n";
		}
		$out .= "|variants=";
		foreach ( $entry['cases'] as $lang => $value ) {
			$out .= "{{Variant|language=$lang\n|text=$value\n}}\n";
		}
		$out .= "}}\n";


		$out .= "=={{INT:sanat-entry-translations}}==\n";
		foreach ( $entry['translations'] as $lang => $values ) {
			foreach ( (array)$values as $value ) {
				$out .= "{{Translation|language=$lang\n|text=$value\n}}\n";
			}
		}

		$out .= "=={{INT:sanat-entry-examples}}==\n";
		$out .= "{{Include examples}}\n\n";

		$out .= "=={{INT:sanat-entry-seealso}}==\n";
		foreach ( $entry['links'] as $target ) {
			$target = (array)$target;
			foreach ( $target as &$page ) {
				$page = "[[Lud:$page]]";
			}

			$joined = implode( '; ', $target );
			$out .= "* $joined\n";
		}

		return $out;
	}
}
