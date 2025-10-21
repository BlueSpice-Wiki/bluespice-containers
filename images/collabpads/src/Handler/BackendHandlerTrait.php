<?php

namespace MediaWiki\Extension\CollabPads\Backend\Handler;

trait BackendHandlerTrait {

	/**
	 * @param int $eventId
	 * @param string $eventName
	 * @param string $eventData
	 * @return string
	 */
	private function response( int $eventId, string $eventName, string $eventData = '' ): string {
		if ( $eventData !== '' ) {
			$eventData = ',' . $eventData;
		}

		return $eventId . '["' . $eventName . '"' . $eventData . ']';
	}
}
