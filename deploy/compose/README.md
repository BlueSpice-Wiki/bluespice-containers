# BlueSpice Galaxy "Deploy"
<img style="display:block;margin:auto" src="https://bluespice.com/wp-content/uploads/2026/09/BlueSpice-Galaxy_Logo_Textmarke.svg" alt="BlueSpice MediaWiki logo" width="188" />
Toolkit for containerized deployment of BlueSpice Galaxy Wiki.

## Deployment
Please follow the [installation guide](https://en.wiki5.bluespice.com/wiki/Setup:Installation_Guide/Docker) in the BlueSpice Helpdesk 📚. For questions and support, please use the contact [form 🌐](https://bluespice.com/contact/) or visit the [community forums 💡](https://community.bluespice.com/).


## Configuration
| Variable Name                | Default Value  | Description                                          | Optional |
|------------------------------|----------------|------------------------------------------------------|----------|
|`BLUESPICE_SERVICE_REPOSITORY`| `bluespice`    | pull Docker images from an alternative service repo  | Yes      |
| `SERVICES_REPOSITORY_PATH`   | none           |a previous alias to set `BLUESPICE_SERVICE_REPOSITORY`| Yes      |
| `BLUESPICE_WIKI_IMAGE`       |edition-specific| use an alternative image for the wiki-containers     | Yes      |
| `COMPOSE_PROJECT_NAME`       | `bluespice`    | use an alternate-docker-compose-name                 | Yes      |
| `DATADIR`                    | `./_volume`    | Path to persitent Volumes                            | Yes      |
| `ANTIVIRUS`                  | `false`        | enables ClamAV antivirus service                     | Yes      |
| `LETSENCRYPT`                | `false`        | enables LetsEcrpyt cert renew                        | Yes      |
| `KERBEROS`                   | `false`        | enables Kerberos-Authentication                      | Yes      |
| `TZ`                         | `UTC`          | Timezone for BlueSpice and container system time     | Yes      |
| `CHAT`                       | `false`        | enables [Chat service connection](https://en.wiki.bluespice.com/wiki/Manual:Extension/ChatBot)                          | Yes      |
| `AI`                         | `false`        | enables [AI service connection](https://en.wiki.bluespice.com/wiki/Manual:AI_integrations_-_Overview)                              | Yes      |

For more variables to set in `.env`, please also check [documentation](https://github.com/hallowelt/docker-bluespice-wiki/blob/main/README.md) of the image behind the wiki-containers.

If the environment variable `DEV_WIKI_DEBUG` is set, one can set the `debug-entrypoint` GPC (=`$_REQUEST`) to a value matching the `MW_ENTRY_POINT` constant in context of the application to enable full debug log to `stdout` for any call to the specified entry point.
