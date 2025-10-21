<?php

namespace MediaWiki\Extension\CollabPads\Backend\Model;

class Author implements \JsonSerializable {

	/**
	 * @var int
	 */
	private $id;

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @param int $id
	 * @param string $name
	 */
	public function __construct( int $id, string $name ) {
		$this->id = $id;
		$this->name = $name;
	}

	/**
	 * @return int
	 */
	public function getId(): int {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @return mixed
	 */
	public function jsonSerialize(): mixed {
		return [
			'id' => $this->id,
			'name' => $this->name
		];
	}

	// TODO: Add stuff from session
}
