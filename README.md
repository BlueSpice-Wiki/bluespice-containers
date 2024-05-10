## Table of contents
- [Table of contents](#table-of-contents)
- [Information](#information)
- [Requirements](#requirements)
- [Creating a build](#creating-a-build)
- [Usage](#usage)
- [Config](#config)

## Information
This executes runjobs service parallely for multiple wiki farm instances as well as non-farm instances. 

## Requirements
- `php >= 8.0`
- `composer >= 2`
- Install and update composer dependencies using:
   ```bash
   composer update
   ```

## Creating a build
1. Clone this repo
2. Run `composer update --no-dev`
3. Run `box compile` to actually create the PHAR file  in `dist/`. See also https://github.com/humbug/box

## Usage
1. Create config file:
   - Copy the template config and make required changes:
      ```bash
      cp ./example.config.yaml ./config.yaml
      ```
      If using config file then wiki-type and wiki-path options are mandatory to mention
2. Run the main script file:
   - Using the default config:
      ```bash
      ./bin/run.php
      ```
   - Using custom config:
      - Create config file as explained above and then run the following:
         ```bash
         ./bin/run.php --config ./config.yaml
         ```
   - `./bin/run.php` script cli options:
      ```bash
      Description:
      Execute runjobs service parallelly for multiple instances.

      Usage:
      runjobs [options]

      Options:
      -c, --config[=CONFIG]                                      Path to the configuration file (YAML). If not provided, the default path will be used.
            --wiki-type[=WIKI-TYPE]                                Type of wiki (standalone or farm).
            --wiki-path[=WIKI-PATH]                                Absolute path of the wiki.
            --wiki-reference[=WIKI-REFERENCE]                      Reference file for the wiki (e.g., LocalSettings.php).
            --runjobs-percentage[=RUNJOBS-PERCENTAGE]              Maximum percentage of total jobs (per wiki, per cycle).
            --runjobs-maxtime[=RUNJOBS-MAXTIME]                    Maximum life time of a runJobs.php (per wiki, per cycle) in seconds.
            --runjobs-cooldown[=RUNJOBS-COOLDOWN]                  Wait time after each cycle in seconds.
            --runjobs-maxforkprocesses[=RUNJOBS-MAXFORKPROCESSES]  Maximum number of sub processes that can be spawned for parallel processing
            --exclude-instances[=EXCLUDE-INSTANCES]                Contains list of comma separated farm instances name for which the runjobs should not be run
            --include-instances[=INCLUDE-INSTANCES]                Contains list of comma separated farm instances name for which  the runjobs should be run, if left empty, all the farm instances will be considered except the ones in "exclude-instances" list
      -h, --help                                                 Display help for the given command. When no command is given display help for the runjobs command
      -q, --quiet                                                Do not output any message
      -V, --version                                              Display this application version
            --ansi|--no-ansi                                       Force (or disable --no-ansi) ANSI output
      -n, --no-interaction                                       Do not ask any interactive question
      -v|vv|vvv, --verbose                                       Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
      ```

3. Start as a init.d service (optional):
   - Create config file as explained above
   - Setup init.d script, from inside the git clone repo run:
   ```bash
   ./add-init.d-service.sh
   ```
   - Start service:
   ```bash
   service farm-runjobs start
   ```
   - Look at service status:
   ```bash
   service farm-runjobs status
   ```
   - Look at service logs:
   ```bash
   tail -f /var/log/farm-runjobs.log
   ```
   - Stop service:
   ```bash
   service farm-runjobs stop
   ```

## Config
 | name               | default value          | accepted values                                                             | description                                                                                                                                                                    |
 | ------------------ | ---------------------- | --------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
 | wiki:type          | `farm`                 | `farm`, `pro`                                                               | type of the bluespice edition                                                                                                                                                  |
 | wiki:path          | `/var/www/bluespice/w` | absolute path                                                               | bluespice installation path                                                                                                                                                    |
 | wiki:reference     | `LocalSettings.php`    | `LocalSettings.php`                                                         | local settings filename for the wiki which has database credentials                                                                                                            |
 | runjobs:percentage | `50`                   | 1-100                                                                       | Maximum percentage of total jobs. (Per wiki, per cycle)                                                                                                                        |
 | runjobs:maxtime    | `10`                   | any suitable number                                                         | Maximum life time of a runJobs.php (Per wiki, per cycle) - Seconds                                                                                                             |
 | runjobs:cooldown   | `3`                    | any suitable number                                                         | Wait time after each cycle - Seconds                                                                                                                                           |
 | runjobs:maxforkprocesses   | `5`                    | any suitable number                                                         | Maximum number of sub processes that can be spawned for parallel processing                                                                                                                                           |
 | exclude_instances  | `[]`                   | array of valid wiki farm instance names in double quotes separated by comma | contains list of farm instances name for which the runjobs should not be run                                                                                                   |
 | include_instances  | `[]`                   | array of valid wiki farm instance names in double quotes separated by comma | contains list of farm instances name for which the runjobs should be run, if left empty, all the farm instances will be considered except the ones in `exclude_instances` list |

