<?php

namespace MediaWiki\Extension\CollabPads\Backend\DAO;

use Exception;
use MediaWiki\Extension\CollabPads\Backend\IAuthorDAO;
use MediaWiki\Extension\CollabPads\Backend\ICollabSessionDAO;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

class DAOFactory {

	/**
	 * @param array $config
	 * @param LoggerInterface $logger
	 * @return ICollabSessionDAO
	 * @throws Exception
	 */
	public static function createSessionDAO( array $config, LoggerInterface $logger ): ICollabSessionDAO {
		if ( $config['db-type'] === 'mongo' ) {
			$instance = new MongoDBCollabSessionDAO( $config );
			$instance->setLogger( $logger );
			return $instance;
		}

		throw new UnexpectedValueException( "Invalid database type '{$config['db-type']}'" );
	}

	/**
	 * @param array $config
	 * @param LoggerInterface $logger
	 * @return IAuthorDAO
	 * @throws Exception
	 */
	public static function createAuthorDAO( array $config, LoggerInterface $logger ): IAuthorDAO {
		if ( $config['db-type'] === 'mongo' ) {
			return new MongoDBAuthorDAO( $config );
		}

		throw new UnexpectedValueException( "Invalid database type '{$config['db-type']}'" );
	}
}
