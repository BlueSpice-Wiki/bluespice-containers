<?php

namespace BlueSpice\Service\ParallelRunJobs\QueueLock;

class NullQueueLock implements QueueLock {

	/**
	 * @inheritDoc
	 */
	public function isLocked( string $instance ): bool {
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function obtainLock( string $instance ): string {
		return uniqid( 'null-lock-' );
	}

	/**
	 * @inheritDoc
	 */
	public function release( string $instance ): bool {
		return true;
	}
}