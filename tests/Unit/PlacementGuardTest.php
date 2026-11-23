<?php

declare(strict_types=1);

use Tests\TestCase;

/*
 * Method-level placement guard for the atom rule in app/README.md's Rationale:
 * atoms compose UI, not reads. Pest's arch() compares namespaces only, so a query
 * written inside an atom's closure is invisible to it — and the atom layer is the
 * one the contract demands stay thin.
 *
 * The scan is textual and claims only what it can see: a static model facade
 * (`ExampleItem::query()`). Relationship traversal (`$user->notifications`) needs
 * the receiver's type, so it stays a review rule and a green run here is not a
 * claim that atoms hold no reads at all.
 *
 * Scope is the three atom directories the contract names, plus the whole panel
 * layer, which "reads and writes through module services". Module Filament PAGES
 * sit outside it: a page is a screen, not an atom, and this rule says nothing about
 * screens. Route files are excluded because a route's `Route::where()` constraint
 * shares the shape of a model's.
 *
 * Both self-checks are load-bearing: an atom added to a directory nobody scans, or
 * a regex that matches nothing, looks identical to compliance without them.
 */

uses(TestCase::class);

if (! function_exists('eloquentReadFacades')) {
    /**
     * Static Eloquent read facades found in the given source.
     *
     * `get` and `value` are deliberately absent: reached statically they collide
     * with `Route::get()` and helpers that are not model reads, and a query built
     * through any other facade in this list is already caught.
     *
     * @return list<string>
     */
    function eloquentReadFacades(string $source): array
    {
        preg_match_all(
            '/\b[A-Z][A-Za-z0-9_]*::(?:query|all|count|exists|sole|pluck|find[A-Za-z]*|first[A-Za-z]*|where[A-Za-z]*)\s*\(/',
            $source,
            $matches
        );

        return $matches[0];
    }

    /**
     * Every atom file in the module layer, plus every panel class file.
     *
     * @return list<string>
     */
    function guardedPlacementFiles(): array
    {
        $found = [];

        $collect = function (string $root, bool $atomDirectoriesOnly) use (&$found): void {
            if (! is_dir($root)) {
                return;
            }

            $tree = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($tree as $entry) {
                $path = $entry->getPathname();

                if (! $entry->isFile() || ! str_ends_with($path, '.php') || str_contains($path, '/routes/')) {
                    continue;
                }

                $isAtom = str_contains($path, '/Filament/Actions/')
                    || str_contains($path, '/Filament/Schemas/')
                    || str_contains($path, '/Filament/Tables/');

                if ($isAtom || ! $atomDirectoriesOnly) {
                    $found[] = $path;
                }
            }
        };

        $collect(app_path('Modules'), true);
        $collect(app_path('Panels'), false);

        return $found;
    }
}

it('guards a placement scope that actually resolves to files', function () {
    $files = guardedPlacementFiles();

    // An empty scope passes every scan, so the scope itself is the assertion. The
    // panel layer always holds files; atoms exist only once a module ships some.
    expect($files)->not->toBeEmpty();
});

it('flags the static read facades it forbids', function () {
    // Positive control: without it, a regex that matches nothing is
    // indistinguishable from a layer that holds no reads.
    $planted = <<<'PHP'
        $rows = ExampleItem::query()->orderByDesc('created_at')->get();
        $one = Identity::whereKey($actor->id)->exists();
        PHP;

    expect(eloquentReadFacades($planted))->toBe([
        'ExampleItem::query(',
        'Identity::whereKey(',
    ]);
});

it('leaves a read-free atom alone', function () {
    // The control's other half: the pattern must not fire on the container and
    // notification calls every atom legitimately makes.
    $clean = <<<'PHP'
        $column = TextColumn::make('name')->sortable();
        Notification::make()->success()->send();
        $items = $catalog->itemsFor($actor);
        PHP;

    expect(eloquentReadFacades($clean))->toBe([]);
});

it('keeps eloquent read facades out of the atom and panel layers', function () {
    $violations = [];

    foreach (guardedPlacementFiles() as $path) {
        foreach (eloquentReadFacades((string) file_get_contents($path)) as $facade) {
            $violations[] = str_replace(base_path().'/', '', $path).': '.$facade;
        }
    }

    expect($violations)->toBe([]);
});
