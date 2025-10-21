<?php

$idpRemoteMetaData = require( __DIR__ . '/../config/_bluespice-saml20-idp-remote-meta.php' );

$metadata[ $idpRemoteMetaData['entityid'] ] = $idpRemoteMetaData;

unset( $idpRemoteMetaData );
