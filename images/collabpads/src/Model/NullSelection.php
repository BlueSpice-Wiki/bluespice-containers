<?php

namespace MediaWiki\Extension\CollabPads\Backend\Model;

class NullSelection extends Selection {

	public function __construct() {
		return parent::__construct( new Range( 0, 0 ) );
	}

	/**
	 * @return array[]
	 */
	public function jsonSerialize(): mixed {
		return [ 'type' => null ];
	}

	/**
	 * @param Transaction $transaction
	 * @param int $author
	 * @return $this
	 */
	public function translateByTransactionWithAuthor( Transaction $transaction, int $author ): Selection {
		return new self;
	}
}
