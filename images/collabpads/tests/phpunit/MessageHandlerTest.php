<?php

namespace MediaWiki\Extension\CollabPads\Backend\Tests;

use MediaWiki\Extension\CollabPads\Backend\ConnectionList;
use MediaWiki\Extension\CollabPads\Backend\DAO\MongoDBAuthorDAO;
use MediaWiki\Extension\CollabPads\Backend\DAO\MongoDBCollabSessionDAO;
use MediaWiki\Extension\CollabPads\Backend\Handler\MessageHandler;
use MediaWiki\Extension\CollabPads\Backend\IAuthorDAO;
use MediaWiki\Extension\CollabPads\Backend\ICollabSessionDAO;
use MediaWiki\Extension\CollabPads\Backend\Model\Author;
use MediaWiki\Extension\CollabPads\Backend\Model\Change;
use MediaWiki\Extension\CollabPads\Backend\Rebaser;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface;

// We have to do that, MediaWiki autoload won't work because of "backend" dir
require_once dirname( __DIR__ ) . '../../vendor/autoload.php';

/**
 * @covers \MediaWiki\Extension\CollabPads\Backend\Handler\MessageHandler
 */
class MessageHandlerTest extends TestCase {

	/**
	 * @var ConnectionInterface
	 */
	private $initiatorConnectionMock;

	/**
	 * @var IAuthorDAO
	 */
	private $authorDAOMock;

	/**
	 * @var ICollabSessionDAO
	 */
	private $sessionDAOMock;

	/**
	 * @var LoggerInterface
	 */
	private $loggerMock;

	/**
	 * Checks that when one of the authors disconnects:
	 * * author is marked as inactive in the DB
	 * * author connection to that specific session is deleted from DB
	 * * all other authors are notified about that (by getting specific message from the server)
	 *
	 * @covers \MediaWiki\Extension\CollabPads\Backend\Handler\MessageHandler::handle
	 * @covers \MediaWiki\Extension\CollabPads\Backend\Handler\MessageHandler::authorDisconnect
	 * @dataProvider provideAuthorDisconnectData
	 */
	public function testAuthorDisconnect(
		array $authorConnections, array $initiatorConnection, int $sessionId, string $messageFromClient,
		string $expectedResponseMessage, array $expectedRecipientConnections
	) {
		// Fill connection list with current connections
		// Also make sure that expected recipients will receive expected response from the server
		$connectionList = $this->makeConnectionList(
			$authorConnections, $expectedResponseMessage, $expectedRecipientConnections
		);

		$this->initTest( $authorConnections, $initiatorConnection, $sessionId );

		$this->sessionDAOMock->method( 'getAuthorInSession' )->willReturn( [
			'id' => $initiatorConnection['authorId']
		] );

		// Make sure that author is deactivated
		$this->sessionDAOMock->expects( $this->once() )->method( 'deactivateAuthor' )
			->with( $sessionId, false, $initiatorConnection['authorId'] );

		// Make sure that author connection with session is deleted from DB
		$this->authorDAOMock->expects( $this->once() )->method( 'deleteConnection' )
			->with( $initiatorConnection['connectionId'], $initiatorConnection['authorId'] );

		$rebaserMock = $this->createMock( Rebaser::class );
		$messageHandler = new MessageHandler(
			$this->authorDAOMock, $this->sessionDAOMock, $this->loggerMock, $rebaserMock
		);
		$messageHandler->handle( $this->initiatorConnectionMock, $messageFromClient, $connectionList );
	}

	/**
	 * @return array
	 */
	public function provideAuthorDisconnectData(): array {
		return [
			// We have 3 authors in collaborative session, one of them disconnects
			'Regular case' => [
				// Author connections
				[
					[
						'connectionId' => 100,
						'authorId' => 1
					],
					[
						'connectionId' => 110,
						'authorId' => 2
					],
					[
						'connectionId' => 120,
						'authorId' => 3
					]
				],
				// Data about "initiator" connection - connection which triggered that event
				// "Author ID" is needed for further mocking
				[
					'connectionId' => 100,
					'authorId' => 1
				],
				// Session ID
				128888,
				// Message received from the client
				// In case with "authorDisconnect" event we do not actually need anything more than event code
				'41',
				// Message client should get in response
				'42["authorDisconnect",1]',
				// Client connections which should eventually receive response from the server
				[
					110,
					120
				]
			]
		];
	}

