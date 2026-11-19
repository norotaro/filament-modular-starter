<?php

namespace App\Modules\Example\Models;

use App\Modules\Core\Casts\LocalizedArray;
use App\Modules\Core\Concerns\HasLocalizedFields;
use App\Modules\Core\Concerns\HasModuleFactory;
use App\Modules\Example\Database\Factories\ExampleItemFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Example model: shows the module-local factory wiring.
 *
 * `HasModuleFactory` resolves the factory inside the module by splitting the
 * model FQCN on `Models\` — which is why models must always live under a
 * `Models/` directory: any other path yields a bogus factory class and a
 * fatal error at first use.
 *
 * `tagline` shows a localized column: the cast reads and writes the whole
 * locale map, `HasLocalizedFields` picks the text to display.
 *
 * @property-read int $id
 * @property string $name
 * @property array<string, string> $tagline
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static ExampleItemFactory factory($count = null, $state = [])
 */
class ExampleItem extends Model
{
    use HasLocalizedFields;

    /** @use HasModuleFactory<ExampleItemFactory> */
    use HasModuleFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'tagline',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tagline' => LocalizedArray::class,
        ];
    }
}
