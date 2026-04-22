<?php

namespace BlueSpice\Service\ParallelRunJobs\QueueLock;

use BlueSpice\Service\ParallelRunJobs\Config;
use Redis;
use RedisException;
use RuntimeException;

class RedisQueueLock implements QueueLock {

	private const KEY_PREFIX = 'runjobs:lock:';

	/** @var Redis */
	private Redis $redis;

	/** @var int */
	private int $lockTtl;

	/**
	 * @param string $runnerId
	 * @param Config $config
	 * @throws RedisException
	 */
	public function __construct( private string $runnerId, Config $config ) {
		$connection = $config->getRedisConnection();
		if ( !$connection ) {
			throw new RuntimeException( 'Redis connection not configured' );
		}
		$this->lockTtl = max( ( $config->getJobConfig()['maxtime'] ?? 30 ) * 2, 60 );
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

	/**
	 * @inheritDoc
	 */
	public function isLocked( string $instance ): bool {
		return (bool)$this->redis->exists( self::KEY_PREFIX . $instance );
	}

	/**
	 * @inheritDoc
	 */
	public function obtainLock( string $instance ): string {
		$lockId = uniqid( $this->runnerId . '_' );
		$key = self::KEY_PREFIX . $instance;
		$result = $this->redis->set( $key, $lockId, [ 'NX', 'EX' => $this->lockTtl ] );
		if ( $result ) {
			return $lockId;
		}
		return '';
	}

	/**
	 * @inheritDoc
	 */
	public function release( string $instance ): bool {
		$key = self::KEY_PREFIX . $instance;
		// Atomically release only if this runner owns the lock
		$script = <<<'LUA'
			local val = redis.call('get', KEYS[1])
			if val and string.find(val, ARGV[1], 1, true) == 1 then
				return redis.call('del', KEYS[1])
			end
			return 0
		LUA;
		return $this->redis->eval( $script, [ $key, $this->runnerId . '_' ], 1 ) > 0;
	}
}