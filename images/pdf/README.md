# BlueSpice "Pdf" service

This is a service that provides PDF rendering backend for Extension:PDFCreator. It is based on the `xhtmlrenderer` Java library.

## Using it within BlueSpice or MediaWiki

Given you have this service running at `http://localhost:8080`, you can configure the BlueSpice pdf like this:

```php
$GLOBALS['wgPDFCreatorHtml2PdfServiceUrl'] = 'http://localhost:8080/Html2PDF/v1';
```

## How to release a new version

### Build a new version of the image
```sh
docker build -t bluespice/pdf:latest .
```

### Apply proper tags
HINT: We align the image tags with the version of BlueSpice that it is compatible with.

Example:
```sh
docker tag bluespice/pdf:latest bluespice/pdf:5
docker tag bluespice/pdf:latest bluespice/pdf:5.0
docker tag bluespice/pdf:latest bluespice/pdf:5.0.1
```

### Push the image to the registry
Example:
```sh
docker push bluespice/pdf:latest
docker push bluespice/pdf:5
docker push bluespice/pdf:5.0
docker push bluespice/pdf:5.0.1
```

## Testing
Install `trivy` and run `trivy image bluespice/pdf` to check for vulnerabilities.