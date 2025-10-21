<?php

namespace MediaWiki\Extension\CollabPads\Backend;

use Exception;
use MediaWiki\Extension\CollabPads\Backend\DAO\MongoDBCollabSessionDAO;
use MediaWiki\Extension\CollabPads\Backend\Model\Author;
use MediaWiki\Extension\CollabPads\Backend\Model\Change;
use MediaWiki\Extension\CollabPads\Backend\Model\LinearSelection;
use MediaWiki\Extension\CollabPads\Backend\Model\NullSelection;
use MediaWiki\Extension\CollabPads\Backend\Model\Range;
use MediaWiki\Extension\CollabPads\Backend\Model\Selection;
use MediaWiki\Extension\CollabPads\Backend\Model\TableSelection;
use MediaWiki\Extension\CollabPads\Backend\Model\Transaction;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Rebaser implements LoggerAwareInterface {

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var MongoDBCollabSessionDAO
	 */
	private $session;

	/**
	 * @param MongoDBCollabSessionDAO $session
	 */
	public function __construct( MongoDBCollabSessionDAO $session ) {
		$this->session = $session;
		$this->logger = new NullLogger();
	}

	/**
	 * @param LoggerInterface $logger
	 * @return void
	 */
	public function setLogger( LoggerInterface $logger ): void {
		$this->logger = $logger;
	}

	/**
	 * @param int $sessionId
	 * @param Author $author
	 * @param int $backtrack
	 * @param Change $change
	 * @return Change
	 * @throws Exception
	 */
	public function applyChange( int $sessionId, Author $author, int $backtrack, Change $change ): Change {
		$this->logger->info( "Rebasing change", [
			'author' => json_encode( $author ),
			'change' => json_encode( $change ),
			'backtrack' => $backtrack,
			'sessionId' => $sessionId,
		] );

		$base = $this->session->getAuthorContinueBase( $sessionId, $author ) ?? $change->truncate( 0 );
		$rejections = $this->session->getAuthorRejections( $sessionId, $author );
		if ( $rejections > $backtrack ) {
			// Comment from original implementation:
			// Follow-on does not fully acknowledge outstanding conflicts: reject entirely
			$rejections = $rejections - $backtrack + $change->getLength();
			$this->session->changeAuthorDataInSession( $sessionId, $author->getId(), 'rejections', $rejections );
			// Comment from original implementation:
			// FIXME argh this publishes an empty change, which is not what we want
			// PHP-port comment:
			// As original comment above says, this is definitely not what we want,
			// not clear how to fix or even when this would happen
			$this->logger->warning( "Rejections higher than backtrack, rejecting change entirely", [
				'rejections' => $rejections,
				'backtrack' => $backtrack
			] );
			$appliedChange = new Change( 0, [], [], [] );
		} elseif ( $rejections < $backtrack ) {
			$this->logger->error( "Cannot backtrack long enough: Backtrack=$backtrack, rejections=$rejections" );
			throw new Exception( "Cannot backtrack long enough: Backtrack=$backtrack, rejections=$rejections" );
		} else {
			if ( $change->getStart() > $base->getStart() ) {
				// Comment from original implementation:
				// Remote has rebased some committed changes into its history since base was built.
				// They are guaranteed to be equivalent to the start of base. See mathematical
				// docs for proof (Cuius rei demonstrationem mirabilem sane deteximus hanc marginis
				// exiguitas non caperet).
				$base = $base->mostRecent( $change->getStart() );
			}
			$sessionChange = $this->session->getChange( $sessionId );
			$base = $base->concat( $sessionChange->mostRecent( $base->getStart() + $base->getLength() ) );
			$result = $this->rebaseUncommittedChange( $base, $change );
			$rejections = $result['rejected'] ? $result['rejected']->getLength() : 0;
			if ( !$result['rebased']->isEmpty() ) {
				// Update session with newly applied change
				$sessionChange->push( $result['rebased'] );
				$this->session->replaceHistory( $sessionId, $sessionChange );
			}
			$this->session->changeAuthorDataInSession(
				$sessionId,
				$author->getId(),
				'rejections',
				$rejections
			);
			$this->session->changeAuthorDataInSession(
				$sessionId,
				$author->getId(),
				'continueBase',
				json_decode( json_encode( $result['transposedHistory'] ), true )
			);
			$appliedChange = $result['rebased'];
		}

		$this->logger->debug( 'Change rebased', [
			'sessionId' => $sessionId,
			'authorId' => $author->getId(),
			'incoming' => json_decode( json_encode( $change ), true ),
			'applied' => json_decode( json_encode( $appliedChange ), true ),
			'backtrack' => $backtrack,
			'rejections' => $rejections
		] );
		return $appliedChange;
	}

	/**
	 * @param Change $base
	 * @param Change $uncommited
	 * @return Change[]
	 * @throws Exception
	 */
	private function rebaseUncommittedChange( Change $base, Change $uncommited ): array {
		if ( $base->getStart() !== $uncommited->getStart() ) {
			if ( $base->getStart() > $uncommited->getStart() && $base->getLength() === 0 ) {
				$base->setStart( $uncommited->getStart() );
				return $this->rebaseUncommittedChange( $base, $uncommited );
			}
			if ( $uncommited->getStart() > $base->getStart() && $uncommited->getLength() === 0 ) {
				$uncommited->setStart( $base->getStart() );
				return $this->rebaseUncommittedChange( $base, $uncommited );
			}

			throw new Exception( 'Different starts: ' . $base->getStart() . ' and ' . $uncommited->getStart() );
		}

		$transactionsA = $base->getTransactions();
		$transactionsB = $uncommited->getTransactions();
		$storesA = $base->getStores();
		$storesB = $uncommited->getStores();
		$selectionsA = $base->getSelections();
		$selectionsB = $uncommited->getSelections();
		$rejected = null;

		// Comment from original implementation:
		// For each element b_i of transactionsB, rebase the whole list transactionsA over b_i.
		// To rebase a1, a2, a3, …, aN over b_i, first we rebase a1 onto b_i. Then we rebase
		// a2 onto some b', defined as
		//
		// b_i' := b_i|a1 , that is b_i.rebasedOnto(a1)
		//
		// (which as proven above is equivalent to inv(a1) * b_i * a1)
		//
		// Similarly we rebase a3 onto b_i'' := b_i'|a2, and so on.
		//
		// The rebased a_j are used for the transposed history: they will all get rebased over the
		// rest of transactionsB in the same way.
		// The fully rebased b_i forms the i'th element of the rebased transactionsB.
		//
		// If any rebase b_i|a_j fails, we stop rebasing at b_i (i.e. finishing with b_{i-1}).
		// We return
		// - rebased: (uncommitted sliced up to i) rebased onto history
		// - transposedHistory: history rebased onto (uncommitted sliced up to i)
		// - rejected: uncommitted sliced from i onwards
		for ( $i = 0, $iLen = count( $transactionsB ); $i < $iLen; $i++ ) {
			$b = $transactionsB[ $i ];
			$storeB = $storesB[ $i ] ?? null;
			$rebasedTransactionsA = [];
			$rebasedStoresA = [];
			for ( $j = 0, $jLen = count( $transactionsA ); $j < $jLen; $j++ ) {
				$a = $transactionsA[ $j ];
				$storeA = $storesA[ $j ] ?? null;
				$rebases = $b->getAuthor() < $a->getAuthor() ?
					array_reverse( $this->rebaseTransactions( $b, $a ) ) :
					$this->rebaseTransactions( $a, $b );
				if ( $rebases[ 0 ] === null ) {
					$rejected = $uncommited->mostRecent( $uncommited->getStart() + $i );
					$transactionsB = array_slice( $transactionsB, 0, $i );
					$storesB = array_slice( $storesB, 0, $i );
					$selectionsB = [];
					break 2;
				}
				$rebasedTransactionsA[ $j ] = $rebases[ 0 ];
				if ( $storeA && $storeB ) {
					$rebasedStoresA[ $j ] = $storeA->difference( $storeB );
				}
				$b = $rebases[ 1 ];
				if ( $storeB && $storeA ) {
					$storeB = $storeB->difference( $storeA );
				}
			}
			$transactionsA = $rebasedTransactionsA;
			$storesA = $rebasedStoresA;
			$transactionsB[ $i ] = $b;
			if ( $storeB ) {
				$storesB[ $i ] = $storeB;
			}
		}
		$rebased = new Change(
			$uncommited->getStart() + count( $transactionsA ),
			$transactionsB,
			$selectionsB,
			$storesB
		);

		$transposedHistory = new Change(
			$base->getStart() + count( $transactionsB ),
			$transactionsA,
			$selectionsA,
			$storesA
		);
		foreach ( $selectionsB as $authorId => $selection ) {
			$translated = $this->translateSelectionByChange( $selection, $transposedHistory, $authorId );
			if ( $translated ) {
				$rebased->setSelection( $authorId, $translated );
			}
		}
		foreach ( $selectionsA as $authorId => $selection ) {
			$translated = $this->translateSelectionByChange( $selection, $rebased, $authorId );
			if ( $translated ) {
				$transposedHistory->setSelection( $authorId, $translated );
			}
		}

		return [
			'rejected' => $rejected,
			'rebased' => $rebased,
			'transposedHistory' => $transposedHistory
		];
	}

	/**
	 * @param Transaction $a
	 * @param Transaction $b
	 * @return array|null[]
	 */
	private function rebaseTransactions( Transaction $a, Transaction $b ): array {
		$infoA = $a->getActiveRangeAndLengthDiff();
		$infoB = $b->getActiveRangeAndLengthDiff();

		if ( $infoA['start'] === null || $infoB['start'] === null ) {
			// One of the transactions is a no-op: only need to adjust its retain length.
			// We can safely adjust both, because the no-op must have diff 0
			$a->adjustRetain( 'start', $infoB['diff'] );
			$b->adjustRetain( 'start', $infoA['diff'] );
		} elseif ( $infoA['end'] <= $infoB['start'] ) {
			// This includes the case where both transactions are insertions at the same
			// point
			$b->adjustRetain( 'start', $infoA['diff'] );
			$a->adjustRetain( 'end', $infoB['diff'] );
		} elseif ( $infoB['end'] <= $infoA['start'] ) {
			$a->adjustRetain( 'start', $infoB['diff'] );
			$b->adjustRetain( 'end', $infoA['diff'] );
		} else {
			$this->logger->error( 'Failed to rebase transactions', [
				'a' => $a,
				'b' => $b,
				'infoA' => $infoA,
				'infoB' => $infoB
			] );
			// The active ranges overlap: conflict
			return [ null, null ];
		}
		return [ $a, $b ];
	}

	/**
	 * @param array $selection
	 * @param Change $change
	 * @param int $authorId
	 * @return Selection|null
	 * @throws Exception
	 */
	private function translateSelectionByChange( array $selection, Change $change, int $authorId ): ?Selection {
		$type = $selection['type'];
		if ( !$type ) {
			$this->logger->error( 'Trying to create selection without type', $selection );
			throw new Exception( 'Selection type not set' );
		}
		switch ( $type ) {
			case 'linear':
				$selection = new LinearSelection( Range::newFromData( $selection['range'] ) );
				break;
			case 'table':
				$selection = new TableSelection(
					Range::newFromData( $selection['tableRange'] ),
					$selection['fromCol'], $selection['fromRow'], $selection['toCol'], $selection['toRow']
				);
				break;
			case null:
				$selection = new NullSelection();
				break;
			default:
				// Not a selection event
				return null;
		}

		foreach ( $change->getTransactions() as $transaction ) {
			$selection = $selection->translateByTransactionWithAuthor( $transaction, $authorId );
		}
		return $selection;
	}

}
