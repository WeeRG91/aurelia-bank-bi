<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var array{country_code: string, city: string, location_code: string} $location */
        $location = fake()->randomElement([
            ['country_code' => 'LU', 'city' => 'Luxembourg', 'location_code' => 'LUX'],
            ['country_code' => 'LU', 'city' => 'Esch-sur-Alzette', 'location_code' => 'ESZ'],
            ['country_code' => 'FR', 'city' => 'Metz', 'location_code' => 'MET'],
            ['country_code' => 'DE', 'city' => 'Trier', 'location_code' => 'TRI'],
            ['country_code' => 'BE', 'city' => 'Arlon', 'location_code' => 'ARL'],
        ]);

        $sequence = fake()->unique()->numberBetween(1, 999);

        return [
            'branch_code' => sprintf(
                '%s-%s-%03d',
                $location['country_code'],
                $location['location_code'],
                $sequence,
            ),
            'name' => sprintf('Aurelia %s Branch %03d', $location['city'], $sequence),
            'country_code' => $location['country_code'],
            'city' => $location['city'],
            'opened_at' => fake()->dateTimeBetween('-75 years', '-1 year')->format('Y-m-d'),
        ];
    }
}
