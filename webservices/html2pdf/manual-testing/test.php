<?php

// Call me like `php test.php doc1/`

require_once __DIR__ . '/vendor/autoload.php';

// This implementation mimics the behavior of `extensions/BlueSpiceUEModulePDF/includes/PDFServlet.class.php`

$sourceFolder = $argv[1];
$serviceUrl = $argv[2] ?? 'http://localhost:8080/Html2PDF/v1';
$imageFolder = $sourceFolder . '/images';
$attachmentFolder = $sourceFolder . '/attachments';
$stylesheetsFolder = $sourceFolder . '/stylesheets';
$documentId = basename( $sourceFolder );
$document = $sourceFolder . "/$documentId.html";

function doUpload( $documentId, $files, $type ) {
	$postData = [
		[
			'name' => 'wikiId',
			'contents' => 'html2pdftest'
		],
		[
			'name' => 'documentToken',
			'contents' => $documentId
		],
		[
			'name' => 'fileType',
			'contents' => $type
		]
	];

	foreach ($files as $idx => $file) {
		$filename = basename($file);
		echo "Uploading $type/$filename\n";
		$postData[] = [
			'name' => $filename,
			'contents' => file_get_contents( $file ),
			'filename' => $filename
		];
		$postData[] = [
			'name' => "{$filename}_name",
			'contents' => $filename
		];
	}
	$uploadUrl = $GLOBALS['serviceUrl'] . '/UploadAsset';

	$guzzle = new GuzzleHttp\Client();
	$response = $guzzle->request('POST', $uploadUrl, [
		'multipart' => $postData
	]);
	$body = $response->getBody();
	$json = json_decode($body, true);
	if ( $json['success'] !== true ) {
		echo "Failed to upload $type\n";
	}

	echo json_encode($json, JSON_PRETTY_PRINT) . "\n";
}

# Upload all images
$images = glob($imageFolder . '/*');
doUpload( $documentId, $images, 'images' );

# Upload all attachments
$attachments = glob($attachmentFolder . '/*');
doUpload( $documentId, $attachments, 'attachments' );

# Upload all stylesheets
$stylesheets = glob($stylesheetsFolder . '/*');
doUpload( $documentId, $stylesheets, 'stylesheets' );

# Upload the document
$documentFiles = [ $document ];
doUpload( $documentId, $documentFiles, '' );

# Create PDF
$postData = [
	'wikiId' => 'html2pdftest',
	'documentToken' => $documentId,
	'debug' => 'true'
];
$createPdfUrl = $GLOBALS['serviceUrl'] . '/RenderPDF';
$guzzle = new GuzzleHttp\Client();
$response = $guzzle->request('POST', $createPdfUrl, [
	'form_params' => $postData
]);
$status = $response->getStatusCode();
if ( $status !== 200 ) {
	echo "Failed to create PDF\n";
	var_dump( $response->getBody() );
}
$body = $response->getBody();
$pdfFileName = str_replace( '.html', '.pdf', $document );
file_put_contents( $pdfFileName, $body );