<?php

namespace MediaWiki\Extension\CollabPads\Backend;

abstract class EventType {
	public const CONTENT = 42;
	public const IS_ALIVE = 2;
	public const KEEP_ALIVE = 3;
	public const CONNECTION_INIT = 0;
	public const CONNECTION_ESTABLISHED = 40;
	public const CONNECTION_REFUSED = 41;
}
