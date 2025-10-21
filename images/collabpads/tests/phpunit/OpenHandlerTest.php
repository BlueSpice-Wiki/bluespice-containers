<?php

namespace MediaWiki\Extension\CollabPads\Backend\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use MediaWiki\Extension\CollabPads\Backend\ConnectionList;
use MediaWiki\Extension\CollabPads\Backend\DAO\MongoDBAuthorDAO;
use MediaWiki\Extension\CollabPads\Backend\DAO\MongoDBCollabSessionDAO;
use MediaWiki\Extension\CollabPads\Backend\Handler\OpenHandler;
use MediaWiki\Extension\CollabPads\Backend\Model\Author;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface;

// We have to do that, MediaWiki autoload won't work because of "backend" dir
require_once dirname( __DIR__ ) . '../../vendor/autoload.php';

/**
 * @covers \MediaWiki\Extension\CollabPads\Backend\Handler\OpenHandler
 */
class OpenHandlerTest extends TestCase {

	/**
	 * @var array
	 */
	private $serverConfigs = [
		'server-id' => 'mediawiki-collabpads-backend',
		'ping-interval' => 25000,
		'ping-timeout' => 5000,
		'baseurl' => 'http://host.docker.internal/REL1_39__2'
	];

	/**
	 * @var int
	 */
	private $connectionId = 128;

	/**
	 * @var int
	 */
	private $sessionId = 10048;

	/**
	 * @var string
	 */
	private $sessionToken = 'some-session-token';

	/**
	 * @var int
	 */
	private $authorId = 1;

	/**
	 * @var string
	 */
	private $userName = 'TestUser';

	/**
	 * @var string
	 */
	private $scriptPath = '/REL1_39__2';

	/**
	 * @var string
	 */
	private $pageTitle = 'SomeNs:Page_for_CollabPads';

	/**
	 * @var string
	 */
	private $accessToken = 'some-access-token';

	/**
	 * In case of successful execution:
	 * * Server sends "settings string" to the client
	 * * Server sends "connection established" message to the client
	 * * Server sends to the client message about successful registration ("registered" message)
	 * * Server sends to the client message about successful document initialization ("initDoc" message)
	 * * Connection object is added to "connection list"
	 *
	 * @covers \MediaWiki\Extension\CollabPads\Backend\Handler\OpenHandler::handle
	 */
	public function testSuccess() {
		$authorDAOMock = $this->createMock( MongoDBAuthorDAO::class );
		$authorDAOMock->method( 'getAuthorByName' )->willReturn(
			new Author( $this->authorId, $this->userName )
		);

		$sessionDAOMock = $this->createMock( MongoDBCollabSessionDAO::class );
		$sessionDAOMock->method( 'getSessionByName' )->willReturn( [
			's_token' => $this->sessionToken,
			's_id' => $this->sessionId,
			's_page_title' => $this->pageTitle,
			's_page_namespace' => NS_MAIN,
		] );
		$sessionDAOMock->method( 'getAuthorInSession' )->willReturn( [
			'id' => $this->authorId,
			'value' => [
				// It is used after user registration to prove that author has connection with session
				// Otherwise we'll get "alreadyLoggedIn" message
				'connection' => [ $this->connectionId ]
			]
		] );
		$sessionDAOMock->method( 'isAuthorInSession' )->willReturn( false );
		$sessionDAOMock->method( 'getAllAuthorsFromSession' )->willReturn( [
			[
				'id' => $this->authorId,
				'value' => [
					'color' => 'some-color',
					'name' => $this->userName
				]
			]
		] );
		// To simplify test just empty arrays here for now
		$sessionDAOMock->method( 'getFullHistoryFromSession' )->willReturn( [] );
		$sessionDAOMock->method( 'getFullStoresFromSession' )->willReturn( [] );

		$aclAnswer = json_encode( [
			'access' => true,
			'user' => [
				'userName' => $this->userName
			],
			'pageTitle' => $this->pageTitle,
			'pageNamespace' => NS_MAIN,
			'message' => 'Access granted!'
		] );

		$aclAnswerStreamMock = $this->createMock( StreamInterface::class );
		$aclAnswerStreamMock->method( '__toString' )->willReturn( $aclAnswer );

		$httpResponseMock = $this->createMock( Response::class );
		$httpResponseMock->method( 'getStatusCode' )->willReturn( 200 );
		$httpResponseMock->method( 'getBody' )->willReturn( $aclAnswerStreamMock );

		$httpClientMock = $this->createMock( Client::class );
		$httpClientMock->method( 'get' )->willReturn( $httpResponseMock );

		$loggerMock = $this->createMock( LoggerInterface::class );

		$query = 'docName=' .
			urlencode( $this->scriptPath . '|' . $this->accessToken . '|' . $this->pageTitle );

		$uriMock = $this->createMock( Uri::class );
		$uriMock->method( 'getQuery' )->willReturn( $query );

		$httpRequestMock = $this->createMock( Request::class );
		$httpRequestMock->method( 'getUri' )->willReturn( $uriMock );

		$connectionMock = $this->createMock( ConnectionInterface::class );
		$connectionMock->httpRequest = $httpRequestMock;
		$connectionMock->resourceId = $this->connectionId;

		$connectionListMock = $this->createMock( ConnectionList::class );

		$settingsResponse = [
			// 'sid' => "9mbmwV5MzVClKxBSpAAI",
			'upgrades' => [],
			'pingInterval' => $this->serverConfigs['ping-interval'],
			'pingTimeout' => $this->serverConfigs['ping-timeout']
		];

		$registeredResponse = [
			'serverId' => $this->serverConfigs['server-id'],
			'authorId' => $this->authorId,
			'token' => $this->sessionToken,
		];

		$initDocResponse = [
			'history' => [
				'start' => 0,
				'transactions' => [],
				'stores' => [],
				'selections' => []
			],
			'authors' => [
				$this->authorId => [
					'name' => $this->userName,
					'realName' => '',
					'color' => 'some-color'
				]
			]
		];

		// Make sure that author connection for this collaborative session will be added to the DB
		$authorDAOMock->expects( $this->once() )
			->method( 'setNewConnection' )->with( $this->connectionId, $this->sessionId, $this->authorId );

		// Make sure that all necessary messages are sent from the server to the client
		$connectionMock->expects( $this->exactly( 4 ) )
			->method( 'send' )->withConsecutive(
				[ '0' . json_encode( $settingsResponse ) ],
				[ '40' ],
				[ '42["registered",' . json_encode( $registeredResponse ) . ']' ],
				[ '42["initDoc",' . json_encode( $initDocResponse ) . ']' ]
			);

		// Make sure that connection is added to the connection list
		$connectionListMock->expects( $this->once() )->method( 'add' )->with( $connectionMock, $this->authorId );

		$openHandler = new OpenHandler(
			$authorDAOMock, $sessionDAOMock, $this->serverConfigs, $httpClientMock, $loggerMock
		);
		$openHandler->handle( $connectionMock, $connectionListMock );
	}
}
