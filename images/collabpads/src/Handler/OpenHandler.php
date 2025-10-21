<?php

namespace MediaWiki\Extension\CollabPads\Backend\Handler;

use Exception;
use GuzzleHttp\Client;
use MediaWiki\Extension\CollabPads\Backend\ConnectionList;
use MediaWiki\Extension\CollabPads\Backend\EventType;
use MediaWiki\Extension\CollabPads\Backend\IAuthorDAO;
use MediaWiki\Extension\CollabPads\Backend\ICollabSessionDAO;
use MediaWiki\Extension\CollabPads\Backend\Model\Author;
use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface;

class OpenHandler {
	use BackendHandlerTrait;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var IAuthorDAO
	 */
	private $authorDAO;

	/**
	 * @var ICollabSessionDAO
	 */
	private $sessionDAO;

	/**
	 * @var array
	 */
	private $serverConfigs;

	/**
	 * @var Client
	 */
	private $httpClient;

	/**
	 * @param IAuthorDAO $authorDAO
	 * @param ICollabSessionDAO $sessionDAO
	 * @param array $serverConfigs
	 * @param Client $httpClient
	 * @param LoggerInterface $logger
	 */
	public function __construct(
		IAuthorDAO $authorDAO, ICollabSessionDAO $sessionDAO,
		array $serverConfigs, Client $httpClient, LoggerInterface $logger
	) {
		$this->authorDAO = $authorDAO;
		$this->sessionDAO = $sessionDAO;
		$this->serverConfigs = $serverConfigs;
		$this->httpClient = $httpClient;

		$this->logger = $logger;
	}

	/**
	 * @param ConnectionInterface $conn
	 * @param ConnectionList $connectionList
	 * @return void
	 */
	public function handle( ConnectionInterface $conn, ConnectionList $connectionList ): void {
		// Get data from WikiServer
		$queryArgs = [];

		parse_str( $conn->httpRequest->getURI()->getQuery(), $queryArgs );
		$this->logger->info( 'Opening connection for: ' . $queryArgs['docName'] );

		$configs = $this->createCurlRequest( $conn, $queryArgs['docName'] );
		if ( !$configs['access'] ) {
			$this->logger->info( 'Access denied' );
			$conn->close();
			return;
		}
		$configs['connectionId'] = $conn->resourceId;

		// Check if author exists & create if not
		$author = $this->authorDAO->getAuthorByName( $configs['user']['userName'] );
		if ( !$author ) {
			$author = $this->newAuthor( $configs['user']['userName'] );
			$this->logger->info( "Created new author: Name '{$author->getName()}', ID '{$author->getId()}'" );
		} else {
			$this->logger->info( "Found existing author: Name '{$author->getName()}', ID '{$author->getId()}'" );
		}

		$configs['authorId'] = $author->getId();
		$configs['authorName'] = $author->getName();

		// Check if session exists & create if not
		$session = $this->sessionDAO->getSessionByName( $configs['wikiScriptPath'], $configs['pageTitle'], $configs['pageNamespace'] ); // phpcs:ignore Generic.Files.LineLength.TooLong
		if ( !$session ) {
			$this->logger->info( "No existing session found for page title '{$configs['pageTitle']}' in namespace '{$configs['pageNamespace']}'. Creating a new session." ); // phpcs:ignore Generic.Files.LineLength.TooLong
			$session = $this->newSession( $configs );
			$this->logger->info( "New session created. Token '{$session['s_token']}', ID '{$session['s_id']}', Page Title '{$session['s_page_title']}', Namespace '{$session['s_page_namespace']}'" ); // phpcs:ignore Generic.Files.LineLength.TooLong
		} else {
			$this->logger->info( "Found existing session. Token '{$session['s_token']}', ID '{$session['s_id']}', Page Title '{$session['s_page_title']}', Namespace '{$session['s_page_namespace']}'" ); // phpcs:ignore Generic.Files.LineLength.TooLong
		}

		$configs['sessionToken'] = "" . $session['s_token'];
		$configs['sessionId'] = $session['s_id'];

		// Init the session for author
		$this->init( $conn, $connectionList, $configs );
	}

	/**
	 * @param ConnectionInterface $conn
	 * @param ConnectionList $connectionList
	 * @param array $configs
	 */
	private function init( ConnectionInterface $conn, ConnectionList $connectionList, array $configs ) {
		$settingsString = $this->getSettingsString();
		$conn->send( $settingsString );
		$conn->send( EventType::CONNECTION_ESTABLISHED );

		$answer = $this->register( $configs );
		$this->logger->debug( "Registering: {$answer}" );
		$conn->send( $answer );

		$answer = $this->initDoc( $configs );
		$this->logger->debug( "Init doc: {$answer}" );
		$conn->send( $answer );

		// Add to the connections list
		$connectionList->add( $conn, $configs['authorId'] );

		// Proving already established connections must be done after all
		// init processes - that's important for normal work of disconnect event
		$answer = $this->authorAlreadyLogged( $configs );
		if ( $answer ) {
			$this->logger->debug( "Already logged: {$answer}" );
			// User has already opened this session
			$conn->send( $answer );
		}
	}

