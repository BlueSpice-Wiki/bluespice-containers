## Helper for Installation and upgrades
runs as prepare service to create Directory-Tree and set Correct permissions
` command : prepare bluespice `
## Runs as upgrade-helper

```
tree /app/bin/upgrade5x                                                                                                
upgrade5x
├── upgrade-databases (Prevents braking DB on upgrade)
├── upgrade_db.sql
├── upgrade-filesystem(Renames Directories for FARM)
├── upgrade-mongo-db(dumps Mongo5 collabpads and recreates the db in Mongo8)
└── upgrade-tasks(empty)
```

Each update script creates backups at ` $DATADIR/upgrade_backup `

See logs at `$DATADIR/wiki/bluespice/logs/backend_upgrade_5.log`

#### Commands
` command : upgrade-pipeline`

force rerun

` command : upgrade-pipeline -f`


#### Use with bluespice-deploy

To run the automated upgrade use `./bluespice-deploy up -d --profile=upgrade`

Run `./bluespice-deploy up -d -profile=upgrade-force` to force rerun the update-scripts and update.php in Wiki
