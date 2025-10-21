<?php

namespace MediaWiki\Extension\CollabPads\Backend\DAO;

use Exception;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;

abstract class MongoDBDAOBase {

	/**
	 * @var Collection
	 */
	protected $collection;

	/**
	 * @var string
	 */
	private $dbName = 'collabpads';

	/**
	 * @var string
	 */
	private $dbHost = 'localhost';

	/**
	 * @var int
	 */
	private $dbPort = 27017;

	/**
	 * @var string
	 */
	private $dbAuthString = '';

	/**
	 * @var string
	 */
	private $defaultAuthDB = '';

	/**
	 * @var Database
	 */
	private $db = null;

	/**
	 * @param array $config
	 * @throws Exception
	 */
	public function __construct( array $config ) {
		if ( isset( $config['db-host'] ) ) {
			$this->dbHost = $config['db-host'];
		} else {
			throw new Exception( 'Config "db-host" is not set!' );
		}
		if ( isset( $config['db-name'] ) ) {
			$this->dbName = $config['db-name'];
		} else {
			throw new Exception( 'Config "db-name" is not set!' );
		}
		if ( isset( $config['db-port'] ) ) {
			$this->dbPort = $config['db-port'];
		} else {
			throw new Exception( 'Config "db-port" is not set!' );
		}
		if ( isset( $config['db-defaultauthdb'] ) ) {
			$this->defaultAuthDB = $config['db-defaultauthdb'];
		}

		if ( $config['db-user'] !== '' ) {
			$this->dbAuthString =
				rawurlencode( $config['db-user'] ) .
				':' .
				rawurlencode( $config['db-password'] ) .
				'@';
		}

		$this->collection = $this->getCollection();
	}

	/**
	 * @return Collection
	 */
	protected function getCollection(): Collection {
		$this->initDB();
		return $this->db->selectCollection( $this->getCollectionName() );
	}

	private function initDB() {
		if ( $this->db === null ) {
			$connectionString = 'mongodb://'
				. $this->dbAuthString
				. $this->dbHost
				. ':'
				. $this->dbPort
				. '/'
				. $this->defaultAuthDB;

			$client = new Client( $connectionString );
			$this->db = $client->selectDatabase( $this->dbName );
		}
	}

	/**
	 * @return string
	 */
	abstract protected function getCollectionName(): string;
}
