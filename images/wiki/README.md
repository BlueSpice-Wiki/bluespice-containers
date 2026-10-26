## Build

To pull in the required codebases access tokens need to be provided. While most required codebases are hosted on GitHub and can therefore use the same access token, at least some editions of the BlueSpice codebase may be hosted on a private GitLab instance.
For the free edition the GitHub can be used for both.

Example FREE edition build command:

```bash
docker build -t bluespice/wiki:latest .
```

Example custom edition build command:

Assuming you have a valid access token for <url-to-custom-edition-repo> stored in `~/gitlab-token.txt`, you can run

```bash
GIT_AUTH_TOKEN=$(cat ~/gitlab-token.txt) \
docker build \
	--secret id=GIT_AUTH_TOKEN \
	--build-arg BLUESPICE_URL=<url-to-custom-edition-repo> \
	--build-arg BLUESPICE_VERSION=<branch-or-tag> \
	-t bluespice/wiki:latest .
```

## Infrastructure configuration of the codebase

The container can run different codebases (products/editions, custom builds). A codebase may contain several editions (e.g. PRO and FARM). Each image is built for exactly one edition:

- `INTERNAL_PRODUCT` is taken from `BLUESPICE-PRODUCT` of the codebase. A build arg `INTERNAL_PRODUCT` is only used to verify it.
- `INTERNAL_EDITION` is taken from the build arg `INTERNAL_EDITION`. Without build arg, `BLUESPICE-EDITION` (the default edition of the codebase) is used.
- All other values are taken from `_bluespice/.env.infrastructure.<edition>` of the codebase. The available editions are the existing files. They can be overridden with build args of the same name (e.g. for custom builds).

`/app/bin/init-infrastructure-envs` writes the values at build time to `/app/.env.infrastructure.build` and on container start to `/app/.env.infrastructure`:

    container defaults < `_bluespice/.env.infrastructure.<edition>` < `--build-arg` < runtime environment (deployment dependent values only)

Values that define the edition can not be changed at runtime. Attempts are ignored with a warning. Only deployment dependent values (column "Runtime") can be overridden with environment variables of the container.

The build fails if the selected edition is not available, if a value is invalid (e.g. `INTERNAL_ENABLE_FARM=yes`) or if `INTERNAL_BOOTSTRAPER` does not exist. The same checks apply on container start.

| Variable                                   | Default                              | Runtime | Description |
|--------------------------------------------|--------------------------------------|---------|-------------|
| `INTERNAL_PRODUCT`                         | `classic`                            | No      | Product, e.g. `classic`, `galaxy` |
| `INTERNAL_EDITION`                         | `free`                               | No      | Edition, e.g. `free`, `pro`, `farm`, `sky` |
| `INTERNAL_PARALLEL_RUNJOBS_SERVICE_MODE`   | `standalone`                         | No      | `wiki.type` of `parallel-runjobs-service` |
| `INTERNAL_ENABLE_FARM`                     | `false`                              | No      | Enables the wiki farm (`LocalSettings.d/Farm.Base.pre.php`) |
| `INTERNAL_ENABLE_FARM_SHARED_SEARCH`       | `false`                              | No      | Unified search over all farm instances |
| `INTERNAL_ENABLE_FARM_SHARED_USER_SESSION` | `false`                              | No      | Shared users, user sessions and global access control |
| `INTERNAL_ENABLE_FARM_SHARED_DATABASE`     | `false`                              | No      | Additional shared database tables |
| `INTERNAL_ENABLE_FARM_SHARED_JOBQUEUE`     | `false`                              | Yes     | Redis/Valkey job queue. Requires `JOBQUEUE_HOST` |
| `INTERNAL_FARM_INSTANCE_LIMIT`             | `''`                                 | No      | Maximum number of farm instances (sub-wikis). Empty or `0` means unlimited. Not bound to an edition, set it by build arg, e.g. `--build-arg INTERNAL_FARM_INSTANCE_LIMIT=30` |
| `INTERNAL_BOOTSTRAPER`                     | `/app/conf/StandardBootstrapper.php` | No      | Bootstrapper of the codebase. Farms use `/app/bluespice/w/extensions/BlueSpiceWikiFarm/WikiFarm.setup.php`, which includes the Standard Bootstrapper through `$wgWikiFarmConfig_LocalSettingsAppendPath` |

