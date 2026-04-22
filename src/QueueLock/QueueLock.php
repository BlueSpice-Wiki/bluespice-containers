<?php

namespace BlueSpice\Service\ParallelRunJobs\QueueLock;

interface QueueLock {

	public function isLocked( string $instance ): bool;

	public function obtainLock( string $instance ): string;

	public function release( string $instance ): bool;
}