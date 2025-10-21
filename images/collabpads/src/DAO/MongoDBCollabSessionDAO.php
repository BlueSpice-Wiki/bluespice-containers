<?php

namespace MediaWiki\Extension\CollabPads\Backend\DAO;

use MediaWiki\Extension\CollabPads\Backend\ICollabSessionDAO;
use MediaWiki\Extension\CollabPads\Backend\Model\Author;
use MediaWiki\Extension\CollabPads\Backend\Model\Change;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class MongoDBCollabSessionDAO extends MongoDBDAOBase implements ICollabSessionDAO, LoggerAwareInterface {

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @param array $config
	 * @throws \Exception
	 */
	public function __construct( array $config ) {
		parent::__construct( $config );
		$this->logger = new NullLogger();
	}

	/**
	 * @inheritDoc
	 */
	public function setLogger( LoggerInterface $logger ): void {
		$this->logger = $logger;
	}

	/**
	 * @inheritDoc
	 */
	protected function getCollectionName(): string {
		return 'sessions';
	}

	/**
	 * @inheritDoc
	 */
	public function setNewSession( string $wikiScriptPath, string $pageTitle, int $pageNamespace, int $ownerId ) {
		$sessionId = (int)rand();

		$this->collection->insertOne( [
			's_id' => $sessionId,
			's_token' => (float)rand() / (float)getrandmax() / 10,
			's_wiki_script_path' => $wikiScriptPath,
			's_page_title' => $pageTitle,
			's_page_namespace' => $pageNamespace,
			's_owner' => [
				// foreign key to authorsCollectionCollection.a_author_id
				'authorId' => $ownerId,
			],
			's_authors' => [
				// first author is ALWAYS owner, s_owner can not be droped from authors list
				// Yes, i know that it is hack
				null
			],
			's_active_connections' => [],
			's_history' => [],
			's_stores' => [],
			's_selections' => [],
		] );

		return $sessionId;
	}

	/**
	 * @inheritDoc
	 */
	public function setNewAuthorInSession(
		int $sessionId, int $authorId, string $authorName, string $authorRealName,
		string $authorColor, bool $authorStatus, int $connectionId
	) {
		$this->collection->updateOne(
			[ 's_id' => $sessionId ],
			[ '$push' => [
				's_authors' => [
					'authorId' => $authorId,
					'name' => $authorName,
					'realName' => $authorRealName,
					'color' => $authorColor,
					'active' => $authorStatus,
					'connection' => [ $connectionId ]
				]
			]
			]
		);

		if ( !$authorStatus ) {
			$this->deactivateAuthor( $sessionId, false, $authorId );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function deleteSession( int $sessionId ) {
		$this->collection->deleteOne(
			[ 's_id' => $sessionId ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function isAuthorInSession( int $sessionId, int $authorId ): bool {
		$result = $this->collection->find(
			[ 's_id' => $sessionId, 's_authors.authorId' => $authorId ],
			[ 'projection' => [ 's_authors' => 1 ] ]
		);

		foreach ( $result as $row ) {
			return true;
		}

		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function changeAuthorDataInSession(
		int $sessionId, int $authorId, string $authorData, mixed $authorValue
	) {
		$this->collection->updateOne(
			[ 's_id' => $sessionId, 's_authors.authorId' => $authorId ],
			[ '$set' => [ 's_authors.$.' . $authorData => $authorValue ] ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function activateAuthor( int $sessionId, int $authorId, int $connectionId ) {
		$this->collection->updateOne(
			[ 's_id' => $sessionId, 's_authors.authorId' => $authorId ],
			[
				'$push' => [ 's_authors.$.connection' => $connectionId ],
				'$set' => [ 's_authors.$.active' => true ]
			]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function deactivateAuthor( int $sessionId, bool $authorActive, int $authorId ) {
		$this->collection->updateOne(
			[ 's_id' => $sessionId, 's_authors.authorId' => $authorId ],
			[
				'$set' => [
					's_authors.$.active' => $authorActive,
				],
				'$pop' => [
					's_authors.$.connection' => 1
				],
			]
		);
		$this->clearAuthorRebaseData( $sessionId, $authorId );
	}

	/**
	 * @param int $sessionId
	 * @param Change $change
	 * @return void
	 */
	public function replaceHistory( int $sessionId, Change $change ) {
		$data = json_decode( json_encode( $change ), true );

		$this->collection->updateOne(
			[ 's_id' => $sessionId ],
			[ '$set' => [
				's_history' => $data['transactions'],
				's_stores' => $data['stores'],
				's_selections' => $data['selections']
			] ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getAllAuthorsFromSession( int $sessionId ) {
		$result = $this->collection->find(
			[ 's_id' => $sessionId ],
			[ 'projection' => [ 's_authors' => 1 ] ]
		);

		$output = [];
		foreach ( $result as $row ) {
			foreach ( $row['s_authors'] as $key => $author ) {
				if ( $author && ( $author['active'] === true ) ) {
					$output[] = [
						'id' => $row['s_authors'][$key]['authorId'],
						'value' => $row['s_authors'][$key]
					];
				}
			}
		}

		return $output;
	}

	/**
	 * @inheritDoc
	 */
	public function getAuthorInSession( int $sessionId, int $authorId ) {
		$result = $this->collection->find(
			[ 's_id' => $sessionId, 's_authors.authorId' => $authorId ],
			[ 'projection' => [ 's_authors' => 1 ] ]
		);

		foreach ( $result as $row ) {
			foreach ( $row['s_authors'] as $key => $author ) {
				if ( $author && ( $author['authorId'] === $authorId ) ) {
					return [
						'id' => $row['s_authors'][$key]['authorId'],
						'value' => $row['s_authors'][$key]
					];
				}
			}
		}
		return [];
	}

	/**
	 * @param int $sessionId
	 * @param Author $author
	 * @return Change|null
	 */
	public function getAuthorContinueBase( int $sessionId, Author $author ): ?Change {
		$author = $this->getAuthorInSession( $sessionId, $author->getId() );
		if ( !$author ) {
			return null;
		}
		$cb = $author['value']['continueBase'] ?? null;
		if ( !$cb ) {
			return null;
		}
		$cb = json_decode( json_encode( $cb ), true );
		$stores = $cb['stores'] ?? [];
		if ( is_array( $stores ) && isset( $stores['hashes'] ) ) {
			// To be removed
			$stores = [ $stores ];
		}
		return new Change( $cb['start'], $cb['transactions'], $cb['selections'] ?? [], $stores );
	}

	/**
	 * @param int $sessionId
	 * @param int $authorId
	 * @return void
	 */
	public function clearAuthorRebaseData( int $sessionId, int $authorId ) {
		// Clear continue base and rejections
		$this->collection->updateOne(
			[ 's_id' => $sessionId, 's_authors.authorId' => $authorId ],
			[
				'$unset' => [
					's_authors.$.continueBase' => '',
					's_authors.$.rejections' => ''
				]
			]
		);
	}

	/**
	 * @param int $sessionId
	 * @param Author $author
	 * @return int
	 */
	public function getAuthorRejections( int $sessionId, Author $author ): int {
		$author = $this->getAuthorInSession( $sessionId, $author->getId() );
		if ( !$author ) {
			return 0;
		}
		if ( isset( $author['value']['rejections'] ) ) {
			return (int)$author['value']['rejections'];
		}
		return 0;
	}

	/**
	 * @inheritDoc
	 */
	public function getFullHistoryFromSession( int $sessionId ) {
		$result = $this->collection->find(
			[ 's_id' => $sessionId ],
			[ 'projection' => [ 's_history' => 1 ] ]
		);

		foreach ( $result as $row ) {
			return json_decode( json_encode( $row['s_history'] ), true );
		}

		return [];
	}

	/**
	 * @param int $sessionId
	 * @return array
	 */
	public function getFullSelectionsFromSession( int $sessionId ): array {
		$result = $this->collection->find(
			[ 's_id' => $sessionId ],
			[ 'projection' => [ 's_selections' => 1 ] ]
		);

		foreach ( $result as $row ) {
			if ( !isset( $row['s_selections'] ) ) {
				return [];
			}
			return json_decode( json_encode( $row['s_selections'] ), true );
		}
		return [];
	}

	/**
	 * @param int $sessionId
	 * @return Change
	 */
	public function getChange( int $sessionId ): Change {
		// TODO: Combine
		$transactions = $this->getFullHistoryFromSession( $sessionId );
		$stores = $this->getFullStoresFromSession( $sessionId );
		// Maybe we should store selections
		$selections = [];
		return new Change( 0, $transactions, $selections, $stores );
	}

	/**
	 * @inheritDoc
	 */
	public function getFullStoresFromSession( int $sessionId ) {
		$result = $this->collection->find(
			[ 's_id' => $sessionId ],
			[ 'projection' => [ 's_stores' => 1 ] ]
		);

		foreach ( $result as $row ) {
			// Convert to array
			return json_decode( json_encode( $row['s_stores'] ), true );
		}
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function getSessionOwner( int $sessionId ) {
		$result = $this->collection->find(
			[ 's_id' => $sessionId ],
			[ 'projection' => [ 's_owner' => 1 ] ]
		);
		foreach ( $result as $row ) {
			return $row['s_owner']['authorId'];
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getActiveConnections( int $sessionId ) {
		$result = $this->collection->find(
			[ 's_id' => $sessionId, 's_authors.active' => true ],
			[ 'projection' => [ 's_authors.connection' => 1 ] ]
		);

		$output = [];
		foreach ( $result as $row ) {
			foreach ( $row['s_authors'] as $author ) {
				$output = array_merge( $output, json_decode( json_encode( $author['connection'] ) ) );
			}
			return $output;
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getSessionByName( string $wikiScriptPath, string $pageTitle, int $pageNamespace ) {
		$result = $this->collection->find(
			[
				's_wiki_script_path' => $wikiScriptPath,
				's_page_title' => $pageTitle,
				's_page_namespace' => $pageNamespace
			],
			[ 'projection' => [ 's_id' => 1, 's_token' => 1, 's_page_title' => 1, 's_page_namespace' => 1 ] ]
		);

		$response = [];
		foreach ( $result as $row ) {
			$response['s_id'] = json_encode( $row['s_id'] );
			$response['s_token'] = json_encode( $row['s_token'] );
			$response['s_page_title'] = $row['s_page_title'];
			$response['s_page_namespace'] = $row['s_page_namespace'];

			if ( isset( $row['s_wiki_script_path'] ) ) {
				$response['s_wiki_script_path'] = $row['s_wiki_script_path'];
			} else {
				// Backward compatibility
				$response['s_wiki_script_path'] = '';
			}
		}

		return $response;
	}

	/**
	 * @inheritDoc
	 */
	public function cleanConnections() {
		$result = $this->collection->find(
			[],
			[
				'projection' => [
					's_authors' => 1,
					's_id' => 1
				]
			]
		);

		$output = [];
		foreach ( $result as $key => $row ) {
			$output[$key]['authors'] = json_decode( json_encode( $row['s_authors'] ) );
			$output[$key]['s_id'] = $row['s_id'];
		}

		foreach ( $output as $session ) {
			for ( $i = 1; $i < count( $session['authors'] ); $i++ ) {
				$session['authors'][$i]->active = false;
				$session['authors'][$i]->connection = [];
				$this->collection->updateOne(
					[ 's_id' => $session['s_id'] ],
					[ '$set' => [ 's_authors' => $session['authors'] ] ]
				);
			}
		}
	}
}
