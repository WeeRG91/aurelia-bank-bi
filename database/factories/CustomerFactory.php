<?php

namespace Database\Factories;

use App\Enums\CustomerSegment;
use App\Enums\CustomerStatus;
use App\Enums\RiskLevel;
use App\Models\Customer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var array{country_code: string, city: string, postal_code: string, currency: string, phone_prefix: string} $location */
        $location = fake()->randomElement([
            [
                'country_code' => 'LU',
                'city' => 'Luxembourg',
                'postal_code' => 'L-1000',
                'currency' => 'EUR',
                'phone_prefix' => '+352 000',
            ],
            [
                'country_code' => 'FR',
                'city' => 'Metz',
                'postal_code' => '57000',
                'currency' => 'EUR',
                'phone_prefix' => '+33 000',
            ],
            [
                'country_code' => 'DE',
                'city' => 'Trier',
                'postal_code' => '54290',
                'currency' => 'EUR',
                'phone_prefix' => '+49 000',
            ],
            [
                'country_code' => 'BE',
                'city' => 'Arlon',
                'postal_code' => '6700',
                'currency' => 'EUR',
                'phone_prefix' => '+32 000',
            ],
            [
                'country_code' => 'CH',
                'city' => 'Zürich',
                'postal_code' => '8000',
                'currency' => 'CHF',
                'phone_prefix' => '+41 000',
            ],
        ]);

        $birthDate = CarbonImmutable::instance(
            fake()->dateTimeBetween('-90 years', '-18 years'),
        )->startOfDay();

        $joinedAt = CarbonImmutable::instance(
            fake()->dateTimeBetween($birthDate->addYears(18), 'now'),
        )->startOfDay();

        $hasIncome = fake()->boolean(85);
        $incomeInCents = $hasIncome
            ? fake()->numberBetween(2_000_000, 50_000_000)
            : null;

        return [
            'customer_number' => sprintf(
                'CUS-%08d',
                fake()->unique()->numberBetween(1, 99_999_999),
            ),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => $birthDate->format('Y-m-d'),
            'email' => fake()->boolean(90) ? fake()->unique()->safeEmail() : null,
            'phone' => fake()->boolean(90)
                ? sprintf('%s %06d', $location['phone_prefix'], fake()->numberBetween(1, 999_999))
                : null,
            'nationality' => fake()->randomElement(['LU', 'FR', 'DE', 'BE', 'CH']),
            'country_of_residence' => $location['country_code'],
            'city' => $location['city'],
            'postal_code' => $location['postal_code'],
            'occupation' => fake()->boolean(80) ? fake()->jobTitle() : null,
            'annual_income' => $incomeInCents === null
                ? null
                : sprintf(
                    '%d.%02d',
                    intdiv($incomeInCents, 100),
                    $incomeInCents % 100,
                ),
            'annual_income_currency' => $hasIncome ? $location['currency'] : null,
            'customer_segment' => fake()->randomElement([
                CustomerSegment::RETAIL,
                CustomerSegment::RETAIL,
                CustomerSegment::RETAIL,
                CustomerSegment::PREMIUM,
                CustomerSegment::PRIVATE_BANKING,
                CustomerSegment::BUSINESS,
            ]),
            'risk_level' => fake()->randomElement([
                RiskLevel::LOW,
                RiskLevel::LOW,
                RiskLevel::MEDIUM,
                RiskLevel::HIGH,
            ]),
            'joined_at' => $joinedAt->format('Y-m-d'),
            'status' => fake()->randomElement([
                CustomerStatus::ACTIVE,
                CustomerStatus::ACTIVE,
                CustomerStatus::ACTIVE,
                CustomerStatus::INACTIVE,
                CustomerStatus::SUSPENDED,
                CustomerStatus::CLOSED,
            ]),
        ];
    }
}
