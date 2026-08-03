<?php

wfLoadExtensions( [
	'Auth_remoteuser',
	'LDAPProvider',
	'LDAPAuthentication2',
	'LDAPAuthorization',
	'LDAPGroups',
	'LDAPUserInfo',
	'PluggableAuth'
] );

$GLOBALS['LDAPAuthentication2AllowLocalLogin'] = false;
$GLOBALS['LDAPAuthentication2UsernameNormalizer'] = 'strtolower';
$GLOBALS['LDAPAuthorizationAutoAuthUsernameNormalizer'] = 'strtolower';
$GLOBALS['LDAPAuthorizationAutoAuthRemoteUserStringParser'] = 'username-at-domain';
$GLOBALS['LDAPProviderDefaultDomain'] = "DOMAIN.LOCAL";
$GLOBALS['bsgPermissionConfig']['autocreateaccount'] = [
	'type' => 'global',
	'roles' => [ 'autocreateaccount' ]
];

$GLOBALS['wgEmailAuthentication'] = false;
$GLOBALS['wgAuthRemoteuserAllowUserSwitch'] = true;

$GLOBALS['wgExtensionFunctions'][] = function () {
	$GLOBALS['wgPluggableAuth_Config']['Log with DOMAIN.LOCAL'] = [
		'plugin' => 'LDAPAuthentication2',
		'data' => [
			'domain' => 'DOMAIN.LOCAL'
		]
	];
};

$GLOBALS['LDAPProviderDomainConfigProvider'] = function( $ldapConfig ) {
	$config = [
		'DOMAIN.LOCAL' => [
			'connection' => [
				'server' => 'DOMAIN.LOCAL',
				'user' => '<ldap-server-user>',
				'pass' => '<ldap-server-pass>',
				'basedn' => '<base-dn>',
				'userbasedn' => '<user-base-dn>',
				'groupbasedn' => '<group-base-dn>',
				'searchattribute' => 'samaccountname',
				'usernameattribute' => 'samaccountname',
				'realnameattribute' => 'displayname',
				'emailattribute' => 'mail',
				'grouprequest' => 'MediaWiki\\Extension\\LDAPProvider\\UserGroupsRequest\\GroupMember::factory',
				'nestedgroups' => true,
			],
			'authorization' => [
				'rules' => [
					'groups' => [
						'required' => [
							'<required-group-dn>',
						],
					],
				],
			],
			'userinfo' => [
				'attributes-map' => [
					'email' => 'mail',
					'realname' => 'displayname',
				],
			],
			'groupsync' => [
				'mechanism' => 'MediaWiki\\Extension\\LDAPGroups\\SyncMechanism\\AllGroups::factory',
				'locally-managed' => [
					'sysop', 'bureaucrat', 'bot', 'interface-admin',
				],
			],
		],
	];

	return new \MediaWiki\Extension\LDAPProvider\DomainConfigProvider\InlinePHPArray( $config );
};

$GLOBALS['wgAuthRemoteuserUserName'] = function() {
	$user = '';
	if( isset( $_SERVER['HTTP_X_REMOTE_USER'] ) ) {
		$user = $_SERVER['HTTP_X_REMOTE_USER'];
	}

	// Bypass fot Parsoid / PhantomJS calls
	if( empty( $user ) && substr( $_SERVER[ 'REMOTE_ADDR' ], 0, 4 ) == '127.' ) {
		$cookieName = $GLOBALS['wgDBname'] . '304f3058RemoteToken';
		$userNameFromCookie = $_COOKIE[$cookieName];
		$user = $userNameFromCookie . '@' . $GLOBALS['LDAPProviderDefaultDomain'];
	}

	return $user;
};