<?php

namespace MediaWiki\Extension\CollabPads\Backend;

$config = require_once dirname( __DIR__ ) . '/config.php';

use Exception;
use GuzzleHttp\Client;
use MediaWiki\Extension\CollabPads\Backend\Handler\MessageHandler;
use MediaWiki\Extension\CollabPads\Backend\Handler\OpenHandler;
use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class Socket implements MessageComponentInterface {

	/**
	 * @var OpenHandler
	 */
	private $openHandler;

	/**
	 * @var MessageHandler
	 */
	private $messageHandler;

	/**
	 * @var IAuthorDAO
	 */
	private $authorDAO;

	/**
	 * @var ICollabSessionDAO
	 */
	private $sessionDAO;

	/**
	 * @var ConnectionList
	 */
	private $connectionList;

	/**
	 * @var LoggerInterface
	 */
	private $logger = null;

	/**
	 * @param array $config
	 * @param Client $httpClient
	 * @param IAuthorDAO $authorDAO
	 * @param ICollabSessionDAO $sessionDAO
	 * @param LoggerInterface $logger
	 * @param Rebaser $rebaser
	 */
	public function __construct(
		$config, $httpClient, IAuthorDAO $authorDAO,
		ICollabSessionDAO $sessionDAO, LoggerInterface $logger, Rebaser $rebaser
	) {
		$this->logger = $logger;

		$this->authorDAO = $authorDAO;
		$this->authorDAO->cleanConnections();
		$this->sessionDAO = $sessionDAO;
		$this->sessionDAO->cleanConnections();

		$this->openHandler = new OpenHandler( $authorDAO, $sessionDAO, $config, $httpClient, $logger );
		$this->messageHandler = new MessageHandler( $authorDAO, $sessionDAO, $logger, $rebaser, $config );

		$this->connectionList = new ConnectionList();
	}

	/**
	 * @param ConnectionInterface $conn
	 * @return void
	 */
	public function onOpen( ConnectionInterface $conn ) {
		$this->openHandler->handle( $conn, $this->connectionList );
	}

	/**
	 * Processes the incoming request and creates a response for it
	 *
	 * There are three types of requests:
	 * - EventType::IS_ALIVE -> request to prove is connection still established
	 * - EventType::CONNECTION_REFUSED -> request to close connection between server and user
	 * - EventType::CONTENT -> request with current made changes
	 *
	 * HACK: because of EventType::IS_ALIVE & CONNECTION_REFUSED don't have own event body
	 * the full request will look like an <eventId> (42)
	 *
	 * @param ConnectionInterface $from
	 * @param string $msg
	 */
	public function onMessage( ConnectionInterface $from, $msg ) {
		$this->messageHandler->handle( $from, $msg, $this->connectionList );
	}

	/**
	 * Called when connection is closed by user
	 * Unsets the affected connection from list of all connections
	 *
	 * @param ConnectionInterface $conn
	 */
	public function onClose( ConnectionInterface $conn ) {
		if ( $this->authorDAO->getAuthorByConnection( $conn->resourceId ) ) {
			// emit the EventType::CONNECTION_REFUSED
			$this->onMessage( $conn, "41" );
		}
		$this->connectionList->remove( $conn->resourceId );
	}

	/**
	 * In case of some error during the execution
	 * Error will be printed in logs; connection will be closed
	 *
	 * @param ConnectionInterface $conn
	 * @param Exception $e
	 */
	public function onError( ConnectionInterface $conn, Exception $e ) {
		$this->logger->error( "Error in connection (ID:{$conn->resourceId}); Stacktrace; {$e})" );
		$conn->close();
	}
}
