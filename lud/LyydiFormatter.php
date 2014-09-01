<?php

/**
 * @author Niklas Laxström
 * @license MIT
 */
class LyydiFormatter {
	public function getEntries( $list ) {
		$list = $this->findHomonyms( $list );
		foreach ( $list as $entry ) {
			if ( $entry['type'] !== 'entry' ) {
				continue;
			}

			foreach ( $entry['examples'] as $index => $example ) {
				$list[] = array(
					'id' => "{$entry['id']}/$index",
					'type' => 'entry-example',
					'example' => $example,
				);
				unset( $entry['examples'] );
			}
		}

		return $list;
	}

	public function findHomonyms( $list ) {
		$out = array();
		foreach ( $list as $entry ) {
			$id = $entry['id'];
			if ( !isset( $out[$id] ) ) {
				$out[$id] = $entry;
				continue;
			}

			$da = $this->getDisambiguator( $entry );
			$entry['id'] .= " ($da)";

			// We already have place holder, append to it
			if ( $entry['type'] === 'disambiguation' ) {
				$out[$id]['pages'][] = $entry;
			} else {
				// Take copy of the entry we are going to replace
				$temp = $out[$id];
				// Update the name of the existing one so that it does not clash
				$da = $this->getDisambiguator( $temp );
				$temp['id'] .= " ($da)";

				$out[$id] = array(
					'id' => $id,
					'type' => 'disambiguation',
					'pages' => array( $temp, $entry ),
				);

				// Also must keep them as their own entries in the list
				$out[$entry['id']] = $entry;
				$out[$temp['id']] = $temp;
			}
		}

		return array_values( $out );
	}

	public function getDisambiguator( $entry ) {
		if ( isset( $entry['properties']['pos'] ) ) {
			return $entry['properties']['pos'];
		}

		return $entry['type'];
	}


	public function getTitle( $entry ) {
		return Title::newFromText( 'Lud:' . $entry['id'] );
	}

	public function formatEntry( $entry ) {
		if ( $entry['type'] === 'disambiguation' ) {
			$out = "{{INT:sanat-da}}\n";
			foreach ( $entry['pages'] as $subentry ) {
				$target = $this->getTitle( $subentry )->getPrefixedText();
				$out .= "* [[$target]]\n";
			}
			return $out;
		}

		if ( $entry['type'] === 'redirect' ) {
			return "#REDIRECT [[Lud:{$entry['target']}]]";
		}

		if ( $entry['type'] === 'entry-example' ) {
			$out = "{{Example\n";
			foreach ( $entry['example'] as $lang => $text ) {
				if ( $lang === 'literature' ) {
					$text = $text ? 'Y' : 'N';
					$out .= "|literature=$text\n|entries=";
					continue;
				}

				$out .= "{{Example-entry|language=$lang\n|text=$text\n}}";

			}
			$out .= "\n}}\n";

			return $out;
		}

		$pos = $entry['properties']['pos'];
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