	/**
	 * Checks that when one of the authors saves changes:
	 * * all other authors are notified about that (by getting specific message from the server)
	 *
	 * @covers \MediaWiki\Extension\CollabPads\Backend\Handler\MessageHandler::handle
	 * @covers \MediaWiki\Extension\CollabPads\Backend\Handler\MessageHandler::saveRevision
	 * @dataProvider provideSaveRevisionData
	 */
	public function testSaveRevision(
		array $authorConnections, array $initiatorConnection, int $sessionId, string $messageFromClient,
		string $expectedResponseMessage, array $expectedRecipientConnections
	) {
		// Fill connection list with current connections
		// Also make sure that expected recipients will receive expected response from the server
		$connectionList = $this->makeConnectionList(
			$authorConnections, $expectedResponseMessage, $expectedRecipientConnections
		);

		$this->initTest( $authorConnections, $initiatorConnection, $sessionId );

		$rebaserMock = $this->createMock( Rebaser::class );
		$messageHandler = new MessageHandler(
			$this->authorDAOMock, $this->sessionDAOMock, $this->loggerMock, $rebaserMock
		);
		$messageHandler->handle( $this->initiatorConnectionMock, $messageFromClient, $connectionList );
	}

	/**
	 * @return array
	 */
	public function provideSaveRevisionData(): array {
		return [
			// We have 3 authors in collaborative session, one of them saves changes
			'Regular case' => [
				// Author connections
				[
					[
						'connectionId' => 100,
						'authorId' => 1
					],
					[
						'connectionId' => 110,
						'authorId' => 2
					],
					[
						'connectionId' => 120,
						'authorId' => 3
					]
				],
				// Data about "initiator" connection - connection which triggered that event
				// "Author ID" is needed for further mocking
				[
					'connectionId' => 100,
					'authorId' => 1
				],
				// Session ID
				128888,
				// Message received from the client
				'42["saveRevision",{"authorId":1}]',
				// Message client should get in response
				'42["saveRevision",1]',
				// Client connections which should eventually receive response from the server
				[
					110,
					120
				]
			]
		];
	}

	/**
	 * Checks that when one of the authors gets his data updated (most likely when he connects to the session)
	 * * author data is updated in the DB
	 * * all other authors get actual data about this one (by getting specific message from the server)
	 *
	 * "authorChange" event also may be triggered when author himself changes some data (for example, color)
	 *
	 * @covers \MediaWiki\Extension\CollabPads\Backend\Handler\MessageHandler::handle
	 * @covers \MediaWiki\Extension\CollabPads\Backend\Handler\MessageHandler::authorChange
	 * @dataProvider provideAuthorChangeData
	 */
	public function testAuthorChange(
		array $authorConnections, array $initiatorConnection, int $sessionId,
		string $messageFromClient, array $authorData,
		string $expectedResponseMessage, array $expectedRecipientConnections
	) {
		// Fill connection list with current connections
		// Also make sure that expected recipients will receive expected response from the server
		$connectionList = $this->makeConnectionList(
			$authorConnections, $expectedResponseMessage, $expectedRecipientConnections
		);

		$this->initTest( $authorConnections, $initiatorConnection, $sessionId );

		$this->sessionDAOMock->method( 'getAuthorInSession' )->willReturn( [
			'id' => $initiatorConnection['authorId'],
			'value' => [
				'name' => $authorData['name'],
				'realName' => '',
				'color' => $authorData['color']
			]
		] );

		// Make sure that author data in session is updated
		$this->sessionDAOMock->expects( $this->once() )->method( 'changeAuthorDataInSession' )
			->with( $sessionId, $initiatorConnection['authorId'], 'color', $authorData['color'] );

		$rebaserMock = $this->createMock( Rebaser::class );
		$messageHandler = new MessageHandler(
			$this->authorDAOMock, $this->sessionDAOMock, $this->loggerMock, $rebaserMock
		);
		$messageHandler->handle( $this->initiatorConnectionMock, $messageFromClient, $connectionList );
	}