`INTERNAL_ENABLE_FARM_*` flags only have an effect if `INTERNAL_ENABLE_FARM=true`.

`INTERNAL_PRODUCT` and `INTERNAL_EDITION` are available as ENV variables to every PHP process of the container (web, CLI, `docker exec`), see `/app/conf/init.php`. The codebase should use them instead of its `BLUESPICE-PRODUCT`/`BLUESPICE-EDITION` files, because `BLUESPICE-EDITION` is only the default edition of a codebase that may serve several editions:

```php
$edition = getenv( 'INTERNAL_EDITION' ) ?: trim( file_get_contents( "$IP/BLUESPICE-EDITION" ) );
```

Example: Build a FARM image from a codebase that contains PRO (default edition) and FARM:

```bash
docker build --build-arg INTERNAL_EDITION=farm -t bluespice/wiki:farm .
```

Example: Disable the shared job queue of a FARM image, e.g. if the stack has no Redis/Valkey service:

```bash
docker run -e INTERNAL_ENABLE_FARM_SHARED_JOBQUEUE=false bluespice/wiki:farm
```

`/app/conf/LocalSettings.php` includes the files from `/app/conf/LocalSettings.d/` based on these flags.

All settings files (including `WIKI_PRE_INIT_SETTINGS_FILE`, `WIKI_POST_INIT_SETTINGS_FILE`, `LocalSettings.custom.php` of farm instances and `_bluespice/settings.d/*.php` of the codebase) are included within the scope of a closure. They must use `$GLOBALS['wgSomething']` instead of `$wgSomething`, and `MW_INSTALL_PATH` instead of `$IP`:

```php
<?php
$GLOBALS['wgSomething'] = 'value';          // Works
$GLOBALS['wgHooks']['SomeHook'][] = ...;    // Works
$wgSomething = 'value';                     // Silently ignored!
```

## ENV vars

