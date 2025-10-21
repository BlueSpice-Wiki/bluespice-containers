<?php

namespace MediaWiki\Extension\CollabPads\Backend\Model;

abstract class Selection implements \JsonSerializable {

	/**
	 * @var Range
	 */
	private $range;

	/**
	 * @param Range $range
	 */
	public function __construct( Range $range ) {
		$this->range = $range;
	}

	/**
	 * @return Range
	 */
	public function getRange(): Range {
		return $this->range;
	}

	/**
	 * @return array[]
	 */
	public function jsonSerialize(): mixed {
		return [
			'range' => $this->range
		];
	}

	/**
	 * @param Transaction $transaction
	 * @param int $author
	 * @return Selection
	 */
	abstract public function translateByTransactionWithAuthor( Transaction $transaction, int $author ): Selection;
}
