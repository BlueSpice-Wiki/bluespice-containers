# BlueSpice "Search" service

This is a service that provides the search backend for Extension:BlueSpiceExtendedSearch. It is currently based on OpenSearch.

## Using it within BlueSpice or MediaWiki

Given you have this service running at `http://localhost:9200`, you can configure the BlueSpice search like this:

```php
$GLOBALS['bsgESBackendTransport'] = 'http';
```

## How to release a new version

### Build a new version of the image
```sh
docker build -t bluespice/search:latest .
```

### Apply proper tags
HINT: We align the image tags with the version of BlueSpice that it is compatible with.

Example:
```sh
docker tag bluespice/search:latest bluespice/search:4
docker tag bluespice/search:latest bluespice/search:4.4
docker tag bluespice/search:latest bluespice/search:4.4.1
```

### Push the image to the registry
Example:
```sh
docker push bluespice/search:latest
docker push bluespice/search:4
docker push bluespice/search:4.4
docker push bluespice/search:4.4.1
```

## Testing
Install `trivy` and run `trivy image bluespice/search` to check for vulnerabilities.