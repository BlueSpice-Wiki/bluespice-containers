# Usage
## For development
1. Clone this repo
2. Run `composer update`
3. Create local config file (see [Config](#config) for details)
4. Run the script with the local config file:
   ```bash
   ./bin/parallel-runjobs-service --config ./config.yaml
   ``` 
   
### Creating a build
Once you are done with the development, you can create a build using the following steps:
1. Compile the PHAR file using the following command:
   ```bash
   box compile
   ```
   see https://github.com/humbug/box for installation and usage of `box`
2. That will create a PHAR file in `build/` directory.
3. Commit your changes

## For production
1. Clone this repo
   ```bash
    git clone {URL} /opt/parallel-runjobs-service
    cd /opt/parallel-runjobs-service
    ```
2. Create local config file (see [Config](#config) for details) - must configure Database connection (for management wiki (w))
3. Add service
    - Run the following command to add the service:
      ```bash
      ./add-init.d-service.sh
      ```
    - Start the service:
      ```bash
      service wiki-runjobs start
      ```
    - Check the status of the service:
      ```bash
      service wiki-runjobs status
      ```
    - Check the logs of the service:
      ```bash
      tail -f /var/log/farm-runjobs.log
      ``` 
      
# Config

| name                   | default value                                      | accepted values                         | description                                                                                                                                                                            |
 |------------------------|----------------------------------------------------|-----------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| wiki:type              | `standalone`                                       | `farm`, `standalone`                    | type of the bluespice edition                                                                                                                                                          |
| wiki:path              | - (mandatory)                                      | absolute path                           | bluespice installation path (also in case of farm, path to `w`)                                                                                                                        |
| environment:php        | `/usr/bin/php`                                     | string                                  | Path to PHP executable                                                                                                                                                                 |
| runjobs:maxtime        | `30`                                               | positive integer                        | Maximum life time of a runJobs.php (Per wiki, per cycle) - Seconds                                                                                                                     |
| runjobs:maxjobs        | `50`                                               | positive integer                        | Max number of jobs to execute per cycle                                                                                                                                                |
| runjobs:maxparallel    | `1`/`10`                                           | positive integer                        | Maximum number parallel processes (only applicable to farm)                                                                                                                            |
| farm.exclude_instances | `[]`                                               | array of valid wiki farm instance names | contains list of farm instances name for which the runjobs should executed (has priority over includes, ie. If instance is specified in both include and exclude, it will be excluded) |
| farm.include_instances | `[]`                                               | array of valid wiki farm instance names | contains list of farm instances name for which the runjobs should be run, if left empty, all the farm instances will be considered except the ones in `exclude_instances` list         |
| database               | can be ommited competely for standalone - not used | string                                  | Database server                                                                                                                                                                        |
| database.dbserver      | `localhost`                                        | string                                  | Database server                                                                                                                                                                        |
| database.dbname        | - (mandatory for farm)                             | string                                  | Name of management database for farms)                                                                                                                                                 |
| database.dbuser        | - (mandatory for farm)                             | string                                  | Database user                                                                                                                                                                          |
| database.dbpassword    | - (mandatory for farm)                             | string                                  | Database password                                                                                                                                                                      |
| database.dbprefix      | (empty)                                            | string                                  | Database prefix                                                                                                                                                                        |
| database.dbport        | 3306                                               | integer                                 | Database port                                                                                                                                                                          |
