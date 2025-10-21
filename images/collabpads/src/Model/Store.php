<?php

namespace MediaWiki\Extension\CollabPads\Backend\Model;

class Store implements \JsonSerializable {

	/**
	 * @var array
	 */
	private $hashes = [];

	/**
	 * @var array
	 */
	private $hashStore = [];

	/**
	 * @param array $hashes
	 * @param array $hashStore
	 */
	public function __construct( array $hashes, array $hashStore ) {
		$this->hashes = $hashes;
		$this->hashStore = $hashStore;
	}

	/**
	 * @return array
	 */
	public function getHashes(): array {
		return $this->hashes;
	}

	/**
	 * @return array
	 */
	public function getHashStore(): array {
		return $this->hashStore;
	}

	/**
	 * @param mixed $data
	 * @return self
	 */
	public static function fromData( $data ) {
		if ( !$data ) {
			return new self( [], [] );
		}
		return new self( $data['hashes'], $data['hashStore'] );
	}

	public function jsonSerialize(): mixed {
		return [
			'hashes' => $this->hashes,
			'hashStore' => $this->hashStore
		];
	}

	/**
	 * @return int
	 */
	public function getLength(): int {
		return count( $this->hashes );
	}

	/**
	 * @param Store $other
	 * @return void
	 */
	public function merge( Store $other ) {
		if ( $other === $this ) {
			return;
		}

		foreach ( $other->getHashes() as $otherHash ) {
			if ( !array_key_exists( $otherHash, $this->hashStore ) ) {
				$this->hashStore[ $otherHash ] = $other->hashStore[ $otherHash ];
				$this->hashes[] = $otherHash;
			}
		}
	}

	/**
	 * @param int $start
	 * @param int $end
	 * @return Store
	 */
	public function slice( int $start, int $end ): Store {
		$newHashes = array_slice( $this->hashes, $start, $end - $start );
		$newHashStore = [];
		foreach ( $newHashes as $hash ) {
			$newHashStore[ $hash ] = $this->hashStore[ $hash ];
		}

		return new self( $newHashes, $newHashStore );
	}

	/**
	 * @param Store $omit
	 * @return Store
	 */
	public function difference( Store $omit ) {
		if ( $omit instanceof Store ) {
			$omit = $omit->getHashStore();
		}
		$newHashes = [];
		$newHashStore = [];

		foreach ( $this->hashes as $hash ) {
			if ( !array_key_exists( $hash, $omit ) ) {
				$newHashes[] = $hash;
				$newHashStore[ $hash ] = $this->hashStore[ $hash ];
			}
		}
		return new Store( $newHashes, $newHashStore );
	}
}