	/**
	 * @param ConnectionInterface $conn
	 * @param string $pageTitle
	 * @return array $response
	 */
	private function createCurlRequest( ConnectionInterface $conn, string $pageTitle ): array {
		$docNameParts = explode( '|', $pageTitle, 3 );

		$scriptPath = $docNameParts[0];
		$accessToken = $docNameParts[1];
		$pageName = $docNameParts[2];

		$url = $this->serverConfigs['baseurl'] . $scriptPath . "/rest.php/collabpads/acl/" .
			str_replace( "/", "|", $pageName ) . '/' . $accessToken;
		$this->logger->info( "Calling: {$url}" );

		try {
			$res = $this->httpClient->get( $url );
		} catch ( Exception $e ) {
			$this->logger->error( "Error during request: {$e->getMessage()}" );
			return [
				'access' => false
			];
		}

		$status = $res->getStatusCode();
		$output = (string)$res->getBody();
		$this->logger->debug( "Status: {$status}" );
		$this->logger->debug( "Result: {$output}" );

		if ( $status !== 200 ) {
			$this->logger->warning( "Bad response status: {$status}" );
			return [
				'access' => false
			];
		}

		$response = json_decode( $output, true );
		if ( !is_array( $response ) ) {
			$this->logger->warning( "Could not deserialize JSON" );
			return [
				'access' => false
			];
		}
		$userName = '';
		if ( $response['access'] ) {
			$userName = $response['user']['userName'];
		} else {
			$userName = $response['user']['mName'];
		}
		$this->logger->info( "Request complete. {$response['message']} for user {$userName}" );

		// Add some information to identify wiki where collab session is created
		$response['wikiScriptPath'] = $scriptPath;

		return $response;
	}

	/**
	 * @param string $authorName
	 * @return Author
	 * @throws Exception
	 */
	private function newAuthor( string $authorName ): Author {
		$this->authorDAO->setNewAuthor( $authorName );
		$author = $this->authorDAO->getAuthorByName( $authorName );
		if ( !$author ) {
			$this->logger->error( "Could not create new author for name $authorName" );
			throw new Exception( "Could not create new author for name $authorName" );
		}
		return $author;
	}

	/**
	 * @param array $config
	 * @return array
	 */
	private function newSession( array $config ) {
		$this->sessionDAO->setNewSession(
			$config['wikiScriptPath'], $config['pageTitle'], $config['pageNamespace'], $config['authorId']
		);
		return $this->sessionDAO->getSessionByName(
			$config['wikiScriptPath'], $config['pageTitle'], $config['pageNamespace']
		);
	}

	/**
	 * @return string
	 */
	private function getSettingsString(): string {
		$settingsArray = [
			'upgrades' => [],
			'pingInterval' => $this->serverConfigs['ping-interval'],
			'pingTimeout' => $this->serverConfigs['ping-timeout']
		];

		return EventType::CONNECTION_INIT . json_encode( $settingsArray );
	}

	/**
	 * @param array $config
	 * @return string
	 */
	private function register( array $config ): string {
		$this->connectToSession( $config );

		$response = [
			"serverId" => $this->serverConfigs['server-id'],
			"authorId" => $this->sessionDAO->getAuthorInSession( $config['sessionId'], $config['authorId'] )['id'],
			"token" => $config['sessionToken']
		];

		return $this->response( EventType::CONTENT, 'registered', json_encode( $response ) );
	}

	/**
	 * @param array $config
	 */
	private function connectToSession( array $config ) {
		$authorConnections = $this->sessionDAO->isAuthorInSession( $config['sessionId'], $config['authorId'] );

		if ( !$authorConnections ) {
			$this->sessionDAO->setNewAuthorInSession(
				$config['sessionId'], $config['authorId'],
				$config['user']['userName'], $config['user']['realName'],
				$this->generateRandomColor(), true, $config['connectionId'] );
		} else {
			$this->sessionDAO->activateAuthor( $config['sessionId'], $config['authorId'], $config['connectionId'] );
		}

		$this->authorDAO->setNewConnection( $config['connectionId'], $config['sessionId'], $config['authorId'] );
	}

	/**
	 * @return string
	 */
	private function generateRandomColor(): string {
		return sprintf( "%06X", mt_rand( 0, 0xFFFFFF ) );
	}

	/**
	 * @param array $config
	 * @return string
	 */
	private function initDoc( array $config ): string {
		$authors = $this->sessionDAO->getAllAuthorsFromSession( $config['sessionId'] );

		$sessionAuthors = [];
		foreach ( $authors as $author ) {
			if ( $author == null ) {
				continue;
			}

			$realName = ( isset( $author['value']['realName'] ) ) ? $author['value']['realName'] : '';
			$sessionAuthors[$author['id']] = [
				"name" => $author['value']['name'],
				"realName" => $realName,
				"color" => $author['value']['color']
			];
		}

		$response = [
			'history' => [
				"start" => 0,
				"transactions" => $this->sessionDAO->getFullHistoryFromSession( $config['sessionId'] ) ?: [],
				"stores" => $this->sessionDAO->getFullStoresFromSession( $config['sessionId'] ) ?: [],
				"selections" => $this->sessionDAO->getFullSelectionsFromSession( $config['sessionId'] ) ?: []
			],
			"authors" => $sessionAuthors
		];

		return $this->response( EventType::CONTENT, 'initDoc', json_encode( $response ) );
	}

	/**
	 * @param array $config
	 * @return string
	 */
	private function authorAlreadyLogged( array $config ): string {
		$author = $this->sessionDAO->getAuthorInSession( $config['sessionId'], $config['authorId'] );
		// if author is still inactive after init - don't have connections.
		// cause an alreadyLoggedIn event to prevent incorrect execution of program
		if ( $author && count( $author['value']['connection'] ) !== 1 ) {
			// if user has more than one connection
			return $this->response( EventType::CONTENT, 'alreadyLoggedIn' );
		}

		return "";
	}
}
