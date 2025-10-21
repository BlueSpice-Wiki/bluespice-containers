<?php

namespace MediaWiki\Extension\CollabPads\Backend\Handler;

use Exception;
use MediaWiki\Extension\CollabPads\Backend\ConnectionList;
use MediaWiki\Extension\CollabPads\Backend\EventType;
use MediaWiki\Extension\CollabPads\Backend\IAuthorDAO;
use MediaWiki\Extension\CollabPads\Backend\ICollabSessionDAO;
use MediaWiki\Extension\CollabPads\Backend\Model\Change;
use MediaWiki\Extension\CollabPads\Backend\Rebaser;
use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface;
use Throwable;

class MessageHandler {
	use BackendHandlerTrait;

	/**
	 * @var IAuthorDAO
	 */
	private $authorDAO;

	/**
	 * @var ICollabSessionDAO
	 */
	private $sessionDAO;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/** @var Rebaser */
	private Rebaser $rebaser;

	/** @var array */
	private array $config;

	/**
	 * @param IAuthorDAO $authorDAO
	 * @param ICollabSessionDAO $sessionDAO
	 * @param LoggerInterface $logger
	 * @param Rebaser $rebaser
	 * @param array $config
	 */
	public function __construct(
		IAuthorDAO $authorDAO, ICollabSessionDAO $sessionDAO, LoggerInterface $logger, Rebaser $rebaser, array $config
	) {
		$this->authorDAO = $authorDAO;
		$this->sessionDAO = $sessionDAO;
		$this->logger = $logger;
		$this->rebaser = $rebaser;
		$this->config = $config;
	}

	/**
	 * Handles incoming messages, processes events, and routes them to appropriate actions.
	 *
	 * @param ConnectionInterface $from Source connection of the incoming message
	 * @param string $msg Raw message received
	 * @param ConnectionList $connectionList List of connections for message distribution
	 * @return void
	 * @throws Exception
	 */
	public function handle( ConnectionInterface $from, $msg, ConnectionList $connectionList ): void {
		$relevantConnections = $notRelevantConnections = [];

		$this->logger->debug( "Received raw message: $msg" );
		// Parse incoming message to extract eventID, eventName, and optional eventData
		preg_match( '/(?<eventId>\w+)(\[\"(?<eventName>\w+)\"(?:\,(?<eventData>[\s\S]+))?\])?/', $msg, $msgArgs );
		// Add additional connection and author details
		$msgArgs['connectionId'] = $from->resourceId;
		$author = $this->authorDAO->getAuthorByConnection( $from->resourceId );
		if ( !$author ) {
			$this->logger->error( "Author not found for connection ID: {$msgArgs['connectionId']}" );
			return;
		}
		$msgArgs['authorId'] = $author->getId();
		$msgArgs['sessionId'] = $this->authorDAO->getSessionByConnection( $from->resourceId );

		$message = null;
		switch ( $msgArgs['eventId'] ) {
			case EventType::IS_ALIVE:
				$this->logger->debug( "Received keep-alive message from {$msgArgs['connectionId']}" );
				$message = EventType::KEEP_ALIVE;
				$relevantConnections[] = $msgArgs['connectionId'];
				break;
			case EventType::CONNECTION_REFUSED:
				$message = $this->authorDisconnect( $msgArgs );

				$notRelevantConnections[] = $msgArgs['connectionId'];

				$this->logger->info(
					"Session (ID:{$msgArgs['sessionId']}) "
					. "author (ID:{$msgArgs['authorId']}) disconnected" );
				break;
			case EventType::CONTENT:
				switch ( $msgArgs['eventName'] ) {
					case 'changeAuthor':
						$message = $this->authorChange( $msgArgs );

						$this->logger->info(
							"Session (ID:{$msgArgs['sessionId']}) "
							. "author data (ID:{$msgArgs['authorId']}) changed"
						);
						break;
					case 'submitChange':
						try {
							$eventData = $this->parseEventData( $msgArgs );
							if ( !$eventData ) {
								throw new Exception( 'Error parsing eventData: ' . json_encode( $msgArgs ) );
							}
							$change = $this->createChange( $eventData );
							if ( !$change ) {
								throw new Exception( 'Error creating change: ' . json_encode( $eventData ) );
							}
							$change = $this->rebaser->applyChange(
								$msgArgs['sessionId'], $author, $eventData['backtrack'] ?? 0, $change
							);
						} catch ( Throwable $e ) {
							// Original implementation did not catch exceptions, it would only not emit a message
							// if rebasing cannot be done in expected way. Any unexpected errors would be thrown
							$this->logger->error( "Error processing change: " . $e->getMessage(), [
								'backtrace' => $e->getTraceAsString(),
								'line' => $e->getLine(),
							] );

							return;
						}

						if ( !$change->isEmpty() ) {
							$message = $this->newChange( $msgArgs['sessionId'], $change );
						} else {
							$this->logger->error( "Change is empty, skipping" );
						}
						break;
					case 'deleteSession':
						$message = $this->deleteSession( $msgArgs['authorId'] );
						$relevantConnections = $this->sessionDAO->getActiveConnections( $msgArgs['sessionId'] );
						$this->sessionDAO->deleteSession( $msgArgs['sessionId'] );

						$this->logger->info(
							"Session (ID:{$msgArgs['sessionId']}) deleted " .
							"by author (ID:{$msgArgs['authorId']})"
						);
						break;
					case 'saveRevision':
						$message = $this->saveRevision( $msgArgs['authorId'] );

						$notRelevantConnections[] = $msgArgs['connectionId'];

						$this->logger->info(
							"Session (ID:{$msgArgs['sessionId']}) " .
							"author (ID:{$msgArgs['authorId']}) saved revision"
						);
						break;
					case 'logEvent':
						// logevents from users will not be processed
						return;
					default:
						$this->logger->error( "Unknown event name: {$msgArgs['eventName']}" );
						return;
				}
				break;
			default:
				$this->logger->error( "Unknown event type: {$msgArgs['eventId']}" );
				return;
		}

		if ( !$message ) {
			return;
		}

		// Send the response
		$this->sendMessage(
			$connectionList,
			$message,
			$msgArgs['sessionId'],
			$relevantConnections,
			$notRelevantConnections
		);
	}