| Variable                     | Default Value  | Description                                          | Optional |
|------------------------------|----------------|------------------------------------------------------|----------|
| `AV_HOST`                    | `antivirus`    | Hostname of a `clamav` compatible service            | Yes      |
| `AV_PORT`                    | `3310`         | Port of a `clamav` compatible service                | Yes      |
| `CACHE_HOST`                 | `cache`        | Hostname of a `bluespice/cache` compatible service *)| Yes      |
| `CACHE_PORT`                 | `11211`/`6379` | Port of a `bluespice/cache` compatible service. Default based on `CACHE_TYPE` | Yes      |
| `CACHE_TYPE`                 | `memcached`    | Type of cache to use (memcached or redis/valkey)    | Yes      |
| `CHAT_HOST`                  | `chat`         | Hostname of a `bluespice/chat` compatible service ****)| Yes      |
| `CHAT_PORT`                  | `3000`         | Port of a `bluespice/chat` compatible service        | Yes      |
| `CHAT_PROTOCOL`              | `http`         | Protocol of a `bluespice/chat` compatible service    | Yes      |
| `DB_HOST`                    | `database`     | Database host                                        | Yes      |
| `DB_NAME`                    | `bluespice`    | Database name                                        | Yes      |
| `DB_PASS`                    | `null`         | Database password                                    | No       |
| `DB_PREFIX`                  | `''`           | Database prefix **)                                  | Yes      |
| `DB_ROOT_PASS`               | ``             | Database root password **)                           | Yes      |
| `DB_ROOT_USER`               | `root`         | Database root user                                   | Yes      |
| `DB_TYPE`                    | `mysql`        | Database type                                        | Yes      |
| `DB_USER`                    | `bluespice`    | Database user                                        | Yes      |
| `DEV_WIKI_DEBUG`             | `null`         | Enable debug mode                                    | Yes      |
| `DEV_WIKI_DEBUG_LOGCHANNELS` | `null`         | Debug log channels, comma separated                  | Yes      |
| `DIAGRAM_HOST`               | `$WIKI_HOST`   | Hostname of a `bluespice/diagram` compatible service | Yes      |
| `DIAGRAM_PATH`               | `/_diagram/`   | Path of a `bluespice/diagram` compatible service     | Yes      |
| `DIAGRAM_PORT`               | `$WIKI_PORT`   | Port of a `bluespice/diagram` compatible service     | Yes      |
| `DIAGRAM_PROTOCOL`           | `$WIKI_PROTOCOL`| Protocol of a `bluespice/diagram` compatible service| Yes      |
| `FILESTORE_HOST`            | `-`            | Hostname of a `bluespice/filestore` compatible service. `-` disables it | Yes      |
| `FILESTORE_ACCESS_KEY`      | `''`         | Access key for `bluespice/filestore` compatible service | No, if `FILESTORE_HOST` is set       |
| `FILESTORE_SECRET_KEY`    | `''`         | Secret key for `bluespice/filestore` compatible service | No, if `FILESTORE_HOST` is set       |
| `FILESTORE_BUCKET_NAME`    | `<wikiid>`         | Bucket name for `bluespice/filestore` compatible service | Yes      |
| `FILESTORE_PORT`           | `9000`             | Port of a `bluespice/filestore` compatible service       | Yes      |
| `FILESTORE_PROTOCOL`       | `http`             | Protocol of a `bluespice/filestore` compatible service   | Yes      |
| `FILESTORE_REGION`         | `eu-north-1`        | AWS region for `bluespice/filestore` compatible service  | Yes      |
| `FILESTORE_TOKEN`          | `''`               | Security token for `bluespice/filestore` compatible service | Yes   |
| `FORMULA_HOST`               | `formula`      | Hostname of a `bluespice/formula` compatible service | Yes      |
| `FORMULA_PORT`               | `10044`        | Port of a `bluespice/formula` compatible service     | Yes      |
| `FORMULA_PROTOCOL`           | `http`         | Protocol of a `bluespice/formula` compatible service | Yes      |
| `INTERNAL_CHAT_TOKEN`        | `null`         | Internal service token for `bluespice/chat`              | No, if `CHAT_HOST` is set |
| `INTERNAL_CHAT_WIKI_ACCESS_TOKEN` | `null` | Access token `bluespice/chat` | No       |
| `INTERNAL_SIMPLESAMLPHP_SESSION_API_TOKEN`| `null`         | Token for SAML session API endpoint. Auto-generated during installation | No      |
| `INTERNAL_WIKI_SECRETKEY`    | `null`         | Secret key for the wiki                              | No       |
| `INTERNAL_WIKI_TOKEN_AUTH_SALT` | `null`      | Salt for token-based service authentication              | No       |
| `INTERNAL_WIKI_UPGRADEKEY`   | `null`         | Upgrade key for the wiki                                 | No       |
| `INTERNAL_WIRE_API_KEY`      | `null`         | API key for `bluespice/wire` service                     | No, if `WIRE_HOST` is set |
| `JOBQUEUE_HOST`              | `null`         | Hostname of a `bluespice/jobqueue` compatible service. Only used with `INTERNAL_ENABLE_FARM_SHARED_JOBQUEUE=true` | Yes      |
| `JOBQUEUE_PORT`              | `6379`         | Port of a `bluespice/jobqueue` compatible service          | Yes      |
| `MAX_UPLOAD_SIZE`            | `1024m`        | Max upload size for single file (Allowed: m or g)    | Yes      |
| `METADATASTORE_PROTOCOL`     | `http`         | Protocol of a `bluespice/metadatastore` compatible service      | Yes      |
| `METADATASTORE_HOST`         | `metadatastore`| Hostname of a `bluespice/metadatastore` compatible service      | Yes      |
| `METADATASTORE_PORT`         | `3306`         | Port of a `bluespice/metadatastore` compatible service          | Yes      |
| `METADATASTORE_USER`         | `null`         | User of a `bluespice/metadatastore` compatible service          | Yes      |
| `METADATASTORE_PASS`         | `null`         | Password of a `bluespice/metadatastore` compatible service      | No       |
| `PDF_HOST`                   | `pdf`          | Hostname of a `bluespice/pdf` compatible service     | Yes      |
| `PDF_PORT`                   | `8080`         | Port of a `bluespice/pdf` compatible service         | Yes      |
| `PDF_PROTOCOL`               | `http`         | Protocol of a `bluespice/pdf` compatible service     | Yes      |
| `SAML_LOG_LEVEL`             | `$WIKI_LOG_LEVEL` | Log level for SAML operations.                    | Yes      |
| `SAML_SESSION_API_ALLOWED`   | `''`           | Additional allowed CIDR range for SAML session API endpoint beside `127.0.0.1/32` | Yes      |
| `SEARCH_HOST`                | `search`       | Hostname of a `bluespice/search` compatible service  | Yes      |
| `SEARCH_PASS`                | ``             | Password of a `bluespice/search` compatible service  | Yes      |
| `SEARCH_PORT`                | `9200`         | Port of a `bluespice/search` compatible service      | Yes      |
| `SEARCH_PROTOCOL`            | `http`         | Protocol of a `bluespice/search` compatible service  | Yes      |
| `SEARCH_USER`                | ``             | User of a `bluespice/search` compatible service      | Yes      |
| `SMTP_HOST`                  | `null`         | SMTP host                                            | Yes      |
| `SMTP_IDHOST`                | `null`         | SMTP ID host                                         | Yes      |
| `SMTP_PASS`                  | `null`         | SMTP password                                        | Yes      |
| `SMTP_PORT`                  | `25`           | SMTP port                                            | Yes      |
| `SMTP_USER`                  | `null`         | SMTP username                                        | Yes      |
| `TZ`                         | `UTC`          | Timezone for BlueSpice and container system time     | Yes      |
| `WIKI_BASE_PATH`             | `''`           | Base path for the wiki. Must be aligned with proxy   | Yes      |
| `WIKI_ARTICE_PATH`           | `wiki`         | Definition of $wgArticlePath for pretty URLs ***)    | Yes      |
| `FARM_DEFAULT_INSTANCE`      | `''`           | Farm instance to redirect to by default ***)         | Yes      |
| `WIKI_EMERGENCYCONTACT`      | `''`           | Emergency contact email                              | No       |
| `WIKI_FARM_DB_PREFIX`        | `sfr_`         | Database name prefix for wiki farm instances **)     | Yes      |
| `WIKI_FARM_USE_SHARED_DB`    | `null`         | Store wiki farm instances in `DB_NAME` **)           | Yes      |
| `WIKI_HOST`                  | `localhost`    | Host for the wiki                                    | Yes      |
| `WIKI_INITIAL_ADMIN_PASS`    | `null`         | Initial admin password. Uses random, if not set      | Yes      |
| `WIKI_INITIAL_ADMIN_USER`    | `Admin`        | Admin user name use during initial installation      | Yes      |
| `WIKI_LANG`                  | `en`           | Language code for the wiki                           | Yes      |
| `WIKI_LOG_LEVEL`             | `error`        | Set php and Nginx log level (error/warn/debug)       | Yes      |
| `WIKI_NAME`                  | `BlueSpice`    | Name of the wiki                                         | Yes      |
| `WIKI_OAUTH2_PRIVATE_KEY_FILE` | `null`       | Path to OAuth2 private key file                          | Yes      |
| `WIKI_OAUTH2_PUBLIC_KEY_FILE`  | `null`       | Path to OAuth2 public key file                           | Yes      |
| `WIKI_PASSWORDSENDER`        | `no-reply@$WIKI_HOST` | Password sender email                         | Yes      |
| `WIKI_PORT`                  | `443`          | Port for the wiki                                    | Yes      |
| `WIKI_PROTOCOL`              | `https`        | Protocol for the wiki                                | Yes      |
| `WIKI_PROXY`                 | `null`         | IP address(es) of proxy server. Will fall back to `proxy` service of `bluespice-deploy` | Yes      |
| `WIKI_SERVICE_TOKEN_AUTH_ALLOWED` | `null`    | CIDR range allowed to use service token authentication   | Yes      |
| `WIKI_STATUSCHECK_ALLOWED`   | `null`         | IP or CIDR range for status check REST endpoint      | Yes      |
| `WIKI_SUBSCRIPTION_KEY`      | `null`         | Only used by PRO edition. Overrides in-app config    | Yes      |
| `WIRE_HOST`                  | `wire`         | Hostname of a `bluespice/wire` compatible service    | Yes      |
| `WIRE_PORT`                  | `3333`         | Port of a `bluespice/wire` compatible service        | Yes      |
| `WIRE_PROTOCOL`              | `http`         | Protocol of a `bluespice/wire` compatible service    | Yes      |

