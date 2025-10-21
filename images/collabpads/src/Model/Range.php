<?php

namespace MediaWiki\Extension\CollabPads\Backend\Model;

class Range implements \JsonSerializable {

	/**
	 * @var int
	 */
	private $from;

	/**
	 * @var int
	 */
	private $to;

	/**
	 * @var int
	 */
	private $start;

	/**
	 * @var int
	 */
	private $end;

	/**
	 * @param int $from
	 * @param int $to
	 */
	public function __construct( int $from, int $to ) {
		$this->from = $from;
		$this->to = $to;
		$this->start = min( $from, $to );
		$this->end = max( $from, $to );
	}

	/**
	 * @param array $range
	 * @return static
	 */
	public static function newFromData( array $range ) {
		return new static( $range['from'], $range['to'] );
	}

	/**
	 * @return int|mixed
	 */
	public function getStart(): mixed {
		return $this->start;
	}

	/**
	 * @return int
	 */
	public function getFrom(): int {
		return $this->from;
	}

	/**
	 * @return int|mixed
	 */
	public function getEnd(): mixed {
		return $this->end;
	}

	/**
	 * @return int
	 */
	public function getTo(): int {
		return $this->to;
	}

	/**
	 * @return bool
	 */
	public function isBackwards(): bool {
		return $this->from > $this->to;
	}

	/**
	 * @return mixed
	 */
	public function jsonSerialize(): mixed {
		return [
			'from' => $this->from,
			'to' => $this->to
		];
	}

	/**
	 * @return bool
	 */
	public function isCollapsed(): bool {
		return $this->from === $this->to;
	}

}
