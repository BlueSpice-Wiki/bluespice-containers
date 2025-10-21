<?php

namespace MediaWiki\Extension\CollabPads\Backend\Model;

class TableSelection extends Selection {

	/**
	 * @var int
	 */
	private $toCol;

	/**
	 * @var int
	 */
	private $toRow;

	/**
	 * @var int
	 */
	private $fromCol;

	/**
	 * @var int
	 */
	private $fromRow;

	/**
	 * @param Range $range
	 * @param int $fromCol
	 * @param int $fromRow
	 * @param int $toCol
	 * @param int $toRow
	 */
	public function __construct( Range $range, $fromCol, $fromRow, $toCol, $toRow ) {
		parent::__construct( $range );
		$this->toCol = $toCol;
		$this->toRow = $toRow;
		$this->fromCol = $fromCol;
		$this->fromRow = $fromRow;
	}

	/**
	 * @return array[]
	 */
	public function jsonSerialize(): mixed {
		return [
			'type' => 'table',
			'tableRange' => [
				'type' => 'range',
				'from' => $this->getRange()->getFrom(),
				'to' => $this->getRange()->getTo()
			],
			'fromCol' => $this->fromCol,
			'fromRow' => $this->fromRow,
			'toCol' => $this->toCol,
			'toRow' => $this->toRow
		];
	}

	/**
	 * @param Transaction $transaction
	 * @param int $author
	 * @return $this
	 */
	public function translateByTransactionWithAuthor( Transaction $transaction, int $author ): Selection {
		$newRange = $transaction->translateRangeWithAuthor( $this->getRange(), $author );
		if ( $newRange->isCollapsed() ) {
			return new NullSelection();
		}
		return new static( $newRange, $this->fromCol, $this->fromRow, $this->toCol, $this->toRow );
	}
}