*) External cache can be disabled by setting `-` as `CACHE_HOST`.

**) See section "Database requirements for FARM edition"

***) For a farm with non-default `WIKI_ARTICLE_PATH`, set `FAMR_DEFAULT_INSTANCE` as well, e.g `w` the root farm instance.

****) Functions requiring `bluespice/chat` can be disabled by setting `-` as `CHAT_HOST`.

## Directories and Volumes

The main directory for application data is `/data/`. The setup routine will create the following directories:

```
.
├── bluespice
│   ├── extensions
│   │   └── BlueSpiceFoundation
│   ├── farm-archive             -> Contains data of "deleted" FARM instances
│   ├── farm-instances           -> Contains data of FARM instances
│   ├── images
│   ├── logs
│   │   ├── postupdate
│   │   └── baseversion          -> Stores the application version at the last container start. Used to determine if update scripts need to run.
│   │
│   ├── oauth_private.key        -> Automatically created during installation. Used by Extension:OAuth
│   ├── oauth_public.key         -> Automatically created during installation. Used by Extension:OAuth
│   │
│   ├── post-init-settings.php   -> Automatically created during installation. May contain additional settings.
│   └── pre-init-settings.php    -> Automatically created during installation. May contain additional settings.
│
├── simplesamlphp
│   ├── cache
│   ├── certs                    -> Automatically created during installation. Used by SimpleSAMLphp
│   │   ├── saml.crt
│   │   └── saml.pem
│   ├── data
│   ├── logs
│   └── saml_idp_metadata.xml    -> Needs to be created manually, in case SAML authentication is used.
|
├── initialAdminPassword         -> Automatically created during installation.
└── .wikienv                     -> Automatically created during installation. Contains various keys.
```

