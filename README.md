# BlueSpice Containers

$$\text{BlueSpice} = \Big((\text{wiki codebase} + \text{runtime}) + \text{services containers}\Big)_\text{arranged together}$$

The **BlueSpice Containers** project is a unified development setup for BlueSpice. It features:

- **Full picture of BlueSpice**: All components _outside_ the wiki codebase are [collected](#components-of-the-project) into this single project, making browse/grep easy
- **Source to containers, no blackboxes**: [One-command-build](#step-2-build-the-images) of all images, then run containers reflecting your current workspace
- **Dev setup, mostly automated**: [Wire dev-ready configs](#step-3-configure-the-stack) to the stack, a few tweaks then it's ready to launch

## Quick Start

### Step 1: Create workspace and subdirectories

```text
.
├── bluespice-containers  (clone this project, switch to target branch)
├── code                  (clone wiki codebase, build it in target branch)
└── data                  (`mkdir data`)
```

- Choice of the workspace directory:
  - If more than one installations of BlueSpice is planned on your computer, please read [this guide](#having-multiple-installations-on-one-computer).
- Choice of target branches:
  - E.g. to develop BlueSpice 5.2: use default branch `dev-5.2.x`, then clone and build wiki codebase at its dev branch `REL1_43-5.2.x`.
  - Find more supported combinations in the [Compatibility Policy](#branches-tags-and-compatibility-policy) section.
- Optionally, clone [not-yet-published components](#additional-repos-to-clone).

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
  - `CODEDIR` and `DATADIR` should match absolute addresses of `code` and `data` in [Step 1](#step-1-create-workspace-and-subdirectories)
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

## Branches and Components

### Branches, tags and compatibility policy

Each `dev-*` branch/tag of this project corresponds to a planned/released version of Bluespice, and is only compatible with corresponding branches or tags of wiki codebase of BlueSpice.

|`dev-*`|_raw_ wiki codebase|_built_ wiki codebase|BlueSpice versions, planned or released|
|-|-|-|-|
|[`dev-5.1.x`](https://github.com/BlueSpice-Wiki/bluespice-containers/tree/dev-5.1.x)|`REL1_43-5.1.x` [free](https://github.com/hallowelt/mediawiki/tree/REL1_43-5.1.x)/[pro](https://gitlab.hallowelt.com/BlueSpice/mediawiki/-/tree/REL1_43-5.1.x)|`5.1.x` [free](https://github.com/BlueSpice-Wiki/bluespice-free-release/tree/5.1.x)/[pro](https://gitlab.hallowelt.com/bluespicebuilds/build-pro/-/tree/5.1.x)|planned patch version for BlueSpice 5.1 LTS|
|[**`dev-5.2.x`**](https://github.com/BlueSpice-Wiki/bluespice-containers/tree/dev-5.2.x)|`REL1_43-5.2.x` [free](https://github.com/hallowelt/mediawiki/tree/REL1_43-5.2.x)/[pro](https://gitlab.hallowelt.com/BlueSpice/mediawiki/-/tree/REL1_43-5.2.x)|`5.2.x` [free](https://github.com/BlueSpice-Wiki/bluespice-free-release/tree/5.2.x)/[pro](https://gitlab.hallowelt.com/bluespicebuilds/build-pro/-/tree/5.2.x)|planned patch version for BlueSpice 5.2 (current minor)|
|[`dev-5.3.x`](https://github.com/BlueSpice-Wiki/bluespice-containers/tree/dev-5.3.x)|`REL1_43` [free](https://github.com/hallowelt/mediawiki/tree/REL1_43)/[pro](https://gitlab.hallowelt.com/BlueSpice/mediawiki/-/tree/REL1_43)|`5.3.x` [pro](https://gitlab.hallowelt.com/bluespicebuilds/build-pro/-/tree/5.3.x)|planned minor version of BlueSpice|
|[`dev-galaxy`](https://github.com/BlueSpice-Wiki/bluespice-containers/tree/dev-galaxy)|`main` [galaxy](https://github.com/BlueSpice-Wiki/bluespice-galaxy/tree/main)|N/A|planned version BlueSpice Galaxy|
|[`dev-5.1.10`](https://github.com/BlueSpice-Wiki/bluespice-containers/tree/dev-5.1.10)|`5.1.x` [free](https://github.com/BlueSpice-Wiki/bluespice-free-release/tree/5.1.x)/[pro](https://gitlab.hallowelt.com/bluespicebuilds/build-pro/-/tree/5.1.x)|`5.1.10` [free](https://github.com/BlueSpice-Wiki/bluespice-free-release/tree/5.1.10)/[pro](https://gitlab.hallowelt.com/bluespicebuilds/build-pro/-/tree/5.1.10)|5.1.10 (latest released 5.1 LTS version)|
|[`dev-5.2.6`](https://github.com/BlueSpice-Wiki/bluespice-containers/tree/dev-5.2.6)|`5.2.x` [free](https://github.com/BlueSpice-Wiki/bluespice-free-release/tree/5.2.x)/[pro](https://gitlab.hallowelt.com/bluespicebuilds/build-pro/-/tree/5.2.x)|`5.2.6` [free](https://github.com/BlueSpice-Wiki/bluespice-free-release/tree/5.2.6)/[pro](https://gitlab.hallowelt.com/bluespicebuilds/build-pro/-/tree/5.2.6)|5.2.6 (latest released 5.2 verion)|

The `main` branch of this project serves as the source of truth of common files shared across different branches - it should always be able to merge into any `dev-*` branch cleanly. The `main` branch is itself not directly usable.

### Components of the project

This project is meant to collect all components outside the wiki codebase, including:

- `deploy`: the script orchestrating containers, currently yaml files for docker compose, but will include helm charts for kubernetes soon.
- `images/*`: source code of container images, target to run for the stack
- `misc/*`: source code of compiled services, only as debugging context.
- `webservices/*`: source code of web services, not launched by the stack by default. Serves mainly as debugging context.

Here, to _collect_ means to faithfully mirror each remote source repo at target branch to subdirectories, merging also the full commit history.

- For components already managed by this project, `git-subtree` is the underlying magic.
  - [`git-subtree`](https://www.geeksforgeeks.org/git/git-subtree/) is "a strategy for including one Git repo as a subdirectory within another repo".
  - The usages of `git-subtree` are wrapped by `maintenance.sh` - one rarely needs to call raw `git-subtree` commands, as it can be quite long and complicated.
  - The called remote repos and branches respect definitions in `.env` of the project root.
- There can also be images that are not yet published, hence not directly integrated into this project.
  - `.gitignore` of the project root should ignore existance of such repos.
  - Ignored webservices can be cloned to provide debug context.
  - When [building images](#step-2-build-the-images), each `images/*` will be built as an image, including the ignored subdirs.

#### Additional repos to clone

In certain `dev-*` branches, unpublished image repos are _required_.

|subdir|origin|`dev-5.3.x`|`dev-galaxy`|
|-|-|-|-|
|`images/keyvaluestore`|`git@github.com:BlueSpice-Wiki/docker-bluespice-keyvaluestore.git`|`5.3.x`|`5.3.x`|
|`images/mcp`|`git@github.com:BlueSpice-Wiki/docker-bluespice-mcp.git`|`5.3.x`|`5.3.x`|
|`images/prompt`|`git@github.com:BlueSpice-Wiki/docker-bluespice-prompt.git`|`5.3.x`|`5.3.x`|
|`images/statisticsdashboard`|`git@github.com:BlueSpice-Wiki/docker-bluespice-statisticsdashboard.git`|`5.3.x`|`5.3.x`|

## Advanced Usages

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

### Having multiple installations on one computer

#### Best practice: One Workspace per Branch

- At each branch of this project, branch-specific ignored files are generated
  - If one simply switchs to another branch of this project in an already-setup installation, incompatibilities would arise annoyingly.
  - It is most intuitive to organize a set of compatible `code`, `data` and `bluespice-containers` in [hierarchy of subdirectories](#step-1-create-workspace-and-subdirectories).
- Containers from this project have names with branch-specific prefix, e.g `dev-52x-wiki-web` from branch `dev-5.2.x`.
  - Therefore, two containers of the same type but from different branches won't collide.
  - The containers have different native names other than [the `bluespice-deploy` stack](https://github.com/hallowelt/bluespice-deploy), so it can also co-exist well with a running `bluespice-deploy` installation.
- A known limitation is, multiple stacks compete for the 80 and 443 ports of the host machine.
  - Workaround A: use `./bluespice-deploy down proxy` to shutdown the proxy container of one stack occupying the ports, then the `proxy` container of another stack can start normally.
  - Workaround B: use `docker-compose.override.yml` to bind `proxy` ports with different host ports, and use `WIKI_PORT` or even an additional layer of host proxy to serve the wikis.
- To switch between different compatible code bases or data storages:
  - Put down the stack then edit `deploy/compose/.env`, comment out original `CODEDIR`, `DATADIR` etc. and replace with your new target. The stack would be ready to go up then.

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
