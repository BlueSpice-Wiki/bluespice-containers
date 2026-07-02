# BlueSpice Containers

$$\text{BlueSpice} = \Big((\text{wiki codebase} + \text{runtime}) + \text{services containers}\Big)_\text{arranged together}$$

The **BlueSpice Containers** project is an unified development setup for BlueSpice. It features: 
- **Full picture of BlueSpice**: All components _outside_ the wiki codebase are collected into one single repo, making browse/grep easy
- **Source to containers, no blackboxes**: Build all images with one command, then run containers reflecting current source in your workspace
- **Dev setup, mostly automated**: A script wires up dev-ready configs onto the deployment stack, a few tweaks and you're ready to go.

## Quick start
### Step 1: Create directories in your workspace
```
.
├── bluespice-containers  (clone this project, switch to target branch)
├── code                  (clone wiki codebase, build it in target branch)
└── data                  (`mkdir data`)
```
Optionally, you might want to clone certain not-yet-published repos under `bluespice-containers/images` or `bluespice-containers/webservices`. Target subdirectories should be in `bluespice-containers/.gitignore`.
### Step 2: Build the images
```sh
cd bluespice-containers
./maintenance.sh --build --buildargs EDITION=free
```
Optionally, assume that you prepared access tokens `~/.github-token` and/or `~/.gitlab-token` (needed for `pro`, `farm` editions and certain images):
```sh
GITHUB_TOKEN=$(cat ~/.github-token) GITLAB_HW_TOKEN=$(cat ~/.gitlab-token) \
./maintenance.sh --build --buildargs EDITION=farm
```
### Step 3: Configure the stack
```
./maintenance.sh --dev-setup
```
This script creates two files:
- `deploy/compose/.env`: you need to tweak it further:
  - `CODEDIR` and `DATADIR` should match absolute addresses of `code` and `data` in [Step 1](#step-1-create-directories-in-your-workspace)
  - `EDITION`, `DB_USER` and `DB_PASS` should be configured - read [official tutorial](https://en.wiki.bluespice.com/wiki/Setup:Installation_Guide/Docker) for richer details
- `deploy/compose/docker-compose.override.yml` works out of the box
  - Optionally you can enable advanced configs here, e.g use [xdebug](https://xdebug.org/), add packages to containers and so on. 
### Step 4: Bring up the stack
```
cd deploy/compose
./bluespice-deploy up -d
```
Optionally:
- to run wiki on a local only host name, add it to your `/etc/hosts` file
- to run wiki in `https` protocol, add the `.key` and `.crt` certificate files of your domain name to `${DATADIR}/proxy/certs`, then run `./bluespice-deploy restart proxy` to load the certificate
- use `--build` tag for the first run to utilize inline Dockerfile in the override yml

## Advanced usages

### Run PHPUnit tests inside a wiki container
1. Run `./bluespice-deploy up -d --build` for once, so that composer is added to your wiki containers
2. If you are running farm edition, please add the following lines to your `pre-init-settings.php`:
```php
if ( defined( 'MW_PHPUNIT_TEST' ) && MW_PHPUNIT_TEST ) {
	$GLOBALS['wgSharedTables'] = [];
}
```
3. Inside a wiki container (e.g `./bluespice-deploy exec -it wiki-web bash`), go to `/app/bluespice/w`
4. Run your tests, for example `composer phpunit extensions/WikiRAG/tests/phpunit/integration/`


