<?php

try {
	$xmldata = file_get_contents( '/data/simplesamlphp/saml_idp_metadata.xml' );
	$entities = \SimpleSAML\Metadata\SAMLParser::parseDescriptorsString( $xmldata );

	$idpRemoteMetaData = [];
	foreach ( $entities as &$entity ) {
		//Last one wins
		$currentMetadata = $entity->getMetadata20IdP();
		if ( $currentMetadata !== null ) {
			$idpRemoteMetaData = $currentMetadata;
		}
	}
	unset( $idpRemoteMetaData['entityDescriptor'] );

	return $idpRemoteMetaData;
} catch ( \SimpleSAML\Error\Exception $e ) {
	return [];
}
