<?php

namespace MediaWiki\Extension\CollabPads\Backend\Model;

class Transaction implements \JsonSerializable {

	/**
	 * @var array
	 */
	private $operations;

	/**
	 * @var int|null
	 */
	private $author;

	/**
	 * @param array $operations
	 * @param int|null $author
	 */
	public function __construct( array $operations, ?int $author = null ) {
		$this->operations = $operations;
		$this->author = $author;
	}

	/**
	 * @param array|string $data
	 * @return Transaction
	 */
	public static function fromMinified( $data ): Transaction {
		if ( !isset( $data['o'] ) ) {
			$a = $data['a'] ?? null;
			unset( $data['a'] );
			return new Transaction( static::deminifyOperations( $data ), $a );
		}
		$operations = static::deminifyOperations( $data['o'] );
		return new Transaction( $operations, $data['a'] );
	}

	/**
	 * @param string $data
	 * @return array
	 */
	public static function split( string $data ): array {
		$bits = mb_str_split( $data );
		$final = [];
		foreach ( $bits as $bit ) {
			if ( static::isEmoji( $bit ) ) {
				$final[] = $bit;
				// Pad for surrogate
				$final[] = ' ';
				continue;
			}
			$final[] = $bit;
		}

		return $final;
	}

	/**
	 * @param string $char
	 * @return bool
	 */
	private static function isEmoji( string $char ): bool {
		// Convert the string to UTF-16
		$utf16 = mb_convert_encoding( $char, 'UTF-16', 'UTF-8' );

		// Unpack the UTF-16 string into an array of code units
		$codeUnits = unpack( 'n*', $utf16 );

		// Check if the string contains surrogate pairs
		foreach ( $codeUnits as $codeUnit ) {
			if ( $codeUnit >= 0xD800 && $codeUnit <= 0xDFFF ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param Transaction $foreign
	 * @return bool
	 */
	public function equals( Transaction $foreign ): bool {
		return $this->author === $foreign->author && $this->operations === $foreign->operations;
	}

	/**
	 * @return int|null
	 */
	public function getAuthor(): ?int {
		return $this->author;
	}

	/**
	 * @param array $ops
	 * @return array
	 */
	private static function deminifyOperations( array $ops ) {
		$expanded = [];
		foreach ( $ops as $op ) {
			if ( is_numeric( $op ) ) {
				$expanded[] = [ 'type' => 'retain', 'length' => $op ];
				continue;
			}
			if ( static::isLinearArray( $op ) ) {
				$expanded[] = [
					'type' => 'replace',
					'remove' => static::deminifyLinearData( $op[0] ),
					'insert' => static::deminifyLinearData( $op[1] )
				];
			} else {
				// Clone $op
				$expanded[] = array_merge( [], $op );
			}
		}

		return $expanded;
	}

	/**
	 * @param mixed $op
	 * @return bool
	 */
	private static function isLinearArray( $op ): bool {
		return is_array( $op ) && array_keys( $op ) === range( 0, count( $op ) - 1 );
	}

	/**
	 * @param mixed $element
	 * @return array|mixed
	 */
	private static function deminifyLinearData( $element ) {
		if ( is_string( $element ) ) {
			if ( $element === '' ) {
				return [];
			}
			return static::split( $element );
		}
		return $element;
	}

	/**
	 * @return array
	 */
	public function getActiveRangeAndLengthDiff() {
		$offset = 0;
		$diff = 0;

		$start = $startOpIndex = $end = $endOpIndex = null;
		for ( $i = 0, $len = count( $this->operations ); $i < $len; $i++ ) {
			$op = $this->operations[ $i ];
			$active = $op['type'] !== 'retain';
			// Place start marker
			if ( $active && $start === null ) {
				$start = $offset;
				$startOpIndex = $i;
			}
			// Adjust offset and diff
			if ( $op['type'] === 'retain' ) {
				$offset += $op['length'];
			} elseif ( $op['type'] === 'replace' ) {
				$offset += $this->operationLen( $op['insert'] );
				$diff += $this->operationLen( $op['insert'] ) - $this->operationLen( $op['remove'] );
			}
			if ( $op['type'] === 'attribute' || $op['type'] === 'replaceMetadata' ) {
				// Op with length 0 but that effectively modifies 1 position
				$end = $offset + 1;
				$endOpIndex = $i + 1;
			} elseif ( $active ) {
				$end = $offset;
				$endOpIndex = $i + 1;
			}
		}

		return [
			'start' => $start,
			'end' => $end,
			'startOpIndex' => $startOpIndex,
			'endOpIndex' => $endOpIndex,
			'diff' => $diff
		];
	}

	/**
	 * @param string $place
	 * @param int $diff
	 * @return void
	 */
	public function adjustRetain( string $place, int $diff ) {
		if ( $diff === 0 ) {
			return;
		}
		$start = $place === 'start';
		$ops = $this->operations;
		$i = $start ? 0 : count( $ops ) - 1;

		if ( $ops[$i] && $ops[$i]['type'] === 'retain' ) {
			$ops[$i]['length'] += $diff;
			if ( $ops[$i]['length'] < 0 ) {
				throw new \Error( 'Negative retain length' );
			} elseif ( $ops[$i]['length'] === 0 ) {
				array_splice( $ops, $i, 1 );
			}
			$this->operations = $ops;
			return;
		}
		if ( $diff < 0 ) {
			throw new \Error( 'Negative retain length' );
		}
		$this->operations = array_splice(
			$ops, $start ? 0 : count( $ops ), 0, [ 'type' => 'retain', 'length' => $diff ]
		);
	}

	/**
	 * @return mixed
	 */
	public function jsonSerialize(): mixed {
		$operations = array_map( function ( $op ) {
			if ( $op['type'] === 'retain' ) {
				return $op['length'];
			}
			$insertLength = isset( $op['insert'] ) ? $this->operationLen( $op['insert'] ) : 0;
			if (
				$op['type'] === 'replace' &&
				( !isset( $op['insertedDataOffset' ] ) || !$op['insertedDataOffset'] ) &&
				( !isset( $op['insertedDataLength' ] ) || $op['insertedDataLength'] === $insertLength )
			) {
				return [ $this->minifyLinearData( $op['remove'] ), $this->minifyLinearData( $op['insert'] ) ];
			}
			return $op;
		}, $this->operations );

		if ( $this->author !== null ) {
			return [
				'o' => $operations,
				'a' => $this->author
			];
		} else {
			return $operations;
		}
	}

	/**
	 * @param Range $range
	 * @param int $author
	 * @return Range
	 */
	public function translateRangeWithAuthor( Range $range, int $author ): Range {
		$backward = !$this->author || !$author || $author < $this->author;
		$start = $this->translateOffset( $range->getStart(), $backward );
		$end = $this->translateOffset( $range->getEnd(), $backward );

		return $range->isBackwards() ? new Range( $end, $start ) : new Range( $start, $end );
	}

	/**
	 * @param mixed $data
	 * @return mixed|string
	 */
	private function minifyLinearData( mixed $data ) {
		if ( is_array( $data ) ) {
			if ( empty( $data ) ) {
				return '';
			}
			$allSingle = true;
			foreach ( $data as $element ) {
				if ( !is_string( $element ) || strlen( $element ) !== 1 ) {
					$allSingle = false;
					break;
				}
			}
			if ( $allSingle ) {
				return implode( '', $data );
			}
			// Handle special case => template with no params
			// Due to the way PHP handles json encoding/decoding of empty arrays, it will
			// produce an array, instead of {}
			// in JSON output, this breaks Parsoid conversion
			foreach ( $data as &$element ) {
				if (
					isset( $element['type'] ) && $element['type'] === 'mwTransclusionBlock' &&
					isset( $element['attributes']['mw']['parts'] ) && is_array( $element['attributes']['mw']['parts'] )
				) {
					foreach ( $element['attributes']['mw']['parts'] as &$part ) {
						if (
							is_array( $part ) && isset( $part['template']['params'] ) &&
							is_array( $part['template']['params'] ) && empty( $part['template']['params'] )
						) {
							$part['template']['params'] = new \stdClass();
						}
					}
				}
			}

		}
		return $data;
	}

	/**
	 * @param int $offset
	 * @param bool $excludeInsertion
	 * @return int|mixed
	 */
	private function translateOffset( int $offset, bool $excludeInsertion ) {
		$cursor = 0;
		$adjustment = 0;
		foreach ( $this->operations as $operation ) {
			if (
				$operation['type'] === 'retain' ||
				(
					$operation['type'] === 'replace' &&
					$this->operationLen( $operation['insert'] ) === $this->operationLen( $operation['remove'] ) &&
					$this->compareElementsForTranslate( $operation['insert'], $operation['remove'] )
				)
			) {
				$retainLength = $operation['type'] === 'retain' ?
					$operation['length'] :
					$this->operationLen( $operation['remove'] );
				if ( $offset >= $cursor && $offset < $cursor + $retainLength ) {
					return $offset + $adjustment;
				}
				$cursor += $retainLength;
				continue;
			} else {
				$insertLength = $this->operationLen( $operation['insert'] );
				$removeLength = $this->operationLen( $operation['remove'] );
				$prevAdjustment = $adjustment;
				$adjustment += $insertLength - $removeLength;
				if ( $offset === $cursor + $removeLength ) {
					if ( $excludeInsertion && $insertLength > $removeLength ) {
						return $offset + $adjustment - $insertLength + $removeLength;
					}
					return $offset + $adjustment;
				} elseif ( $offset === $cursor ) {
					if ( $insertLength === 0 ) {
						return $cursor + $removeLength + $adjustment;
					}
					return $cursor + $prevAdjustment;
				} elseif ( $offset > $cursor && $offset < $cursor + $removeLength ) {
					return $cursor + $removeLength + $adjustment;
				}
				$cursor += $removeLength;
			}
		}
		return $offset + $adjustment;
	}

	/**
	 * @param mixed $op
	 * @return int
	 */
	private function operationLen( mixed $op ): int {
		if ( is_array( $op ) ) {
			if ( isset( $op['length'] ) ) {
				return (int)$op['length'];
			}
			return count( $op );
		}
		return strlen( $op ) ?? 0;
	}

	/**
	 * @param mixed $insert
	 * @param mixed $remove
	 * @return bool
	 */
	private function compareElementsForTranslate( mixed $insert, mixed $remove ) {
		foreach ( $insert as $i => $element ) {
			if ( !$this->doCompareElements( $element, $remove[$i] ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * @param mixed $a
	 * @param mixed $b
	 * @return bool
	 */
	private function doCompareElements( mixed $a, mixed $b ): bool {
		if ( $a === $b ) {
			return true;
		}
		$aPlain = $a;
		$bPlain = $b;

		if ( is_array( $a ) && array_keys( $a ) === range( 0, count( $a ) - 1 ) ) {
			$aPlain = $a[0];
		}
		if ( is_array( $b ) && array_keys( $b ) === range( 0, count( $b ) - 1 ) ) {
			$bPlain = $b[0];
		}

		if ( is_string( $aPlain ) && is_string( $bPlain ) ) {
			return $aPlain === $bPlain;
		}
		if ( ( isset( $aPlain['type'] ) && isset( $bPlain['type'] ) ) && $aPlain['type'] !== $bPlain['type'] ) {
			return false;
		}

		return true;
	}

	/**
	 * @return array
	 */
	public function getOperations(): array {
		return $this->operations;
	}

}
