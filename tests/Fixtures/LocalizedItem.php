<?php

namespace Tests\Fixtures;

use App\Modules\Core\Casts\LocalizedArray;
use App\Modules\Core\Concerns\HasLocalizedFields;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property array<string, string> $tagline
 */
class LocalizedItem extends Model
{
    use HasLocalizedFields;

    protected $table = 'localized_items';

    /**
     * @var list<string>
     */
    protected $fillable = ['tagline'];

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
