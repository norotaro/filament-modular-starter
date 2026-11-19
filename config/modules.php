<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Module Enablement Flags
    |--------------------------------------------------------------------------
    |
    | The skeleton has no tenancy, so a module's applicability closure reads
    | these flags instead of a per-organization enablement table. A real
    | deployment replaces the flag with whatever decides enablement (a
    | tenant row, an env gate, a plan lookup) — the closure signature stays.
    |
    */

    'example' => env('MODULE_EXAMPLE', true),

    'sample' => env('MODULE_SAMPLE', false),

];
