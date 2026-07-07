# BlueSpice "Cache" service

This currently is just a regular Memcached server. In the future we may switch to another backend like Redis.

## Using it within BlueSpice or MediaWiki

Given you have this service running at `localhost:11211`, you can configure the BlueSpice cache like this:

```php
$GLOBALS['wgMemCachedServers'] = [ 'localhost:11211' ];
$GLOBALS['wgMainCacheType'] = CACHE_MEMCACHED;
$GLOBALS['wgSessionCacheType'] = CACHE_DB;
```

HINT: It is not recommended to store user sessions in Memcached, as it may cause session loss.

## Configuration

By default, the container runs memcached with 512MB max memory and optimized slab management:

```
memcached -m 512 -o slab_reassign,slab_automove=2
```

You can override these defaults using the `command` parameter in your `docker-compose.yml`:

```yaml
services:
  cache:
    image: bluespice/cache:latest
    command: ["memcached", "-m", "1024", "-o", "slab_reassign,slab_automove=2"]
```

This replaces the entire default command, so include all desired flags when overriding.

## How to release a new version

### Build a new version of the image
```sh
docker build -t bluespice/cache:latest .
```

### Apply proper tags
HINT: We align the image tags with the version of BlueSpice that it is compatible with.

Example:
```sh
docker tag bluespice/cache:latest bluespice/cache:4
docker tag bluespice/cache:latest bluespice/cache:4.4
docker tag bluespice/cache:latest bluespice/cache:4.4.1
```

### Push the image to the registry
Example:
```sh
docker push bluespice/cache:latest
docker push bluespice/cache:4
docker push bluespice/cache:4.4
docker push bluespice/cache:4.4.1
```

## Testing
Install `trivy` and run `trivy image bluespice/cache` to check for vulnerabilities.