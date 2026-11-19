<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

/*
 * Architecture guards for the contract in app/README.md — the subset that
 * namespace-level dependency analysis, class metadata and the filesystem can
 * enforce.
 *
 * The layer-direction rules (modules and partners reaching inward) are
 * deliberately absent until the two known crossings are resolved:
 * `Users\Models\Identity` importing `Panels\Commons\Enums\PanelID`, and the
 * partner contracts consuming `Core\Support\Result`. Adding a rule that encodes
 * today's debt as legal would launder it into the contract.
 *
 * Filesystem guards assert their own scope is non-empty before trusting
 * themselves. The placement guard self-checks structurally instead: an empty
 * derivation leaves every real module model in scope, so it fails loudly rather
 * than passing quietly.
 *
 * Placement rules invert the subject: `arch()` has no predicate about where a
 * class lives, so the subject is the whole layer tree and the predicate is
 * `not->toExtend`, with the exemption list derived from the module layers only.
 * Panels and partners are absent from that list on purpose — they may not own
 * persisted records — so a `Models/` namespace under either is a violation, not
 * a permitted location, and a new module still needs no edit here. Because
 * `toExtend` follows the inheritance chain, a class extending an existing model
 * two directories deep is caught too — a text scan of `extends Model` is not.
 *
 * The derivation runs at file scope, where the container is not yet the
 * application: `app_path()` and `base_path()` only resolve inside closures bound
 * to `TestCase`, hence the `__DIR__` anchors here.
 *
 * Registration guards read `bootstrap/providers.php` as a file, never the
 * container: `registerConfiguredProviders()` hands `getCachedServicesPath()` to
 * the ProviderRepository, so a compiled `bootstrap/cache/services.php` can serve
 * a provider set that no longer matches the registration file.
 */

uses(TestCase::class);

$modelNamespaces = [];

// Only module layers may own persisted records: panels are presentation and
// partners are outbound communication, so a Models namespace under either is a
// violation, not an exemption. Concrete prefixes, not `App\Modules\*\Models`:
// ignoring() matches with a literal str_starts_with, unlike expect(), which expands `*`.
foreach (glob(__DIR__.'/../../app/Modules/*/Models', GLOB_ONLYDIR) ?: [] as $path) {
    $modelNamespaces[] = 'App\\Modules\\'.basename(dirname($path)).'\\Models';
}

if (! function_exists('moduleNamespacesWith')) {
    /**
     * The namespace of every module directory named $directory. Pest throws on a
     * wildcard such as `App\Modules\*\Services` when no module has that
     * directory, as in the bare starter, so arch rules take this list instead.
     *
     * @return list<string>
     */
    function moduleNamespacesWith(string $directory): array
    {
        return array_map(
            fn (string $path): string => 'App\\Modules\\'.basename(dirname($path)).'\\'.$directory,
            glob(__DIR__.'/../../app/Modules/*/'.$directory, GLOB_ONLYDIR) ?: [],
        );
    }
}

arch('support stays mechanism and never depends on policy')
    ->expect(moduleNamespacesWith('Support'))
    ->not->toUse([...moduleNamespacesWith('Services'), ...moduleNamespacesWith('Policies')]);

arch('no eloquent model lives outside a Models namespace')
    ->expect(['App\Modules', 'App\Panels', 'App\Partners'])
    ->classes()
    ->not->toExtend(Model::class)
    ->ignoring($modelNamespaces);

arch('a Models namespace holds nothing but eloquent models')
    ->expect('App\Modules\*\Models')
    ->toBeClasses()
    ->toExtend(Model::class);

arch('every class name matches the casing of its path')
    ->expect('App')
    ->toBeCasedCorrectly();

