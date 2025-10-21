<?php

include (__DIR__ . '/config.php.dist');

$baseUrl = $GLOBALS['wgServer'] = bsAssembleURL(
	[ 'WIKI_PROTOCOL', 'https' ],
	[ 'WIKI_HOST', 'localhost' ],
	[ 'WIKI_PORT', '443' ]
);

// TODO calculate from environment variable
$loglevel = SimpleSAML\Logger::WARNING;

$customConfig = [
	'baseurlpath' => "$baseUrl/_sp",
	'application' => [
		'baseURL' => $baseUrl
	],
	'module.enable' => [
		'exampleauth' => false,
		'core' => true,
		'admin' => true,
		'saml' => true
	],

	'auth.adminpassword' => ( new Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher(
		4, // time cost
		65536, // memory cost
		null, // cost
		PASSWORD_ARGON2ID,
	) )->hash( getenv('INTERNAL_SIMPLESAMLPHP_ADMIN_PASS') ),
	'admin.protectindexpage' => true,
	'admin.protectmetadata' => false,
	'admin.checkforupdates' => false,
	'session.cookie.secure' => true,
	'session.cookie.name' =>
		getenv('DB_NAME') .
		( getenv('DB_PREFIX') ?? '' ) .
		'SAMLSessionID',
	'session.authtoken.cookiename' =>
		getenv('DB_NAME') .
		( getenv('DB_PREFIX') ?? '' ) .
		'SAMLAuthToken',
	'enable.http_post' => true,
	'secretsalt' => getenv('INTERNAL_SIMPLESAMLPHP_SECRET_SALT'),

	'logging.handler' => 'errorlog', //  write to stdout
	'logging.level' => $loglevel,
	'debug' => [
		'saml' => getenv( 'DEV_WIKI_DEBUG' ) ?: false,
		'backtraces' => getenv( 'DEV_WIKI_DEBUG' ) ?: false,
		'validatexml' => getenv( 'DEV_WIKI_DEBUG' ) ?: false,
	],

	'cachedir' => '/data/simplesamlphp/cache/',
	'loggingdir' => '/data/simplesamlphp/logs/',
	'datadir' => '/data/simplesamlphp/data/',

	'showerrors' => getenv( 'DEV_WIKI_DEBUG' ) ?: false,
	'errorreporting' => true,

	'technicalcontact_name' => getenv('WIKI_NAME') ?? 'BlueSpice',
	'technicalcontact_email' => getenv('WIKI_EMERGENCYCONTACT') ?? '',
	'mail.transport.method' => 'smtp',
	'mail.transport.options' => [
		'host' => getenv( 'SMTP_HOST' ),
		'port' => getenv( 'SMTP_PORT' ) ?: 25,
		'username' => getenv( 'SMTP_USER' ),
		'password' => getenv( 'SMTP_PASS' ),
		'security' => 'tls'
	],
	'sendmail_from' => getenv('WIKI_EMERGENCYCONTACT') ?: '',

	'store.type' => 'sql',
	'store.sql.dsn' => 'mysql:dbname=' . (getenv('DB_NAME') ?? 'database') . ';host=' . getenv('DB_HOST'),
	'store.sql.username' => getenv('DB_USER'),
	'store.sql.password' => getenv('DB_PASS'),
	'store.sql.prefix' => (getenv('DB_PREFIX') ?? '') . 'SimpleSAMLphp_',
];

$config = $customConfig + $config;

unset($customConfig);
unset($baseUrl);
