<?php

namespace MediaWiki\Extension\CollabPads\Backend\Model;

class LinearSelection extends Selection {

	/**
	 * @return array[]
	 */
	public function jsonSerialize(): mixed {
		return array_merge( [
			'type' => 'linear'
		], parent::jsonSerialize() );
	}

	/**
	 * @param Transaction $transaction
	 * @param int $author
	 * @return Selection
	 */
	public function translateByTransactionWithAuthor( Transaction $transaction, int $author ): Selection {
		return new static( $transaction->translateRangeWithAuthor( $this->getRange(), $author ) );
	}
}
