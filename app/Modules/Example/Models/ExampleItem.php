<?php

namespace App\Modules\Example\Models;

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
 * @property-read int $id
 * @property string $name
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @method static ExampleItemFactory factory($count = null, $state = [])
 */
class ExampleItem extends Model
{
    /** @use HasModuleFactory<ExampleItemFactory> */
    use HasModuleFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
    ];
}
