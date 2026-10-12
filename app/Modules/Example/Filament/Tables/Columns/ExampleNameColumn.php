<?php

namespace App\Modules\Example\Filament\Tables\Columns;

use Filament\Tables\Columns\TextColumn;

/**
 * Column atom: reusable, domain-carrying building block.
 *
 * Atoms live in the owning module; panels compose them into table
 * compositions — see the Filament Integration Guide in app/README.md.
 */
class ExampleNameColumn
{
    public static function make(): TextColumn
    {
        return TextColumn::make('name')
            ->label(__('Name'))
            ->sortable()
            ->searchable();
    }
}
