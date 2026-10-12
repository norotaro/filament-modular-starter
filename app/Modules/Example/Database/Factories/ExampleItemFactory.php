<?php

namespace App\Modules\Example\Database\Factories;

use App\Modules\Example\Models\ExampleItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExampleItem>
 */
class ExampleItemFactory extends Factory
{
    /**
     * @var class-string<ExampleItem>
     */
    protected $model = ExampleItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
        ];
    }
}
