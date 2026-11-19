# Running on Postgres

The starter defaults to sqlite so a fresh clone runs with no database server, and the test suite uses in-memory sqlite. Switch to Postgres when production runs on it. Sqlite and Postgres behave differently in ways that only show up in production: a transaction aborted by a caught constraint violation, locking, and index shapes. Tests that pass on sqlite can still fail on Postgres.

This is a recipe, not a default. Apply it in your own app.

## 1. Local development

In `.env` (and `.env.example`, so teammates get the same setup):

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=my_app
DB_USERNAME=postgres
DB_PASSWORD=
```

In `config/database.php`, change the fallback of `default` from `sqlite` to `pgsql`. `config/queue.php` reads `DB_CONNECTION` for job batches and failed jobs, so change those two fallbacks as well.

## 2. The test suite

Create a separate database, such as `my_app_test`, and point `phpunit.xml` at it in place of the in-memory sqlite lines:

```xml
<server name="DB_URL" value="" force="true"/>
<env name="DB_URL" value="" force="true"/>
<server name="DB_CONNECTION" value="pgsql" force="true"/>
<env name="DB_CONNECTION" value="pgsql" force="true"/>
<env name="DB_HOST" value="127.0.0.1"/>
<env name="DB_PORT" value="5432"/>
<server name="DB_DATABASE" value="my_app_test" force="true"/>
<env name="DB_DATABASE" value="my_app_test" force="true"/>
<env name="DB_USERNAME" value="postgres"/>
<env name="DB_PASSWORD" value=""/>
```

The connection and database name are forced in both `$_SERVER` and `$_ENV`, for two reasons:
- Laravel reads an exported variable from `$_SERVER` first.
- A `DB_URL` would replace the database it names, so it is blanked.

Host, port and credentials are not forced, so a real environment variable (for example in CI) still wins.

## 3. Guard the test database

Tests that use `RefreshDatabase` drop and recreate tables. On sqlite in memory that is harmless. On a server, one exported `DB_DATABASE` pointing at your development database is enough to wipe it. Make `Tests\TestCase` refuse any database whose name does not end in `_test`:

```php
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $app = parent::createApplication();

        $config = $app['config'];
        $database = (string) $config->get('database.connections.'.$config->get('database.default').'.database');

        // Parallel runs append _<n> to the database name.
        if (preg_match('/_test(_\d+)?$/', $database) !== 1) {
            // Teardown hooks still run after a refused boot; cut the facades
            // loose so none of them reaches the database.
            Facade::clearResolvedInstances();

            throw new RuntimeException("Refusing to run tests against [{$database}]: the database name must end in _test.");
        }

        return $app;
    }
}
```

The `phpunit.xml` settings choose the test database. This guard is what actually protects your development database, because a variable exported in the shell can still override them.