	/**
	 * @return array
	 */
	public function provideAuthorChangeData(): array {
		return [
			// We have 3 authors in collaborative session, one of them has his data changed
			'Regular case' => [
				// Author connections
				[
					[
						'connectionId' => 100,
						'authorId' => 1
					],
					[
						'connectionId' => 110,
						'authorId' => 2
					],
					[
						'connectionId' => 120,
						'authorId' => 3
					]
				],
				// Data about "initiator" connection - connection which triggered that event
				// "Author ID" is needed for further mocking
				[
					'connectionId' => 110,
					'authorId' => 2
				],
				// Session ID
				128888,
				// Message received from the client
				'42["changeAuthor",{"name":"TestUser1","color":"B96091"}]',
				// Author data to update in DB
				[
					'name' => 'TestUser1',
					'color' => 'B96091'
				],
				// Message client should get in response
				'42["authorChange",{"authorId":2,"authorData":{"name":"TestUser1","realName":"","color":"B96091"}}]',
				// Client connections which should eventually receive response from the server
				[
					100,
					110,
					120
				]
			]
		];
	}

	/**
	 * Checks that when one of the authors does change:
	 * * change is persisted in the DB
	 * * all other authors are notified about that (by getting specific message from the server)
	 *
	 * @covers \MediaWiki\Extension\CollabPads\Backend\Handler\MessageHandler::handle
	 * @covers \MediaWiki\Extension\CollabPads\Backend\Handler\MessageHandler::newChange
	 * @dataProvider provideNewChangeData
	 */
	public function testNewChange(
		array $authorConnections, array $initiatorConnection, int $sessionId,
		string $messageFromClient, string $changeDataRaw,
		string $expectedResponseMessage, array $expectedRecipientConnections
	) {
		// Fill connection list with current connections
		// Also make sure that expected recipients will receive expected response from the server
		$connectionList = $this->makeConnectionList(
			$authorConnections, $expectedResponseMessage, $expectedRecipientConnections
		);

		$this->initTest( $authorConnections, $initiatorConnection, $sessionId );

		$rebaserMock = $this->createMock( Rebaser::class );
		$rebaserMock->method( 'applyChange' )->willReturnCallback(
			static function ( int $sessionId, Author $author, int $backtrack, Change $change ) {
				return $change;
			}
		);
		$messageHandler = new MessageHandler(
			$this->authorDAOMock, $this->sessionDAOMock, $this->loggerMock, $rebaserMock
		);
		$messageHandler->handle( $this->initiatorConnectionMock, $messageFromClient, $connectionList );
	}

	/**
	 * @return array
	 */
	public function provideNewChangeData(): array {
		return [
			// We have 3 authors in collaborative session, one of them makes some change
			'Regular case' => [
				// Author connections
				[
					[
						'connectionId' => 100,
						'authorId' => 1
					],
					[
						'connectionId' => 110,
						'authorId' => 2
					],
					[
						'connectionId' => 120,
						'authorId' => 3
					]
				],
				// Data about "initiator" connection - connection which triggered that event
				// "Author ID" is needed for further mocking
				[
					'connectionId' => 100,
					'authorId' => 1
				],
				// Session ID
				128888,
				// Message received from the client
				// phpcs:ignore Generic.Files.LineLength.TooLong
				'42["submitChange",{"backtrack":0,"change":{"start":5,"transactions":[{"o":[47,["","s"],58],"a":1}],"selections":{"1":{"type":"linear","range":{"type":"range","from":48,"to":48}}}},"stores":[]}]',
				// Change actual data
				// phpcs:ignore Generic.Files.LineLength.TooLong
				'{"start":5,"transactions":[{"o":[47,["","s"],58],"a":1}],"selections":{"1":{"type":"linear","range":{"type":"range","from":48,"to":48}}}}',
				// Message client should get in response
				// phpcs:ignore Generic.Files.LineLength.TooLong
				'42["newChange",{"start":5,"transactions":[{"o":[47,["","s"],58],"a":1}],"selections":{"1":{"type":"linear","range":{"type":"range","from":48,"to":48}}},"stores":[]}]',
				// Client connections which should eventually receive response from the server
				[
					100,
					110,
					120
				]
			]
		];
	}

