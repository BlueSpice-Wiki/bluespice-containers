# BlueSpice Containers

$$\text{BlueSpice} = \Big((\text{wiki codebase} + \text{runtime}) + \text{services containers}\Big)_\text{arranged together}$$

The **BlueSpice Containers** project is a unified development setup for BlueSpice. It features:

- **Full picture of BlueSpice**: All components _outside_ the wiki codebase are collected into one single repo, making browse/grep easy
- **Source to containers, no blackboxes**: Build all images with one command, then run containers reflecting current source in your workspace
- **Dev setup, mostly automated**: A script wires up dev-ready configs onto the deployment stack, a few tweaks and you're ready to go.

## Quick start

### Step 1: Create directories in your workspace

```text
.
├── bluespice-containers  (clone this project, switch to target branch)
├── code                  (clone wiki codebase, build it in target branch)
└── data                  (`mkdir data`)
```

For example, `bluespice-containers` at branch `dev-5.2.x` matches BlueSpice wiki codebase built from branch `5.2.x` or dev branch `REL1_43-5.2.x`. For more on such conventions, see section [Compatibility](#compatibiliy) below.

Optionally, you might want to clone certain not-yet-published repos under `bluespice-containers/images` or `bluespice-containers/webservices`. Target subdirectories should be in `bluespice-containers/.gitignore`.

### Step 2: Build the images

```sh
cd bluespice-containers
./maintenance.sh --build --buildargs EDITION=free
```

Optionally, assume that you prepared access tokens `~/.github-token` and/or `~/.gitlab-token` (needed for `pro`, `farm` or even `galaxy` editions and certain images - modify the EDITION argument in the command according to your specific case):

```sh
GITHUB_TOKEN=$(cat ~/.github-token) GITLAB_HW_TOKEN=$(cat ~/.gitlab-token) \
./maintenance.sh --build --buildargs EDITION=farm
```

### Step 3: Configure the stack

```sh
./maintenance.sh --dev-setup
```

This script creates two files:

- `deploy/compose/.env`: you need to tweak it further:
  - `CODEDIR` and `DATADIR` should match absolute addresses of `code` and `data` in [Step 1](#step-1-create-directories-in-your-workspace)
  - `EDITION`, `DB_USER` and `DB_PASS` should be configured - read [official tutorial](https://en.wiki.bluespice.com/wiki/Setup:Installation_Guide/Docker) for richer details
- `deploy/compose/docker-compose.override.yml` works out of the box
  - Optionally you can enable advanced configs here, e.g use [xdebug](https://xdebug.org/), add packages to containers and so on.

### Step 4: Bring up the stack

```sh
cd deploy/compose
./bluespice-deploy up -d
```

Optionally:

- to run wiki on a local only host name, add it to your `/etc/hosts` file
- to run wiki in `https` protocol, add the `.key` and `.crt` certificate files of your domain name to `${DATADIR}/proxy/certs`, then run `./bluespice-deploy restart proxy` to load the certificate
- use `--build` tag for the first run to utilize inline Dockerfile in the override yml

## Compatibiliy

Every `dev-*` branch or tag of this project works _only_ with compatible branches or tags of wiki codebase of BlueSpice.

|a|b|c|
|-|-|-|
|b|c|d|

## Advanced usages

### Running tests in a wiki container

Correctly initialized BlueSpice wiki codebase with dev libraries and binaries is mandatory for this advanced usage - for deployment oriented builds, including the [official build of free edition](https://github.com/BlueSpice-Wiki/bluespice-free-release), running test is not possible - those are always built with something like `composer update --no-dev`.

For farm or galaxy installations, the following lines are also needed in the `/data/bluespice/pre-init-settings.php` (of the wiki containers) to bypass shared tables when running tests:

```php
if ( defined( 'MW_PHPUNIT_TEST' ) && MW_PHPUNIT_TEST ) {
  $GLOBALS['wgSharedTables'] = [];
}
```

1. Run `./bluespice-deploy up -d --build` for once, so that composer is added to your wiki containers
2. Inside a wiki container (e.g `./bluespice-deploy exec -it wiki-web bash`), go to `/app/bluespice/w`
3. One can then run specific PHPUnit tests like `composer phpunit extensions/WikiRAG/tests/phpunit/integration/`
4. To run full `composer test` for a specific extension or skin, symlink the mediawiki vendor with `ln -s ../../vendor vendor` first (operate on the host machine if needed). Then go to target extensions or skin inside a wiki container and run `composer test`.

### Controlling the Docker containers with `bsc`

`bsc` is a helper script to control containers in your current stack.
As script wrapping of `deploy/compose/bluespice-deploy`, which is itself a wrapper of `docker compose`, `bsc` can be called from everywhere - hence one can symlink it into somewhere in `$PATH`.

```sh
cd /usr/local/bin
ln -s $INSTALL_DIR/bin/bsc
```

When having multiple sets of `bluespice-containers` installed on one host, one can consider symlinking the `bsc` of each to a different alias. Most frequent usages of this script are:

- `bsc boot` (alias of `bsc up -d`) brings up all service containers, and `bsc down` put down everything.
- `bsc exec wiki-task bash` connects to the shell of the wiki-task container - same for other containers (some only accept `sh`).
- `bsc cp $(pwd)/file.txt wiki-task:/tmp/`, for example, copies `./file.txt` into the `wiki-task` container. _Always use absolute path._
- `bsc edit-env` launches an editor with your `deploy/compose/.env` file. One can quickly change aspects of your setup, e.g., the data
directory.
  - For most changes in `deploy/compose/docker-compose.override.yml` or `deploy/compose/.env`, running `bsc restart` re-applies those changes.Though sometimes one needs to bring down the affected containers or the whole stack, then go up again to reflect the changes.
