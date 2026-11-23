<?php

// Generates a large test document (~3000 pages, ~800 MB of embedded attachments).
// The data is NOT checked into the repository; generate it locally before testing:
//
//   php generate-large-doc.php [targetFolder] [pageCount] [attachmentCount] [attachmentSizeMB]
//   php test.php data/large-doc/
//
// Defaults: data/large-doc, 3000 pages, 80 attachments of 10 MB each (= 800 MB).
// Attachments are filled with random bytes so that they can not be compressed
// when embedded into the PDF, i.e. the resulting PDF really is > 800 MB.
// Keep `attachmentSizeMB` below the service's `APP_MULTIPART_MAX_FILE_SIZE` (default 50MB).

$targetFolder = rtrim( $argv[1] ?? __DIR__ . '/data/large-doc', '/' );
$pageCount = (int)( $argv[2] ?? 3000 );
$attachmentCount = (int)( $argv[3] ?? 80 );
$attachmentSizeMB = (int)( $argv[4] ?? 10 );

$documentId = basename( $targetFolder );
$attachmentFolder = "$targetFolder/attachments";
if ( !is_dir( $attachmentFolder ) ) {
	mkdir( $attachmentFolder, 0777, true );
}

# Create attachments
$chunkSize = 1024 * 1024;
$attachmentNames = [];
for ( $i = 1; $i <= $attachmentCount; $i++ ) {
	$name = sprintf( 'Attachment_%03d.bin', $i );
	$attachmentNames[] = $name;
	$path = "$attachmentFolder/$name";
	if ( file_exists( $path ) && filesize( $path ) === $attachmentSizeMB * $chunkSize ) {
		echo "Skipping existing $name\n";
		continue;
	}
	echo "Creating $name ($attachmentSizeMB MB)\n";
	$fh = fopen( $path, 'wb' );
	for ( $j = 0; $j < $attachmentSizeMB; $j++ ) {
		fwrite( $fh, random_bytes( $chunkSize ) );
	}
	fclose( $fh );
}
# Remove stale attachments from previous runs with other parameters
foreach ( glob( "$attachmentFolder/*" ) as $file ) {
	if ( !in_array( basename( $file ), $attachmentNames ) ) {
		unlink( $file );
	}
}

# Distribute the attachment links evenly over the pages
$attachmentsByPage = [];
foreach ( $attachmentNames as $idx => $name ) {
	$page = (int)floor( $idx * $pageCount / max( 1, $attachmentCount ) ) + 1;
	$attachmentsByPage[$page][] = $name;
}

# Create document
$loremIpsum = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor '
	. 'incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud '
	. 'exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure '
	. 'dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.';

$document = "$targetFolder/$documentId.html";
echo "Creating $document ($pageCount pages)\n";
$fh = fopen( $document, 'w' );
fwrite( $fh, <<<HTML
<html>
	<head>
		<title>Large document</title>
		<style>
			@page { size: A4; margin: 2cm; }
			body { font-family: sans-serif; font-size: 11pt; }
			.page { page-break-after: always; }
			.page:last-child { page-break-after: auto; }
		</style>
	</head>
	<body>

HTML );
for ( $page = 1; $page <= $pageCount; $page++ ) {
	$html = "\t\t<div class=\"page\">\n\t\t\t<h1>Page $page</h1>\n";
	for ( $p = 0; $p < 3; $p++ ) {
		$html .= "\t\t\t<p>$loremIpsum</p>\n";
	}
	foreach ( $attachmentsByPage[$page] ?? [] as $name ) {
		$html .= "\t\t\t<p>Attachment: <a data-fs-embed-file=\"true\" href=\"attachments/$name\""
			. " class=\"internal media\" title=\"$name\">$name</a></p>\n";
	}
	$html .= "\t\t</div>\n";
	fwrite( $fh, $html );
}
fwrite( $fh, "\t</body>\n</html>\n" );
fclose( $fh );

echo "Done. Run `php test.php $targetFolder/`\n";
