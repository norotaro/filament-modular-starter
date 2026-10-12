<?php

namespace App\Modules\Core\Concerns;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

/**
 * @template TFactory of \Illuminate\Database\Eloquent\Factories\Factory
 */
trait HasModuleFactory
{
    /** @use HasFactory<TFactory> */
    use HasFactory;

    protected static function newFactory(): Factory
    {
        $module = Str::before(get_called_class(), 'Models\\');
        $modelName = Str::after(get_called_class(), 'Models\\');
        $path = $module.'Database\\Factories\\'.$modelName.'Factory';

        return $path::new();
    }
}
