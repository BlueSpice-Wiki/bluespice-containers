# BlueSpice "Diagram" service

This currently is just a regular DrawIO server.

## Using it within BlueSpice or MediaWiki

Given you have this service running at `http://localhost:8080/`, you can configure the BlueSpice diagram like this:

```php
$GLOBALS['wgDrawioEditorBackendUrl'] = 'http://localhost:8080';
```

## How to release a new version

### Build a new version of the image
```sh
docker build -t bluespice/diagram:latest .
```

### Apply proper tags
HINT: We align the image tags with the version of BlueSpice that it is compatible with.

Example:
```sh
docker tag bluespice/diagram:latest bluespice/diagram:4
docker tag bluespice/diagram:latest bluespice/diagram:4.4
docker tag bluespice/diagram:latest bluespice/diagram:4.4.1
```

### Push the image to the registry
Example:
```sh
docker push bluespice/diagram:latest
docker push bluespice/diagram:4
docker push bluespice/diagram:4.4
docker push bluespice/diagram:4.4.1
```

## Testing
Install `trivy` and run `trivy image bluespice/diagram` to check for vulnerabilities.