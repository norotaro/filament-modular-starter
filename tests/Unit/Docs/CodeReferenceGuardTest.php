<?php

use Tests\TestCase;

uses(TestCase::class);

/*
 * Code-reference guard.
 *
 * Living docs — the root README, app/README, and every README.md and docs
 * markdown file under app/Modules — must not cite code by line number. See
 * the "Documentation conventions" section of app/README.md: cite by symbol
 * name, never by line number.
 *
 * The detector mirrors the shell gate used when the docs were written:
 *
 *   grep -ohiE '[A-Za-z_.)` ]:[0-9]+|lines? [0-9]+' <file>
 *
 * which covers four colon forms at once (dotted File.php:123, backtick-
 * prefixed `:123`, space-prefixed " :123", identifier-attached
 * symbolName:123) plus the word form (`line 15`, `lines 77-80`).
 *
 * The fifth surface form is a colon-less citation inside a ```mermaid fence
 * (a node label like "SomeService 12-34"). That pattern over-matches
 * legitimate prose, so a mermaid hit is flagged only when the word
 * immediately before the numbers ends in a file/class-ish marker:
 * .php, Job, Service, Controller, Model.
 */

if (! function_exists('code_reference_guard_violations')) {
    /**
     * Scan one document's contents. Each violation names the file and the
     * matched token, plus the full source line, so a failure is actionable.
     *
     * @return array<int, string>
     */
    function code_reference_guard_violations(string $label, string $contents): array
    {
        // Colon/word citation forms, identical to the shell gate (one union regex).
        $union = '/[A-Za-z_.)` ]:[0-9]+|lines? [0-9]+/i';

        $violations = [];

        foreach (preg_split('/\R/', $contents) as $index => $line) {
            if (preg_match_all($union, $line, $matches) > 0) {
                foreach (array_unique($matches[0]) as $token) {
                    $violations[] = sprintf(
                        '%s :: token "%s" (doc line %d) :: %s',
                        $label,
                        trim($token),
                        $index + 1,
                        trim($line),
                    );
                }
            }
        }

        // Colon-less mermaid label form. The number may be two digits or a
        // two-digit range, and must not run into further digits.
        $mermaidNumber = '/([A-Za-z][A-Za-z0-9_.]*)[ \t]+([0-9]{2}(?:-[0-9]{2})?)(?![0-9])/';
        $classLike = '/(\.php|Job|Service|Controller|Model)$/i';

        $fenceCount = preg_match_all('/(?s)```mermaid\r?\n(.*?)```/', $contents, $fences);

        for ($fence = 0; $fence < (int) $fenceCount; $fence++) {
            foreach (preg_split('/\R/', $fences[1][$fence]) as $blockLine) {
                if (preg_match_all($mermaidNumber, $blockLine, $labelMatches, PREG_SET_ORDER) === false) {
                    continue;
                }

                foreach ($labelMatches as $match) {
                    if (preg_match($classLike, $match[1]) !== 1) {
                        continue;
                    }

                    $violations[] = sprintf(
                        '%s :: token "%s" (mermaid label) :: %s',
                        $label,
                        trim($match[0]),
                        trim($blockLine),
                    );
                }
            }
        }

        return $violations;
    }
}

if (! function_exists('code_reference_guard_living_docs')) {
    /**
     * Living docs scope, resolved from __DIR__ so the test works whether or
     * not the app is booted.
     *
     * @return array<int, string>
     */
    function code_reference_guard_living_docs(): array
    {
        $root = dirname(__DIR__, 3);

        $files = [$root.'/README.md', $root.'/app/README.md'];

        $moduleTree = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root.'/app/Modules', FilesystemIterator::SKIP_DOTS),
        );

        foreach ($moduleTree as $entry) {
            if (! $entry->isFile() || strtolower($entry->getExtension()) !== 'md') {
                continue;
            }

            if ($entry->getFilename() === 'README.md' || basename($entry->getPath()) === 'docs') {
                $files[] = $entry->getPathname();
            }
        }

        return $files;
    }
}

test('living docs scope finds the docs it claims to guard', function () {
    $files = code_reference_guard_living_docs();

    $unreadable = array_values(array_filter($files, fn ($path) => ! is_readable($path)));

    expect($unreadable)->toBe([])
        ->and($files[0])->toBe(dirname(__DIR__, 3).'/README.md')
        ->and($files[1])->toBe(dirname(__DIR__, 3).'/app/README.md')
        // Core is the one module every application keeps, so its README proves the
        // module walk works without counting modules an application may delete.
        ->and($files)->toContain(dirname(__DIR__, 3).'/app/Modules/Core/README.md');
});

test('living docs contain no code-line citations', function () {
    $root = dirname(__DIR__, 3);

    $violations = [];

    foreach (code_reference_guard_living_docs() as $path) {
        $label = str_replace($root.'/', '', $path);
        $violations = array_merge(
            $violations,
            code_reference_guard_violations($label, (string) file_get_contents($path)),
        );
    }

    expect($violations)->toBe([]);
});

test('detector flags all five citation forms', function () {
    $samples = [
        'dotted File.php:123' => 'Read `Route.php:123` before touching the signer.',
        'backtick-prefixed `:123`' => 'The merge at `:123` is a no-op.',
        'space-prefixed  :123' => 'The same binding lives elsewhere :45 and nowhere else.',
        'identifier-attached symbolName:123' => 'Call define:16-48 from the provider.',
        'word form lines 77-80' => 'Skip lines 77-80 in the table composition.',
        'word form line 15' => 'Delete line 15 before publishing.',
        'mermaid class-ish label' => "```mermaid\nflowchart TD\n    A[\"SomeService 12-34\"] --> B[\"Webhook\"]\n```",
        'mermaid .php label' => "```mermaid\nflowchart TD\n    A[\"PanelProvider.php 15\"] --> B[\"Auth\"]\n```",
        'mermaid Job label' => "```mermaid\nflowchart TD\n    A[\"CleanupJob 99\"] --> B[\"Expire\"]\n```",
    ];

    foreach ($samples as $name => $contents) {
        expect(code_reference_guard_violations('synthetic.md', $contents))
            ->not->toBeEmpty("Form not detected: {$name}");
    }
});

test('detector does not flag clean prose', function () {
    $clean = implode("\n", [
        'Constraints are in composer.json; the local server listens on 127.0.0.1:8000.',
        'Cite `ModuleRegistry::define()` — never a line number — and see the docs.',
        'Production runs queue:work --sleep=30 --tries=3 under a supervisor.',
        'These pipeline rules are documented in app/README.md, updated by the last merge.',
        'The enum case is `Participant` and the UI label says the same.',
    ]);

    $violations = code_reference_guard_violations('clean.md', $clean);

    expect($violations)->toBe([]);
});

test('mermaid check spares labels whose adjacent token is not file or class-ish', function () {
    $cleanMermaid = "```mermaid\nflowchart TD\n    A[\"Payments 100 rows\"] --> B[\"Queue 3 workers\"]\n    C[\"Dashboard 42 widgets\"] --> D[\"Done\"]\n```";

    expect(code_reference_guard_violations('prose-mermaid.md', $cleanMermaid))->toBe([]);
});
