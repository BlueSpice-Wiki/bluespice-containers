<?php

namespace BlueSpice\Service\ParallelRunJobs;

use InvalidArgumentException;

class Config {

	/** @var string */
	public $path;
	/** @var string */
	public $type;
	/** @var array */
	public $dbConnection;
	/** @var array */
	public $jobConfig;
	/** @var array */
	public $farmConfig;
	/** @var array */
	public $environment;

	/**
	 * @param array $values
	 * @return static
	 */
	public static function newFromValues( mixed $values ): static {
		$environment = $values['environment'] ?? [];
		$wiki = $values['wiki'] ?? [];

		$jobs = $values['runjobs'] ?? [];
		$farm = $values['farm'] ?? [];
		$farm['maxparallel'] = (int)( $farm['maxparallel'] ?? 10 );

		static::assertRequiredValues( [ 'path' ], $wiki );
		$db = $values['database'] ?? [];
		if ( $wiki['type'] === 'farm' ) {
			static::assertRequiredValues( [ 'dbname', 'dbuser', 'dbpassword' ], $db );
			$db = [
				'dbserver' => $db['dbserver'] ?? 'localhost',
				'dbname' => $db['dbname'],
				'dbuser' => $db['dbuser'],
				'dbpassword' => $db['dbpassword'],
				'dbprefix' => $db['dbprefix'] ?? '',
				'dbport' => $db['dbport'] ?? '3306',
			];
		}

		$jobs = [
			'maxtime' => (int)( $jobs['maxtime'] ?? 30 ),
			'maxjobs' => (int)( $jobs['maxjobs'] ?? 100 ),
			'cooldown' => (int)( $jobs['cooldown'] ?? 1 ),
		];
		$environment = [
			'php' => $environment['php'] ?? '/usr/bin/php',
		];

		return new static(
			$wiki['path'],
			$wiki['type'] ?? 'standalone',
			$db,
			$jobs,
			$farm,
			$environment
		);
	}

	/**
	 * @param array $fields
	 * @param array $data
	 * @return void
	 */
	private static function assertRequiredValues( array $fields, array $data ) {
		foreach ( $fields as $field ) {
			if ( !isset( $data[$field] ) ) {
				throw new InvalidArgumentException( "Missing required field: $field" );
			}
		}
	}

	/**
	 * @param string $path
	 * @param string $type
	 * @param array $dbConnection
	 * @param array $jobConfig
	 * @param array $farmConfig
	 * @param array $environment
	 */
	public function __construct(
		string $path, string $type, array $dbConnection, array $jobConfig, array $farmConfig, array $environment
	) {
		$this->path = $path;
		$this->type = $type;
		$this->dbConnection = $dbConnection;
		$this->jobConfig = $jobConfig;
		$this->farmConfig = $farmConfig;
		$this->environment = $environment;
	}

	/**
	 * @return string
	 */
	public function getPath(): string {
		return $this->path;
	}

	/**
	 * @return bool
	 */
	public function isFarmingEnvironment(): bool {
		return $this->type === 'farm';
	}

	/**
	 * @return string
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * @return array
	 */
	public function getDbConnection(): array {
		return $this->dbConnection;
	}

	/**
	 * @return array
	 */
	public function getJobConfig(): array {
		return $this->jobConfig;
	}

	/**
	 * @return array
	 */
	public function getFarmConfig(): array {
		return $this->isFarmingEnvironment() ? $this->farmConfig : [];
	}

	public function getEnvironment(): array {
		return $this->environment;
	}

	/**
	 * @return string
	 */
	public function getPhpPath(): string {
		return $this->environment['php'];
	}

	/**
	 * @return string
	 */
	public function getRunJobsPath(): string {
		return rtrim( $this->path, '/' ) . '/maintenance/runJobs.php';
	}
}