	/**
	 * Checks that when the last one of the authors leaves the session without changes - session is removed from the DB
	 *
	 * @covers \MediaWiki\Extension\CollabPads\Backend\Handler\MessageHandler::handle
	 * @dataProvider provideSessionCloseData
	 */
	public function testDeleteSession(
		array $authorConnections, array $initiatorConnection, int $sessionId, string $messageFromClient,
		string $expectedResponseMessage, array $expectedRecipientConnections
	) {
		// Fill connection list with current connections
		// Also make sure that expected recipients will receive expected response from the server
		$connectionList = $this->makeConnectionList(
			$authorConnections, $expectedResponseMessage, $expectedRecipientConnections
		);

		$this->initTest( $authorConnections, $initiatorConnection, $sessionId );

		// Make sure that session is deleted in result
		$this->sessionDAOMock->expects( $this->once() )->method( 'deleteSession' )->with( $sessionId );

		$rebaserMock = $this->createMock( Rebaser::class );
		$messageHandler = new MessageHandler(
			$this->authorDAOMock, $this->sessionDAOMock, $this->loggerMock, $rebaserMock
		);
		$messageHandler->handle( $this->initiatorConnectionMock, $messageFromClient, $connectionList );
	}

	/**
	 * @return array
	 */
	public function provideSessionCloseData(): array {
		return [
			// We have 1 author in collaborative session left, and he leaves the session without changes
			// So session gets deleted
			'Regular case' => [
				// Author connections
				[
					[
						'connectionId' => 100,
						'authorId' => 1
					]
				],
				// Data about "initiator" connection - connection which triggered that event
				// "Author ID" is needed for further mocking
				[
					'connectionId' => 100,
					'authorId' => 1
				],
				// Session ID
				128888,
				// Message received from the client
				'42["deleteSession",{"authorId":1}]',
				// No recipients - no message from the server
				'',
				// Session is deleted when the last one leaves, so no recipients
				[]
			]
		];
	}

	/**
	 * Initialise general among all "message handler" tests, such like "initiator connection", logger, DAOs...
	 *
	 * @param array $authorConnections
	 * @param array $initiatorConnection
	 * @param int $sessionId
	 */
	private function initTest( array $authorConnections, array $initiatorConnection, int $sessionId ): void {
		$this->loggerMock = $this->createMock( LoggerInterface::class );

		// Initiator connection, which triggered current event
		$this->initiatorConnectionMock = $this->createMock( ConnectionInterface::class );
		$this->initiatorConnectionMock->resourceId = $initiatorConnection['connectionId'];

		$this->authorDAOMock = $this->createMock( MongoDBAuthorDAO::class );
		$this->authorDAOMock->method( 'getAuthorByConnection' )->willReturn(
			new Author( $initiatorConnection['authorId'], '' )
		);
		$this->authorDAOMock->method( 'getSessionByConnection' )->willReturn( $sessionId );

		$this->sessionDAOMock = $this->createMock( MongoDBCollabSessionDAO::class );
		$this->sessionDAOMock->method( 'getActiveConnections' )->willReturn(
			array_column( $authorConnections, 'connectionId' )
		);
	}

	/**
	 * Fill connection list with authors connections.
	 * Also make sure that expected recipients will receive expected response from the server.
	 *
	 * @param array $connectionsData
	 * @param string $expectedResponseMessage
	 * @param array $expectedRecipientConnections
	 * @return ConnectionList
	 */
	private function makeConnectionList(
		array $connectionsData, string $expectedResponseMessage, array $expectedRecipientConnections
	): ConnectionList {
		$connectionList = new ConnectionList();

		foreach ( $connectionsData as $connectionData ) {
			$connectionMock = $this->createMock( ConnectionInterface::class );
			$connectionMock->resourceId = $connectionData['connectionId'];

			// If that is connection of one of recipients (not initiator connection),
			// then we should ensure that this client will get expected response from the server
			if ( in_array( $connectionData['connectionId'], $expectedRecipientConnections ) ) {
				$connectionMock->expects( $this->once() )->method( 'send' )->with( $expectedResponseMessage );
			}

			$connectionList->add( $connectionMock, $connectionData['authorId'] );
		}

		return $connectionList;
	}
}
