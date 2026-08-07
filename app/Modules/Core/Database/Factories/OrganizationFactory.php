<?php

namespace App\Modules\Core\Database\Factories;

use App\Modules\Core\Models\Organization;
use App\Modules\Core\Support\Locale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Organization>
     */
    protected $model = Organization::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),

            // Set here as well as defaulted in the schema, so a freshly made
            // model carries it in memory rather than only once it is read back.
            'locale' => Locale::default(),
        ];
    }
}
