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

## Backup

Runs as a scheduled backup service via `supercronic`.

**Command:** `run-backups`

### Backup Types

The `BACKUP_TYPE` variable controls which backup method is used:

- **`dump-only`** - Only create database dumps (MariaDB + MongoDB), no TAR/Restic archive
  - Lightweight, database snapshots on each backup run
  - Databases stored in `/data/wiki/db-dumps/` (overwritten at each `BLUESPICE_BACKUP_TIME`)
  - Best for disk-constrained environments or when you don't need full file backups

- **`tar-file`** (default) - Create TAR archives
  - Respects `FILE_BACKUP` variable:
    - `true`: Archive all wiki files + database dumps
    - `false`: Archive only database dumps
  - Stored in `/backup/tar-backup/`
  - Old backups retained based on `BLUESPICE_BACKUP_RETENTION`

- **`restic-repo`** - Create Restic backups (deduplicating backup repository)(experimental)
  - Respects `FILE_BACKUP` variable:
    - `true`: Backup all wiki files + database dumps
    - `false`: Backup only database dumps
  - Stored in `/backup/restic-backup/`
  - Requires `BLUESPICE_BACKUP_PASS` for encryption

### Environment Variables

#### Scheduling & Backup Type
| Variable | Default | Description |
|---|---|---|
| `BLUESPICE_BACKUP_TIME` | `0 2 * * *` | Cron expression for the backup schedule. Falls back to default if invalid. |
| `BACKUP_TYPE` | `tar-file` | Backup type: `dump-only` (databases only), `tar-file` (TAR archives), or `restic-repo` (Restic repository) |

#### Directories
| Variable | Default | Description |
|---|---|---|
| `BLUESPICE_BACKUP_DIRECTORY` | `/backup/restic-backup` | Target directory for Restic backups |
| `BLUESPICE_DATABASE_DUMP_DIRECTORY` | `/data/wiki/db-dumps` | Directory for database dumps (daily overwrites, no versioning) |
| `BLUESPICE_TAR_BACKUP_DIRECTORY` | `/backup/tar-backup` | Target directory for TAR backups |

#### Passwords
| Variable | Description |
|---|---|
| `BLUESPICE_BACKUP_PASS` | Password for the Restic repository (auto-generated and saved to `/data/.secrets/BLUESPICE_BACKUP_PASS` if not set) |

#### Database & Files
| Variable | Default | Description |
|---|---|---|
| `DB_HOST` | `database` | MariaDB hostname |
| `DB_NAME` | – | Database name |
| `DB_USER` | `bluespice` | Database user |
| `DB_PASS` | – | Database password |
| `DB_PREFIX` | – | Table prefix |
| `FILE_BACKUP` | `true` | If `true`, backup all wiki files (plus databases). If `false`, backup only databases (no wiki files). Only relevant for `tar-file` and `restic-repo` backup types. |
| `BLUESPICE_BACKUP_RETENTION` | `5` | Retention time in days for TAR backups (only for `BACKUP_TYPE=tar-file`) |
| `WIKI_HOST` | – | Wiki hostname — used for TAR archive filenames |
| `EDITION` | – | Set to `farm` to enable multi-instance farm backup |
| `WIKI_FARM_USE_SHARED_DB` | – | *(Farm only)* Set if all farm instances share a single database |

---

## Restore (experimental)

Restores backups created by the backup service. Use the `restore` wrapper command or manually set environment variables and call `restore-pipeline`.

**Command:** `restore` or `restore-pipeline`

### Using the `restore` Wrapper

The recommended way to restore is using the `restore` command, which automatically finds the latest backup and prompts for confirmation:

```bash
docker exec <container> restore [full|sfr=<instance>]
```

**Examples:**
```bash
# Full restore (interactive, prompts for confirmation)
docker exec bluespice-wiki restore

# Force full restore
docker exec bluespice-wiki restore full

# Restore single farm instance
docker exec bluespice-wiki restore sfr=instance1
```

The wrapper shows detailed information about what will be restored and deleted before proceeding.

### Manual Restore using `restore-pipeline`

**Required Environment Variable:**
- `RESTORE_BACKUP_FILE` – Full path to the backup `.tar.gz` file to restore

**Optional Environment Variables:**
- `RESTORE_FARM_INSTANCE` – *(Farm only)* Restore only a specific farm instance (e.g., `instance1`). If not set, all instances in the backup are restored
- `BLUESPICE_TAR_BACKUP_DIRECTORY` – Override default backup directory
- `BLUESPICE_DATABASE_DUMP_DIRECTORY` – Override default database dump directory

**Example:**
```bash
RESTORE_BACKUP_FILE=/backup/tar-backup/wiki.example.com_2024-07-28_120000.tar.gz restore-pipeline
```

### How Restore Works

1. **Extract backup:** Unpacks the TAR archive to a temporary directory
2. **Smart cleanup:** Only deletes directories/files that exist in the backup
   - Cleans cacheable directories: `images`, `cache`, `extensions/BlueSpiceFoundation/data`
   - Cleans init settings: `pre-init-settings.php`, `post-init-settings.php`
   - For single instance restore: only cleans that specific farm instance
   - For full restore: only cleans farm instances that exist in the backup
3. **Restore files:** Extracts backup files to their original locations
4. **Restore databases:** Recreates MariaDB and MongoDB from SQL dumps
5. **Logging:** All operations logged to `/data/wiki/bluespice/logs/backend_restore_*.log` with timestamps

### Cleanup Behavior

The restore process **only deletes directories and files that exist in the backup**:

- **FILE_BACKUP=true:** All wiki files and databases are backed up, so they will be restored and old data cleaned
- **FILE_BACKUP=false:** Only database dumps are backed up, so **no wiki files are deleted** — only databases are restored

This ensures safety when you want to restore databases only without affecting existing wiki files.

---

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