## Custom content import
If the directory `/data/bluespice/content-install` exists during installation, the content found in there will be imported into the wiki.

Example:

```
/data/bluespice/content-install
├── files
│   ├── HappyGnu.jpg
│   └── Desiderata.pdf
├── 01-templates.xml
└── 02-pages.xml
```

## Custom SSL certificates

If the application needs to connect to external services that use self-signed certificates, make sure to add those to the `/etc/ssl/certs/ca-certificates.crt` of the **hostmachine** and then mount it into the container like this:

```yaml
volumes:
  - /etc/ssl/certs/ca-certificates.crt:/etc/ssl/certs/ca-certificates.crt:ro
```

## Profiling

The image contains the Excimer profiler, which can be used in production scenarios. To enable it, set `_profiler=trace` or `_profiler=speedscope` in
- the query string (GET parameter) of the URL
- the POST body of the request
- a cookie

Results will be stored in `/data/bluespice/logs`. JSON files can be viewed with the [Speedscope](https://www.speedscope.app/) viewer. LOG files can be processed with https://github.com/brendangregg/FlameGraph

See https://www.mediawiki.org/wiki/Excimer and https://techblog.wikimedia.org/2021/03/03/profiling-php-in-production-at-scale for details.

## Database requirements for FARM edition

If `DB_USER` is not allowed to create databases and the database with `DB_NAME` does not exist yet, you need to provide a `DB_ROOT_USER` and `DB_ROOT_PASS` during installation.

In addition, the `FARM` edition will need to have a user that is allowed to create databases with the `WIKI_FARM_DB_PREFIX` as prefix.
This user can be the same as `DB_USER` or a different one.

If you want to use a different user, you need to provide the `DB_ROOT_USER` and `DB_ROOT_PASS` during installation.

If `WIKI_FARM_USE_SHARED_DB` is set to `1`, the `FARM` edition will store the wiki instance dbs in `DB_NAME` itself and `DB_USER` does not need permissions to create further databases with the `WIKI_FARM_DB_PREFIX` prefix.

# Liveness and readiness probes
The container exposes the following commands for liveness and readiness probes:
- `probe-liveness` - Exists with 0 if the application is healthy
- `probe-readiness <type>` - Value of `<type>` can be `web` or `task`. Exists with 0 if the application is ready
