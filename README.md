# BlueSpice "Formula" service

This service hosts a Mathoid server, that can be used with the CLI configuration of Extension:Math.

## Using it within BlueSpice or MediaWiki

Given you have copied `_client/mathoid-remote` from this repository to `/usr/local/bin/` of your server and you have this service running at `http://localhost:10044/`, you can configure the Math extension like this:

```php
$GLOBALS['wgMathoidCli'] = [
	'/usr/local/bin/mathoid-remote',
	'http://localhost:10044/'
];
```

## Testing locally

Run `curl` like this:
```sh
curl \
	-X POST "http://localhost:10044/" \
	-d "q={\\displaystyle \\overbrace{ 1+2+\\cdots+100 }^{5050}}&type=tex"

curl \
	-X POST "http://localhost:10044/" \
	-d "q={\\displaystyle \\ce{CO2 + C -> 2 CO}}&type=chem"
```

## How to release a new version

### Build a new version of the image
```sh
docker build -t bluespice/formula:latest .
```

### Apply proper tags
HINT: We align the image tags with the version of BlueSpice that it is compatible with.

Example:
```sh
docker tag bluespice/formula:latest bluespice/formula:4
docker tag bluespice/formula:latest bluespice/formula:4.4
docker tag bluespice/formula:latest bluespice/formula:4.4.1
```

### Push the image to the registry
Example:
```sh
docker push bluespice/formula:latest
docker push bluespice/formula:4
docker push bluespice/formula:4.4
docker push bluespice/formula:4.4.1
```

## Testing
Install `trivy` and run `trivy bluespice/formula:latest` to check for vulnerabilities.
