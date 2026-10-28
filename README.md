# BlueSpice Galaxy "Deploy"
<img style="display:block;margin:auto" src="https://bluespice.com/wp-content/uploads/2026/09/BlueSpice-Galaxy_Logo_Textmarke.svg" alt="BlueSpice MediaWiki logo" width="188" />
Toolkit for containerized deployment of BlueSpice Galaxy Wiki.

## Deployment
Please follow the [installation guide](https://en.wiki5.bluespice.com/wiki/Setup:Installation_Guide/Docker) in the BlueSpice Helpdesk 📚. For questions and support, please use the contact [form 🌐](https://bluespice.com/contact/) or visit the [community forums 💡](https://community.bluespice.com/).

Copy `.env.sample` to `.env`, adjust it and run `./bluespice-deploy up -d`. The stack supports fresh installations only.

## Editions
Set `EDITION` in `.env`. Each edition has its own wiki image, the service images are the same.

| Service                        | `glacier` | `sky` | `sapphire` |
|--------------------------------|:---------:|:-----:|:----------:|
| Wiki, search, database, cache, PDF, formula, diagram, wire, job queue | ✅ | ✅ | ✅ |
| Metadata store (NeoWiki)       | –         | ✅    | ✅         |
| CollabPads                     | ✅        | ✅    | ✅         |
| Antivirus (`ANTIVIRUS`)        | optional  | optional | optional |
| Let's Encrypt (`LETSENCRYPT`)  | optional  | optional | optional |
| Chat (`CHAT`)                  | –         | optional | optional |
| AI (`AI`, requires `CHAT`)     | –         | optional | optional |

Chat and AI require external services (e.g. an LLM provider) and are therefore opt-in.

## Images
By default, the images of the released versions are used:

| Edition          | Wiki image                                                        | Service images                                      |
|------------------|-------------------------------------------------------------------|-----------------------------------------------------|
| `glacier`        | `bluespice/galaxy-wiki` (Docker Hub)                              | `bluespice/galaxy-<service>` (Docker Hub)           |
| `sky`, `sapphire`| `docker.bluespice.com/bluespice-galaxy/<edition>/galaxy-wiki`     | `docker.bluespice.com/bluespice-galaxy/galaxy-<service>` |

`docker.bluespice.com` requires a login. `bluespice-deploy` asks for it, if required.

To load all images of all editions from a registry with the layout `<registry>/<edition>/galaxy-wiki` and
`<registry>/galaxy-<service>`, set `BLUESPICE_REGISTRY`:

| System                       | Setting                                                                   |
|------------------------------|---------------------------------------------------------------------------|
| Local/shared DEV and QA      | `BLUESPICE_REGISTRY=docker.bluespice.com/bluespice-galaxy-daily-builds`, `VERSION=1.0.x` |
| Production (hosted)          | `BLUESPICE_REGISTRY=docker.bluespice.com/bluespice-galaxy`, e.g. `VERSION=1.0.0` |

## Configuration
| Variable Name                | Default Value  | Description                                          | Optional |
|------------------------------|----------------|------------------------------------------------------|----------|
| `EDITION`                    | none           | `glacier`, `sky` or `sapphire`                       | No       |
| `VERSION`                    | none           | Tag of all images, e.g. `1.0.0`                      | No       |
| `DATADIR`                    | none           | Path to persistent volumes                           | No       |
| `BLUESPICE_REGISTRY`         | edition-specific | registry of all images, see [Images](#images)      | Yes      |
| `BLUESPICE_SERVICE_REPOSITORY`| edition-specific | pull the service images from an alternative repository | Yes |
| `BLUESPICE_WIKI_IMAGE`       | edition-specific | use an alternative image for the wiki-containers   | Yes      |
| `COMPOSE_PROJECT_NAME`       | `bluespice`    | use an alternate-docker-compose-name                 | Yes      |
| `ANTIVIRUS`                  | `false`        | enables ClamAV antivirus service                     | Yes      |
| `LETSENCRYPT`                | `false`        | enables LetsEcrpyt cert renew                        | Yes      |
| `ADMIN_MAIL`                 | none           | contact e-mail for LetsEncrypt certificates          | No, if `LETSENCRYPT` is `true` |
| `TZ`                         | `UTC`          | Timezone for BlueSpice and container system time     | Yes      |
| `CHAT`                       | `false`        | enables [Chat service connection](https://en.wiki.bluespice.com/wiki/Manual:Extension/ChatBot), not available in `glacier` | Yes      |
| `AI`                         | `false`        | enables [AI service connection](https://en.wiki.bluespice.com/wiki/Manual:AI_integrations_-_Overview), requires `CHAT`, not available in `glacier` | Yes      |

For more variables to set in `.env`, please also check [documentation](https://github.com/hallowelt/docker-bluespice-wiki/blob/main/README.md) of the image behind the wiki-containers.

If the environment variable `DEV_WIKI_DEBUG` is set, one can set the `debug-entrypoint` GPC (=`$_REQUEST`) to a value matching the `MW_ENTRY_POINT` constant in context of the application to enable full debug log to `stdout` for any call to the specified entry point.
