<img alt="BlueSpice Logo" align="right" src="https://bluespice.com/wp-content/uploads/2022/09/bluespice_logo.png" />

# `bluespice/helper` service for BlueSpice

## Helper for installation and upgrades
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

## Testing

Build the image with:

```bash
docker build --no-cache --pull -t bluespice/helper:latest .
```

Test the `prepare-bluespice` command with:
```bash
docker run --rm \
	-v /tmp/helper$(date +%s):/data \
	-e EDITION=farm \
	-e WIKI_HOST=wiki.company.local \
	bluespice/helper:latest prepare-bluespice
```
