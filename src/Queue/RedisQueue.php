<?php

namespace BlueSpice\Service\ParallelRunJobs\Queue;

use Redis;
use RedisException;
use RuntimeException;

class RedisQueue extends DatabaseQueue {

	private const UNCLAIMED_KEY_PATTERN = '*:jobqueue:*:l-unclaimed';
	private const SKIP_COOLDOWN = 3;

	/** @var Redis|null */
	private ?Redis $redis = null;

	/** @var array */
	private array $wikiIdMappingCache = [];

	/** @var array<string, true> Instances currently in-flight (returned but not yet resolved) */
	private array $inFlight = [];

	/** @var array<string, int> Instance => timestamp until which it should be skipped */
	private array $skippedUntil = [];

	/**
	 * @inheritDoc
	 */
	public function getNext(): ?string {
		return $this->fetchNextInstanceWithPendingJobs();
	}

	/**
	 * @inheritDoc
	 */
	public function skip( string $instance ): void {
		unset( $this->inFlight[$instance] );
		// If skipped, let's wait a bit before trying this instance again to avoid loops
		$this->skippedUntil[$instance] = time() + self::SKIP_COOLDOWN;
	}

	/**
	 * @inheritDoc
	 */
	public function onStartFailed( string $instance ): void {
		unset( $this->inFlight[$instance] );
	}

	/**
	 * @inheritDoc
	 */
	public function onFailure( string $instance ): void {
		unset( $this->inFlight[$instance] );
	}

	/**
	 * @inheritDoc
	 */
	public function onSuccess( string $instance ): void {
		unset( $this->inFlight[$instance] );
	}

	/**
	 * Scan Redis for keys matching {wiki_id}:jobqueue:{type}:l-unclaimed,
	 * extract unique wiki IDs, and return the first eligible instance
	 * that is not already in-flight.
	 *
	 * @return string|null
	 * @throws RedisException
	 */
	private function fetchNextInstanceWithPendingJobs(): ?string {
		$this->assertRedisConnection();

		$include = $this->config->getFarmConfig()['include-instances'] ?? [];
		$exclude = $this->config->getFarmConfig()['exclude-instances'] ?? [];
		$include = array_diff( $include, $exclude );

		$seen = [];
		$cursor = null;
		while ( $cursor !== 0 ) {
			$result = $this->redis->scan( $cursor, self::UNCLAIMED_KEY_PATTERN, 100 );
			if ( $result === false ) {
				break;
			}
			foreach ( $result as $key ) {
				$wikiId = strstr( $key, ':', true );
				if ( $wikiId === false || isset( $seen[$wikiId] ) ) {
					continue;
				}
				$seen[$wikiId] = true;

				$instance = $this->wikiIdToInstance( $wikiId );
				if ( $instance === null || isset( $this->inFlight[$instance] ) ) {
					continue;
				}
				if ( isset( $this->skippedUntil[$instance] ) ) {
					if ( time() < $this->skippedUntil[$instance] ) {
						continue;
					}
					unset( $this->skippedUntil[$instance] );
				}
				if ( !empty( $include ) ) {
					if ( !in_array( $instance, $include ) ) {
						continue;
					}
				} elseif ( !empty( $exclude ) && in_array( $instance, $exclude ) ) {
					continue;
				}
				$this->inFlight[$instance] = true;
				return $instance;
			}
		}

		return null;
	}

	/**
	 * Map a wiki ID from the Redis queue to a farm instance name.
	 *
	 * @param string $wikiId
	 * @return string|null
	 */
	private function wikiIdToInstance( string $wikiId ): ?string {
		if ( isset( $this->wikiIdMappingCache[$wikiId] ) ) {
			return $this->wikiIdMappingCache[$wikiId];
		}
		$this->assertManagementConnection();
		$wikiIdEscaped = $this->managementDb->real_escape_string( $wikiId );
		$table = $this->config->getConnection()['dbprefix'] . 'simple_farmer_instances';
		$res = $this->managementDb->query(
			"SELECT sfi_path FROM $table WHERE sfi_wiki_id = '" . $wikiIdEscaped . "' LIMIT 1"
		);
		if ( $res === false ) {
			return 'w';
		}
		$row = $res->fetch_assoc();
		if ( $row === null ) {
			return 'w';
		}
		$this->wikiIdMappingCache[$wikiId] = $row['sfi_path'];
		return $row['sfi_path'];
	}

	/**
	 * @return void
	 * @throws RedisException
	 */
	private function assertRedisConnection(): void {
		if ( $this->redis !== null ) {
			return;
		}
		$connection = $this->config->getRedisConnection();
		if ( !$connection ) {
			throw new RuntimeException( 'Redis connection not configured' );
		}
		$this->redis = new Redis();
		try {
			$this->redis->connect( $connection['host'], $connection['port'] );
		} catch ( RedisException $e ) {
			throw new RuntimeException( 'Failed to connect to Redis: ' . $e->getMessage() );
		}
		if ( !empty( $connection['password'] ) ) {
			$this->redis->auth( $connection['password'] );
		}
		if ( ( $connection['database'] ?? 0 ) > 0 ) {
			$this->redis->select( $connection['database'] );
		}
	}
}