<?php

namespace MediaWiki\Extension\CollabPads\Backend;

use Ratchet\ConnectionInterface;

class ConnectionList {

	/**
	 * @var array
	 */
	private $connections = [];

	/**
	 * @param ConnectionInterface $conn
	 * @param int $authorId
	 * @return void
	 */
	public function add( ConnectionInterface $conn, int $authorId ): void {
		$this->connections[$conn->resourceId] = [
			'connection' => $conn,
			'authorId' => $authorId
		];
	}

	/**
	 * @param int $resourceId
	 * @return ConnectionInterface|null <tt>null</tt> if there is no such connection
	 */
	public function get( int $resourceId ): ?ConnectionInterface {
		if ( isset( $this->connections[$resourceId] ) ) {
			return $this->connections[$resourceId]['connection'];
		}

		return null;
	}

	/**
	 * @param int $resourceId
	 * @return void
	 */
	public function remove( int $resourceId ): void {
		unset( $this->connections[$resourceId] );
	}
}
