This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.
## Commands
All `make` targets run inside Docker. To run tools directly (e.g., in this environment where vendor/ is already present):

```bash
# Tests                                                                                                                                     ./vendor/bin/phpunit --configuration phpunit.xml.dist                                                                                       
                                                                                                                                            # Run a single test file                                              
./vendor/bin/phpunit --configuration phpunit.xml.dist tests/FilesUpTest.php                       
                                                                                                                                            # Static analysis           
./vendor/bin/phpcs          # code style check                                                                                              ./vendor/bin/psalm --show-info --no-diff --no-cache
./vendor/bin/phpstan analyze -c phpstan.neon
                                   
# Auto-fix
./vendor/bin/phpcbf         # fix code style                                                                                                ./vendor/bin/rector         # apply rector rules

# Run all checks               
composer check   # phpcs + psalm + phpstan
                                                                                                                                            # Run all fixes                                                                                                                             composer fix     # phpcbf + rector
```

Via Docker/Make:
```bash                                                               
make check   # run all static analysis tools                                                                                                
make tests   # run phpunit + infection mutation testing                                                                                     
make app     # open shell in example/ directory to run php cli.php migrate:*  
```

## Architecture

This is a PHP 8.3 library (`kuaukutsu/poc-migration`) for SQL-based database migrations.

### Public API (`src/`)

- **`Migrator`** — main entry point implementing `MigratorInterface`. Accepts a `MigrationCollection` and optional `EventSubscriberInterface[]`. Delegates all operations to `internal\action\Workflow`.
- **`Migration`** — represents one database target: a filesystem `$path` for SQL files, a `DriverInterface` for DB connection, and a `Config`.
- **`MigrationCollection`** — collection of `Migration` instances, keyed by `driver->getName()/driver->getSourceName()` (e.g., `sqlite/db`).
- **`Config`** — per-migration config: `$table` name (default `migration`) and a `template\FactoryInterface` for generating new migration files.
- **`InputOptions`** — value object passed to `up/down/fixture/redo/verify`. Controls `limit`, `version`, `dryRun`, `exactlyAll`, `dbName`, `migrationName`, repeatable, and latest-version flags.

### Internal Layer (`src/internal/`)

- **`action\Workflow`** — orchestrates all operations. Calls `filesystem\Action` to iterate SQL files, then calls `CommandInterface` methods (`up`, `down`, `exec`) per file, dispatching events on success/error.
- **`filesystem\Action`** — reads SQL files from the migration directory. Filters applied vs. pending migrations. Handles `up`, `down`, `fixture`, `repeatable`, and `create` operations.
- **`filesystem\Setup`** — reads setup SQL from the driver's built-in setup path (used by `init`).
- **`action\Command`** — implements `CommandInterface`: executes SQL via a `ConnectionInterface`, tracks applied migrations in the tracking table, manages transactions.
- **`connection\PDO\Driver`** — the only `DriverInterface` implementation. Accepts a DSN string; auto-detects `mysql`/`pgsql`/`sqlite`. Connection is lazily created and cached for 5 minutes.

### Connection and Setup SQL

Each DB type has its own setup migration under `src/connection/{sqlite,pgsql,mysql}/migration/`. These SQL files create the migration tracking table and are applied during `migrate:init`.

### Event System (`src/event/`)

`EventDispatcher` calls registered `EventSubscriberInterface` instances on events: `MigrateSuccess`, `MigrateError`, `ConnectionError`, `FilesystemError`, `FilesystemNotice`, `InitializationError`, `ConfigurationError`. Built-in subscribers: `PrettyConsoleOutput` (colored CLI output) and `TraceConsoleOutput`.

### SQL File Conventions

- Naming: `YYYYMMDDHHmm_description.sql` (sorted lexicographically)
- Skip a file by appending `_skip` to the filename: `202501011025_name_skip.sql`
- Sections within a file: `-- @up`, `-- @down`, `-- @skip`; if no directives, entire file is treated as `up`
- Repeatable migrations live in a `repeatable/` subdirectory and always re-run

### Example App (`example/`)

A working SQLite-based CLI app. Run `make app` then `php cli.php migrate:<command>`. Uses `php-di/php-di` and `symfony/console` (dev dependencies) to wire commands — these are not required by the library itself.