	/**
	 * @param array $msgArgs
	 * @return string
	 */
	private function authorDisconnect( array $msgArgs ): string {
		$author = $this->sessionDAO->getAuthorInSession( $msgArgs['sessionId'], $msgArgs['authorId'] );

		if ( isset( $author['value']['connection'] ) ) {
			$authorActive = count( $author['value']['connection'] ) !== 1;
		} else {
			$authorActive = false;
		}

		if ( $author ) {
			$this->sessionDAO->deactivateAuthor( $msgArgs['sessionId'], $authorActive, $msgArgs['authorId'] );
			$this->authorDAO->deleteConnection( $msgArgs['connectionId'], $msgArgs['authorId'] );
			$this->sessionDAO->clearAuthorRebaseData( $msgArgs['sessionId'], $msgArgs['authorId'] );
			return $authorActive ? "" : $this->response( EventType::CONTENT, 'authorDisconnect', $author[ 'id' ] );
		}

		return "";
	}

	/**
	 * @param int $authorId
	 * @return string
	 */
	private function saveRevision( int $authorId ): string {
		$this->logger->info( "Author (ID:$authorId) saved revision" );
		return $this->response( EventType::CONTENT, 'saveRevision', $authorId );
	}

	/**
	 * @param int $authorId
	 * @return string
	 */
	private function deleteSession( int $authorId ): string {
		return $this->response( EventType::CONTENT, 'deleteSession', $authorId );
	}

	/**
	 * @param array $msgArgs
	 * @return string
	 */
	private function authorChange( array $msgArgs ): string {
		$eventData = json_decode( $msgArgs['eventData'], true );

		foreach ( $eventData as $key => $value ) {
			if ( $key === "name" ) {
				continue;
			}

			$this->sessionDAO->changeAuthorDataInSession( $msgArgs['sessionId'], $msgArgs['authorId'], $key, $value );
		}

		$this->sessionDAO->clearAuthorRebaseData( $msgArgs['sessionId'], $msgArgs['authorId'] );
		$author = $this->sessionDAO->getAuthorInSession( $msgArgs['sessionId'], $msgArgs['authorId'] );
		$realName = ( isset( $author['value']['realName'] ) ) ? $author['value']['realName'] : '';

		$response = [
			"authorId" => $author['id'],
			"authorData" => [
				"name" => $author['value']['name'],
				"realName" => $realName,
				"color" => $author['value']['color']
			],
		];

		return $this->response( EventType::CONTENT, 'authorChange', json_encode( $response, JSON_UNESCAPED_SLASHES ) );
	}

