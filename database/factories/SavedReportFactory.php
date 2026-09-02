<?php

namespace Database\Factories;

use App\Analytics\Datasets\DatasetKey;
use App\Models\Employee;
use App\Models\SavedReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedReport>
 */
final class SavedReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'owner_employee_id' => Employee::factory(),
            'name' => fake()->words(4, true),
            'description' => fake()->optional()->sentence(),
            'dataset' => DatasetKey::TRANSACTIONS,
            'definition_version' => 1,
            'definition' => [
                'dimensions' => [
                    'booking_month',
                    'currency',
                ],
                'measures' => [
                    'total_amount',
                ],
                'filters' => [
                    [
                        'dimension' => 'status',
                        'operator' => 'equals',
                        'value' => 'booked',
                    ],
                ],
                'relative_date' => [
                    'dimension' => 'booking_date',
                    'preset' => 'last_30_days',
                ],
                'limit' => 100,
            ],
        ];
    }
}
