# BlueSpice MediaWiki

<img style="display:block;margin:auto" src="./root-fs/var/www/html/Bluespice_Icon.svg" width="100" height="100" alt="BlueSpice MediaWiki" />

## Build

```bash
docker build -t bluespice/wiki:latest .
```

## ENV vars

| Variable                     | Default Value  | Description                                          | Optional |
|------------------------------|----------------|------------------------------------------------------|----------|
| `AV_HOST`                    | `antivirus`    | Hostname of a `clamav` compatible service            | Yes      |
| `AV_PORT`                    | `3310`         | Port of a `clamav` compatible service                | Yes      |
| `BACKUP_HOUR`                | `1`            | Hour for daily backup. Set to `-1` to disable        | Yes      |
| `CACHE_HOST`                 | `cache`        | Hostname of a `bluespice/cache` compatible service *)| Yes      |
| `CACHE_PORT`                 | `11211`        | Port of a `bluespice/cache` compatible service       | Yes      |
| `DB_HOST`                    | `database`     | Database host                                        | Yes      |
| `DB_NAME`                    | `bluespice`    | Database name                                        | Yes      |
| `DB_PASS`                    | `null`         | Database password                                    | No       |
| `DB_PREFIX`                  | `''`           | Database prefix **)                                  | Yes      |
| `DB_ROOT_PASS`               | ``             | Database root password **)                           | Yes      |
| `DB_ROOT_USER`               | ``             | Database root user                                   | Yes      |
| `DB_TYPE`                    | `mysql`        | Database type                                        | Yes      |
| `DB_USER`                    | `bluespice`    | Database user                                        | Yes      |
| `DEV_WIKI_DEBUG`             | `null`         | Enable debug mode                                    | Yes      |
| `DEV_WIKI_DEBUG_LOGCHANNELS` | `null`         | Debug log channels, comma separated                  | Yes      |
| `DIAGRAM_HOST`               | `$WIKI_HOST`   | Hostname of a `bluespice/diagram` compatible service | Yes      |
| `DIAGRAM_PATH`               | `/_diagram/`   | Path of a `bluespice/diagram` compatible service     | Yes      |
| `DIAGRAM_PORT`               | `$WIKI_PORT`   | Port of a `bluespice/diagram` compatible service     | Yes      |
| `DIAGRAM_PROTOCOL`           | `$WIKI_PROTOCOL`| Protocol of a `bluespice/diagram` compatible service| Yes      |
| `EDITION`                    | `null`         | Edition of the wiki                                  | Yes      |
| `FORMULA_HOST`               | `formula`      | Hostname of a `bluespice/formula` compatible service | Yes      |
| `FORMULA_PORT`               | `10044`        | Port of a `bluespice/formula` compatible service     | Yes      |
| `FORMULA_PROTOCOL`           | `http`         | Protocol of a `bluespice/formula` compatible service | Yes      |
| `INTERNAL_WIKI_SECRETKEY`    | `null`         | Secret key for the wiki                              | No       |
| `INTERNAL_WIKI_UPGRADEKEY`   | `null`         | Upgrade key for the wiki                             | No       |
| `PDF_HOST`                   | `pdf`          | Hostname of a `bluespice/pdf` compatible service     | Yes      |
| `PDF_PORT`                   | `8080`         | Port of a `bluespice/pdf` compatible service         | Yes      |
| `PDF_PROTOCOL`               | `http`         | Protocol of a `bluespice/pdf` compatible service     | Yes      |
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
| `WIKI_EMERGENCYCONTACT`      | `''`           | Emergency contact email                              | No       |
| `WIKI_FARM_DB_PREFIX`        | `sfr_`         | Database name prefix for wiki farm instances **)     | Yes      |
| `WIKI_FARM_USE_SHARED_DB`    | `null`         | Store wiki farm instances in `DB_NAME` **)           | Yes      |
| `WIKI_HOST`                  | `localhost`    | Host for the wiki                                    | Yes      |
| `WIKI_INITIAL_ADMIN_PASS`    | `null`         | Initial admin password. Uses random, if not set      | Yes      |
| `WIKI_INITIAL_ADMIN_USER`    | `Admin`        | Admin user name use during initial installation      | Yes      |
| `WIKI_LANG`                  | `en`           | Language code for the wiki                           | Yes      |
| `WIKI_LOG_LEVEL`             | `error`        | Set php and Nginx log level (error/warn/debug)       | Yes      |
| `WIKI_NAME`                  | `BlueSpice`    | Name of the wiki                                     | Yes      |
| `WIKI_PASSWORDSENDER`        | `no-reply@$WIKI_HOST` | Password sender email                         | Yes      |
| `WIKI_PORT`                  | `443`          | Port for the wiki                                    | Yes      |
| `WIKI_PROTOCOL`              | `https`        | Protocol for the wiki                                | Yes      |
| `WIKI_PROXY`                 | `null`         | IP address(es) of proxy server. Will fall back to `proxy` service of `bluespice-deploy` | Yes      |
| `WIKI_SUBSCRIPTION_KEY`      | `null`         | Only used by PRO edition. Overrides in-app config    | Yes      |
| `WIKI_STATUSCHECK_ALLOWED`   | `null`         | IP or CIDR range for status check REST endpoint      | Yes      |

*) External cache can be disabled by setting `-` as `CACHE_HOST`.
**) See section "Database requirements for FARM edition"

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