if (! function_exists('moduleAndPartnerProviderFiles')) {
    /**
     * Every discoverable provider file, keyed by its expected class name. Core is
     * excluded: the manager registers it unconditionally rather than by glob, and
     * ModuleManagerServiceProvider is the one provider that must be hand-listed.
     *
     * @return array<string, string> path => expected FQCN
     */
    function moduleAndPartnerProviderFiles(): array
    {
        $found = [];

        foreach (['Modules', 'Partners'] as $layer) {
            foreach (glob(app_path("{$layer}/*/*ServiceProvider.php")) ?: [] as $path) {
                if (basename(dirname($path)) === 'Core') {
                    continue;
                }

                $found[$path] = 'App\\'.$layer.'\\'.basename(dirname($path)).'\\'.basename($path, '.php');
            }
        }

        return $found;
    }
}

it('lists every panel provider in bootstrap/providers.php', function () {
    // Panels sit outside the auto-discovery globs, so a provider missing from
    // this file boots nothing: no routes, no PanelID mount, and the suite stays
    // green because no class is loaded to break.
    $registered = (array) include base_path('bootstrap/providers.php');
    $paths = glob(app_path('Panels/*/*PanelProvider.php')) ?: [];
    $unregistered = [];

    expect($paths)->not->toBeEmpty();

    foreach ($paths as $path) {
        $fqcn = 'App\Panels\\'.basename(dirname($path)).'\\'.basename($path, '.php');

        if (! in_array($fqcn, $registered, true)) {
            $unregistered[] = $fqcn;
        }
    }

    expect($unregistered)->toBe([]);
});

it('registers no module or partner provider by hand', function () {
    // The manager's glob already registers these. A second listing is a runtime
    // no-op ({@see \Illuminate\Foundation\Application::register()} skips a
    // provider it already holds), but it hides which mechanism boots it.
    $registered = (array) include base_path('bootstrap/providers.php');
    $providers = moduleAndPartnerProviderFiles();
    $handListed = [];

    expect($providers)->not->toBeEmpty();

    foreach ($providers as $fqcn) {
        if (in_array($fqcn, $registered, true)) {
            $handListed[] = $fqcn;
        }
    }

    expect($handListed)->toBe([]);
});

it('names each discoverable provider after its own file', function () {
    // The manager registers a discovered provider only when class_exists()
    // passes, so a provider file declaring nothing at all is skipped silently —
    // only this check names it. Read statically rather than through
    // class_exists(): a declared name sitting at the wrong path raises a
    // redeclare fatal before this guard runs, crashing the suite instead of
    // reporting the file.
    $providers = moduleAndPartnerProviderFiles();
    $mismatched = [];

    expect($providers)->not->toBeEmpty();

    foreach ($providers as $path => $fqcn) {
        $source = (string) file_get_contents($path);
        $declared = null;

        if (preg_match('/^namespace\s+([^;]+);/m', $source, $namespace) === 1
            && preg_match('/^(?:(?:final|abstract|readonly)\s+)*class\s+(\w+)/m', $source, $class) === 1) {
            $declared = trim($namespace[1]).'\\'.$class[1];
        }

        if ($declared !== $fqcn) {
            $mismatched[] = basename($path).' declares '.($declared ?? 'no top-level class');
        }
    }

    expect($mismatched)->toBe([]);
});

it('keeps repository and trait directories out of the layer tree', function () {
    // The contract puts data access in Models and Services and names the trait
    // directory Concerns/. A Repositories/ or Traits/ directory is legal PHP and
    // silently forks the convention, so nothing else would ever notice it.
    $forbidden = ['Repositories', 'Traits'];
    $hits = [];
    $directories = 0;

    foreach (['Modules', 'Partners', 'Panels'] as $layer) {
        $root = app_path($layer);

        if (! is_dir($root)) {
            continue;
        }

        // SELF_FIRST is required: the default LEAVES_ONLY mode never yields a
        // directory that has children, which is precisely the violating case.
        $tree = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($tree as $entry) {
            if (! $entry->isDir()) {
                continue;
            }

            $directories++;

            if (in_array($entry->getFilename(), $forbidden, true)) {
                $hits[] = $entry->getPathname();
            }
        }
    }

    expect($directories)->toBeGreaterThan(0);
    expect($hits)->toBe([]);
});