	/**
	 * Fixes broken surrogate pairs in JSON strings
	 * Prevents 'Single unpaired UTF-16 surrogate in unicode escape'
	 *
	 * @param string $json
	 * @return string|null
	 */
	private function fixSurrogatePairs( string $json ): ?string {
		// Find broken surrogate pairs
		$pattern = '/"\\\\u(d[89ab][0-9a-f]{2})","\\\\u(d[c-f][0-9a-f]{2})"/i';
		// Replace with the merged surrogate pair
		return preg_replace_callback( $pattern, static function ( $matches ) {
			return '"",' . '"\\u' . $matches[1] . '\\u' . $matches[2] . '"';
		}, $json );
	}

	/**
	 * @param int $sessionId
	 * @param Change $change
	 * @return string
	 */
	private function newChange( int $sessionId, Change $change ): string {
		$changeData = json_encode( $change, JSON_UNESCAPED_SLASHES );
		$this->logger->debug( 'Emit change', [ 'sessionId' => $sessionId, 'change' => $changeData ] );
		return $this->response( EventType::CONTENT, 'newChange', $changeData );
	}

	/**
	 * @param array $eventData
	 * @return Change|null
	 */
	private function createChange( array $eventData ): ?Change {
		if ( isset( $eventData['change'] ) ) {
			return new Change(
				$eventData['change']['start'],
				$eventData['change']['transactions'] ?? [],
				$eventData['change']['selections'] ?? [],
				$eventData['change']['stores'] ?? []
			);
		}

		return null;
	}

	/**
	 * @param array $args
	 * @return array|null
	 */
	private function parseEventData( array $args ): ?array {
		$rawJson = $args['eventData'] ?? null;
		if ( !$rawJson ) {
			$this->logger->error( "Missing eventData in message", $args );
			return null;
		}
		$eventData = json_decode( $rawJson, true );
		if ( json_last_error() === JSON_ERROR_UTF16 ) {
			$this->logger->info( 'JSON_ERROR_UTF16... fixing Surrogate Pairs' );
			$cleanedJson = $this->fixSurrogatePairs( $rawJson );
			$eventData = json_decode( $cleanedJson, true );
		}

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			$this->logger->error( 'JSON decode error: ' . json_last_error_msg() );
			return null;
		}
		return $eventData;
	}

	/**
	 * Function sends $message back to the authors as response to all affected authors,
	 * excluding not relevant connections.
	 *
	 * $recipients - array of connection IDs that will be affected by response sending
	 *
	 * @param ConnectionList $connectionList
	 * @param string $message - content of response
	 * @param int $sessionId - identifier that will be used as default
	 * if $relevantConnections are not set. Recipients will be all active users in the session
	 * @param array $relevantConnections - array of recipients to current message
	 * @param array $notRelevantConnections - array of authors that might be excluded from
	 * recipients list (will not receive this message)
	 */
	private function sendMessage(
		ConnectionList $connectionList, string $message, int $sessionId = 0,
		array $relevantConnections = [], array $notRelevantConnections = []
	) {
		// Create recipients list
		if ( $relevantConnections ) {
			$recipients = $relevantConnections;
		} else {
			$recipients = $this->sessionDAO->getActiveConnections( $sessionId );
			$recipients = $recipients ?: [];
		}

		$recipients = array_diff( $recipients, $notRelevantConnections );

		$this->logger->debug( "Sending message '$message' to: " . json_encode( $recipients ) );
		foreach ( $recipients as $recipient ) {
			$conn = $connectionList->get( $recipient );
			if ( $conn ) {
				$conn->send( $message );
			}
		}
	}
}
